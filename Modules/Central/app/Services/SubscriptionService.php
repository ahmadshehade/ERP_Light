<?php

namespace Modules\Central\Services;

use App\Enums\NameOfCache;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;

use App\Traits\ApplyFilters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use Modules\Central\Models\Subscription;
use Modules\Central\Models\SubscriptionPrice;
use Modules\Central\Services\Payments\PaymentService;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use Modules\Central\Services\Payments\StripePaymentService;
use Modules\Central\Services\Subscriptions\SubscriptionNotificationService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SubscriptionService
{
    use ApplyFilters;

    public  SubscriptionLifecycleService $lifeSycle;

    public function __construct(
        SubscriptionLifecycleService $lifeSycle,
        public PaymentService $paymentService,
        public StripePaymentService $stripePaymentService,
        public SubscriptionNotificationService $subscriptionNotification,
    ) {
        $this->lifeSycle = $lifeSycle;
    }


    /**
     * Generate cache key
     */
    protected function genKey(array $data = [], string $prefix = "", int $page = 1, int $perPage = 15): string
    {
        $user = Auth::user();
        $userKey = $user
            ? $user->id . "_" . $prefix . implode('_', $user->roles->pluck('name')->toArray())
            : '';
        $cacheData = [
            'filters' => $data,
            'page' => $page,
            'perpage' => $perPage
        ];
        return $userKey . "_" . NameOfCache::SUBSCRIPTION->value . "_" . md5(json_encode($cacheData));
    }
    /**
     * Flush subscription cache
     */
    protected function flushCache(): void
    {
        Cache::tags(NameOfCache::SUBSCRIPTION->value)->flush();
    }
    /**
     * Get all subscriptions
     */
    public function getAll(array $data = [])
    {
        $page = request()->integer('page', 1);
        $perPage = request()->integer('per_page', 15);
        $cacheKey = $this->genKey($data, '_no_trashed_', $page, $perPage);
        return Cache::tags(NameOfCache::SUBSCRIPTION->value)
            ->remember($cacheKey, 60, function () use ($data) {

                $query = Subscription::userSubscriptions(Auth::user())
                    ->with([
                        'company.owner',
                        'price'
                    ]);
                if (!empty($data)) {
                    $this->filterData($query, $data);
                }
                $this->sortData($query, $data, ['company_id', 'status', 'created_at', 'end_date', 'trial_end_date', 'canceled_at', 'start_date']);
                return $query->paginate(15);
            });
    }
    /**
     * Get single subscription
     */
    public function getSubscription(Subscription $subscription): Subscription
    {
        return $subscription->load([
            'company.owner',
            'price'
        ]);
    }
    /**
     * Create subscription
     */

    public function store(array $data): Subscription
    {
        return DB::transaction(function () use ($data) {
            $data = $this->lifeSycle->prepareSubscriptionData($data);
            $exists = Subscription::query()
                ->where('company_id', $data['company_id'])
                ->whereIn('status', [
                    SubscriptionStatus::PENDING->value,
                    SubscriptionStatus::ACTIVE->value,
                ])
                ->exists();
            if ($exists) {
                throw new BusinessRuleException(
                    'Company already has a subscription.',
                    409
                );
            }
            $price = SubscriptionPrice::query()
                ->where('is_active', true)
                ->findOrFail($data['price_id']);
            $subscription = new Subscription([
                'status' => SubscriptionStatus::PENDING->value,
            ]);
            $data = $this->lifeSycle->prepareSubscriptionStatus(
                $data,
                $price,
                $subscription
            );
            $data = $this->lifeSycle->prepareTrialData(
                $data,
                $price
            );
            $subscription = Subscription::create($data);
            $payment = $this->paymentService->store([
                'subscription_id' => $subscription->id,
                'amount' => $price->price,
                'currency' => 'usd',
                'status' => PaymentStatus::PENDING,
            ]);
            $checkout = $this->stripePaymentService
                ->createCheckoutSession($payment);
            $checkoutUrl = $checkout['url'];
            $subscription->setAttribute('checkout_url', $checkoutUrl);
            DB::afterCommit(function () use ($subscription, $checkoutUrl) {
                $this->flushCache();
                $this->subscriptionNotification
                    ->SubscriptionCreateNotification($subscription->id, $checkoutUrl);
            });
            return $subscription;
        }, 5);
    }


    /**
     * Update subscription
     */
    public function update(
        Subscription $subscription,
        array $data
    ): Subscription {
        return DB::transaction(function () use ($subscription, $data) {

            if (
                $subscription->status !== SubscriptionStatus::PENDING &&
                (isset($data['company_id']) || isset($data['price_id']))
            ) {
                throw new BusinessRuleException(
                    'Cannot change company or price because status is not pending. Current status: ' . $subscription->status->value,
                    409
                );
            }
            $oldPriceId = $subscription->price_id;
            $oldPlanName = $subscription->price->plan->getTranslation('name', 'en') ?? 'N/A';
            $oldPrice = $subscription->price->price;
            $oldStatus = $subscription->status;
            $newPriceId = $data['price_id'] ?? $oldPriceId;
            $priceChanged = ($oldPriceId != $newPriceId);

            Log::info('🔄 Updating subscription', [
                'subscription_id' => $subscription->id,
                'old_price_id' => $oldPriceId,
                'new_price_id' => $newPriceId,
                'price_changed' => $priceChanged,
                'old_plan' => $oldPlanName,
                'old_price' => $oldPrice,
            ]);
            $data = $this->lifeSycle->prepareSubscriptionData($data);
            $price = isset($data['price_id'])
                ? SubscriptionPrice::query()
                ->where('is_active', true)
                ->findOrFail($data['price_id'])
                : $subscription->price;
            $data = $this->lifeSycle->prepareSubscriptionStatus($data, $price, $subscription);
            if ($subscription->status === SubscriptionStatus::PENDING) {
                $data = $this->lifeSycle->prepareTrialData($data, $price);
            }
            $subscription->update($data);
            if ($priceChanged && $subscription->status === SubscriptionStatus::PENDING) {

                $oldPayment = $subscription->payments()
                    ->where('status', PaymentStatus::PENDING)
                    ->first();

                if ($oldPayment) {

                    try {

                        $this->stripePaymentService
                            ->expireCheckoutSession($oldPayment);

                        Log::info('💳 Old Stripe checkout session expired', [
                            'subscription_id' => $subscription->id,
                            'old_payment_id' => $oldPayment->id,
                            'checkout_session_id' => $oldPayment->checkout_session_id,
                        ]);
                    } catch (\Exception $e) {

                        Log::warning('⚠️ Could not expire old Stripe checkout session', [
                            'subscription_id' => $subscription->id,
                            'old_payment_id' => $oldPayment->id,
                            'checkout_session_id' => $oldPayment->checkout_session_id,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    $this->paymentService->update($oldPayment, [
                        'status' => PaymentStatus::CANCELED,
                    ]);
                }
                $newPayment = $this->paymentService->store([
                    'subscription_id' => $subscription->id,
                    'amount' => $price->price,
                    'currency' => 'usd',
                    'status' => PaymentStatus::PENDING,
                ]);
                $checkout = $this->stripePaymentService
                    ->createCheckoutSession($newPayment);
                $checkoutUrl = $checkout['url'];
                $subscription->setAttribute('checkout_url', $checkoutUrl);
                Log::info('💳 New payment created after price change', [
                    'subscription_id' => $subscription->id,
                    'new_payment_id' => $newPayment->id,
                    'new_amount' => $price->price,
                    'checkout_url' => $checkoutUrl,
                ]);
                DB::afterCommit(function () use ($subscription, $checkoutUrl, $oldPlanName, $oldPrice) {
                    $this->flushCache();
                    $newPlanName = $subscription->price->plan->getTranslation('name', 'en') ?? 'N/A';
                    $newPrice = $subscription->price->price;
                    Log::info('📤 Sending subscription changed notification', [
                        'subscription_id' => $subscription->id,
                        'old_plan' => $oldPlanName,
                        'new_plan' => $newPlanName,
                        'old_price' => $oldPrice,
                        'new_price' => $newPrice,
                    ]);
                    $this->subscriptionNotification
                        ->ChangedNotification(
                            subscriptionId: $subscription->id,
                            checkoutUrl: $checkoutUrl,
                            changes: [
                                'plan' => 'Plan updated from "' . $oldPlanName . '" to "' . $newPlanName . '"',
                                'price' => 'Price updated from $' . number_format($oldPrice, 2) . ' to $' . number_format($newPrice, 2),
                            ],
                            oldPlanName: $oldPlanName,
                            newPlanName: $newPlanName,
                            oldPrice: $oldPrice,
                            newPrice: $newPrice,
                        );
                });
            } else {

                $payment = $subscription->payments()
                    ->where('status', PaymentStatus::PENDING)
                    ->first();
                if ($payment) {
                    $this->paymentService->update($payment, [
                        'amount' => $price->price,
                    ]);
                }
                DB::afterCommit(function () {
                    $this->flushCache();
                });
            }

            return $subscription->load([
                'company.owner',
                'price',
                'payments',
            ]);
        }, 5);
    }
    /**
     * Soft delete
     */
    public function destroy(Subscription $subscription): bool
    {
        return DB::transaction(function () use ($subscription) {
            $subscription->delete();
            $this->flushCache();
            return true;
        });
    }
    /**
     * Restore subscription
     */
    public function restore(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            if (!$subscription->trashed()) {
                throw new BusinessRuleException(
                    'Subscription is not trashed.',
                    404
                );
            }
            $subscription->restore();
            $this->lifeSycle->refreshStatus($subscription);
            $this->flushCache();
            return $subscription->load([
                'company.owner',
                'price'
            ]);
        });
    }
    /**
     * Force delete
     */
    public function forceDelete(Subscription $subscription): bool
    {
        return DB::transaction(function () use ($subscription) {
            if (!$subscription->trashed()) {
                throw new BusinessRuleException(
                    'Subscription must be trashed first.',
                    404
                );
            }
            $subscription->forceDelete();
            $this->flushCache();
            return true;
        });
    }

    /**
     * Get trashed subscriptions
     */
    public function getAllTrashed(array $data = [])
    {
        $page = request()->integer('page', 1);
        $perPage = request()->integer('per_page', 15);
        $cacheKey = $this->genKey($data, '_trashed_', $page, $perPage);
        return Cache::tags(NameOfCache::SUBSCRIPTION->value)
            ->remember($cacheKey, 60, function () use ($data) {
                $query = Subscription::onlyTrashed()
                    ->userSubscriptions(Auth::user())
                    ->with([
                        'company.owner',
                        'price'
                    ]);
                if (!empty($data)) {
                    $this->filterData($query, $data);
                }
                $this->sortData($query, $data, ['company_id', 'status', 'created_at', 'end_date', 'trial_end_date', 'canceled_at', 'start_date']);
                return $query->paginate(15);
            });
    }
    /**
     * Get single trashed subscription
     */
    public function getTrashed(Subscription $subscription): Subscription
    {

        if (!$subscription->trashed()) {
            throw new BusinessRuleException(
                'Subscription is not trashed.',
                404
            );
        }
        return $subscription->load([
            'company.owner',
            'price'
        ]);
    }
    /**
     * Restore all
     */
    public function restoreAll(): bool
    {
        return DB::transaction(function () {

            $subscriptions = Subscription::onlyTrashed()
                ->userSubscriptions(Auth::user())
                ->get();
            if ($subscriptions->isEmpty()) {
                throw new BusinessRuleException(
                    'No trashed subscriptions found.',
                    404
                );
            }
            foreach ($subscriptions as $subscription) {
                $subscription->restore();
                $this->lifeSycle->refreshStatus($subscription);
            }
            $this->flushCache();

            return true;
        });
    }
    /**
     * Destroy all trashed
     */
    public function destroyAllTrashed(): bool
    {
        $query = Subscription::onlyTrashed()
            ->userSubscriptions(Auth::user());
        if (!$query->exists()) {

            throw new BusinessRuleException(
                'No trashed subscriptions found.',
                404
            );
        }
        $query->forceDelete();
        $this->flushCache();
        return true;
    }


    /**
     *Summary of renew
     * @param Subscription $subscription
     * @param array $data
     * @return Subscription
     */
    public function renew(
        Subscription $subscription,
        array $data
    ): Subscription {
        return DB::transaction(function () use ($subscription, $data) {

            $newSubscription = $this->lifeSycle->renew(
                $subscription,
                $data
            );

            $newSubscription->loadMissing([
                'company',
                'price',
            ]);

            if (!$newSubscription->price) {
                throw new BusinessRuleException(
                    'Renewed subscription price not found.',
                    404
                );
            }

            $payment = $this->paymentService->store([
                'subscription_id' => $newSubscription->id,
                'amount' => $newSubscription->price->price,
                'currency' => 'usd',
                'status' => PaymentStatus::PENDING,
            ]);

            $checkout = $this->stripePaymentService
                ->createCheckoutSession($payment);

            $checkoutUrl = $checkout['url'];

            $newSubscription->load([
                'company',
                'price',
                'payments',
            ]);

            $newSubscription->setAttribute(
                'checkout_url',
                $checkoutUrl
            );

            DB::afterCommit(function () use (
                $newSubscription,
                $checkoutUrl
            ) {
                $this->flushCache();

                $this->subscriptionNotification
                    ->renewNotification(
                        subscription: $newSubscription,
                        checkoutUrl: $checkoutUrl
                    );
                activity()->causedBy($this->authenticatedUser())->withProperties([
                    'subscription_id' => $newSubscription->id,
                    'checkout_url' => $checkoutUrl
                ])->log('Subscription renewed');
            });

            return $newSubscription;
        });
    }

    /**
     * Get the authenticated user
     */
    private function authenticatedUser(): User
    {
        $user = Auth::user();

        if (!$user instanceof User) {
            throw new RuntimeException('Authenticated user not found.', 404);
        }

        return $user;
    }
}
