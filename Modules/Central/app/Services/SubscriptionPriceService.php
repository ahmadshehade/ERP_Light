<?php

namespace Modules\Central\Services;

use App\Enums\NameOfCache;
use App\Enums\PriceInterval;
use App\Enums\SubscriptionStatus;
use App\Traits\ApplyFilters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Central\Models\SubscriptionPlan;
use Modules\Central\Models\SubscriptionPrice;
use Modules\Central\Services\SubscriptionPrices\SubscriptionPriceNotification;
use RuntimeException;

class SubscriptionPriceService
{
    use ApplyFilters;

    public function __construct(public SubscriptionPriceNotification $priceNotify) {}

    public const TIME_TTL = 60;

    /**
     * Summary of genKey
     * @param array $data
     * @param string $prefix
     * @return string
     * @throws \Exception
     */
    private function genKey(array $data = [], string $prefix = ''): string
    {
        $user = Auth::user();
        $userKey = $user ? $user->id . "_" . $prefix . "_" . implode("_", $user->roles->pluck('name')->toArray()) : "";
        $cacheKey = $userKey . NameOfCache::SUBSCRIPTION_PRICE->value . "_" . md5(json_encode($data));
        return $cacheKey;
    }

    /**
     * Summary of cacheFlush
     * @return void
     */
    private function cacheFlush(): void
    {
        Cache::tags(NameOfCache::SUBSCRIPTION_PRICE->value)->flush();
    }

    /**
     * Summary of getAll
     * @param array $data
     * @return array
     */
    public function getAll(array $data = []): array
    {
        $cacheKey = $this->genKey($data, "all_SubscriptionPrice_");
        return Cache::tags(NameOfCache::SUBSCRIPTION_PRICE->value)
            ->remember($cacheKey, self::TIME_TTL, function () use ($data) {
                $prices = SubscriptionPrice::query()->active(Auth::user());
                if (! empty($data)) {
                    $this->filterData($prices, $data);
                }
                return $prices->get()->toArray();
            });
    }


    /**

     * Retrieve a SubscriptionPrice along with its associated plan.
     *
     * @param SubscriptionPrice $subscriptionPrice The SubscriptionPrice model to retrieve.
     * @return SubscriptionPrice The SubscriptionPrice model with the associated plan loaded.
     */
    public function get(SubscriptionPrice $subscriptionPrice): SubscriptionPrice
    {
        return $subscriptionPrice->load(['plan']);
    }


    /**
     * Summary of store
     * @param array $data
     * @return SubscriptionPrice
     */
    public function store(array $data): SubscriptionPrice
    {
        return DB::transaction(function () use ($data) {
            $data = $this->prepareData($data);
            $price = SubscriptionPrice::create($data);
            DB::afterCommit(function () use ($price) {
                $this->cacheFlush();
                if ($price->is_active) {
                    $this->priceNotify->activeNotification($price);
                }
            });
            return $price->load(['plan']);
        }, 5);
    }

    /**
     * Summary of update
     * @param SubscriptionPrice $subscriptionPrice
     * @param array $data
     * @return SubscriptionPrice
     */
    public function update(
        SubscriptionPrice $subscriptionPrice,
        array $data
    ): SubscriptionPrice {
        return DB::transaction(function () use ($subscriptionPrice, $data) {
            $data = $this->prepareData($data);
            $hasSubscriptions = $subscriptionPrice
                ->subscriptions()
                ->whereIn('status', [
                    SubscriptionStatus::ACTIVE->value,
                    SubscriptionStatus::PENDING->value,
                ])
                ->exists();
            $newPlanId = $data['plan_id'] ?? $subscriptionPrice->plan_id;
            $newPrice = $data['price'] ?? $subscriptionPrice->price;
            $newInterval = isset($data['interval'])
                ? PriceInterval::from($data['interval'])
                : $subscriptionPrice->interval;
            $newHasTrial = $data['has_trial'] ?? $subscriptionPrice->has_trial;
            $newTrialDays = $data['trial_days'] ?? $subscriptionPrice->trial_days;
            $newIsActive = $data['is_active'] ?? $subscriptionPrice->is_active;
            if ($hasSubscriptions) {

                $changingPriceData =
                    $newPlanId != $subscriptionPrice->plan_id
                    || $newPrice != $subscriptionPrice->price
                    || $newInterval !== $subscriptionPrice->interval
                    || $newHasTrial != $subscriptionPrice->has_trial
                    || $newTrialDays != $subscriptionPrice->trial_days;
                if ($changingPriceData) {
                    throw new RuntimeException(
                        'Cannot modify subscription price because it is used by active or pending subscriptions.'
                    );
                }
                if (
                    $newIsActive === true &&
                    !$subscriptionPrice->is_active
                ) {
                    throw new RuntimeException(
                        'Cannot activate this price while it is associated with active or pending subscriptions.'
                    );
                }
            }

            if ($newIsActive === true) {
                $anotherActivePriceExists = SubscriptionPrice::query()
                    ->where('plan_id', $newPlanId)
                    ->where('is_active', true)
                    ->where('id', '!=', $subscriptionPrice->id)
                    ->exists();
                if ($anotherActivePriceExists) {
                    throw new RuntimeException(
                        'This subscription plan already has an active price.'
                    );
                }
            }
            $subscriptionPrice->update($data);
            $wasDeactivated =
                $subscriptionPrice->wasChanged('is_active')
                && !$subscriptionPrice->is_active;
            DB::afterCommit(function () use (
                $subscriptionPrice,
                $wasDeactivated
            ) {
                $this->cacheFlush();
                if ($subscriptionPrice->is_active) {
                    $this->priceNotify->updatePriceNotify(
                        $subscriptionPrice
                    );
                }
                if ($wasDeactivated) {
                    $this->priceNotify->deActiveNotify(
                        $subscriptionPrice
                    );
                }
            });
            return $subscriptionPrice->load(['plan']);
        }, 5);
    }


    /**
     * Summary of delete
     * @param SubscriptionPrice $subscriptionPrice
     * @return bool
     */
    public function delete(SubscriptionPrice $subscriptionPrice): bool
    {
        return DB::transaction(function () use ($subscriptionPrice) {
            if ($subscriptionPrice->trashed()) {
                throw new RuntimeException(
                    'Subscription price is already deleted.'
                );
            }
            $hasSubscriptions = $subscriptionPrice
                ->subscriptions()
                ->whereIn('status', [
                    SubscriptionStatus::ACTIVE->value,
                    SubscriptionStatus::PENDING->value,
                ])
                ->exists();
            if ($hasSubscriptions) {
                throw new RuntimeException(
                    'Cannot delete subscription price because it is used by active or pending subscriptions. Deactivate it instead.'
                );
            }
            $subscriptionPrice->delete();
            DB::afterCommit(function () {
                $this->cacheFlush();
            });
            return true;
        }, 5);
    }


    /**
     * Summary of restoreSubscriptionPrice
     * @param SubscriptionPrice $subscriptionPrice
     * @return SubscriptionPrice
     */
    public function restoreSubscriptionPrice(
        SubscriptionPrice $subscriptionPrice
    ): SubscriptionPrice {
        return DB::transaction(function () use ($subscriptionPrice) {
            if (!$subscriptionPrice->trashed()) {
                throw new RuntimeException(
                    'Subscription Price is not trashed.'
                );
            }
            $activePriceExists = SubscriptionPrice::query()
                ->where('plan_id', $subscriptionPrice->plan_id)
                ->where('is_active', true)
                ->exists();
            if ($activePriceExists) {
                throw new RuntimeException(
                    'Cannot restore this price because the subscription plan already has an active price.'
                );
            }
            $subscriptionPrice->restore();
            $this->cacheFlush();
            return $subscriptionPrice->load(['plan']);
        }, 5);
    }

    /**
     * Summary of forceDeleteSubscriptionPrice
     * @param SubscriptionPrice $subscriptionPrice
     * @return bool
     */
    public function forceDeleteSubscriptionPrice(SubscriptionPrice $subscriptionPrice): bool
    {
        return DB::transaction(function () use ($subscriptionPrice) {
            if (!$subscriptionPrice->trashed()) {
                throw new RuntimeException('Subscription Price is not trashed and cannot be force deleted.');
            }
            if ($subscriptionPrice->subscriptions()->exists()) {
                throw new RuntimeException(
                    'Cannot delete subscription price used by subscriptions.'
                );
            }
            $subscriptionPrice->forceDelete();
            $this->cacheFlush();
            return true;
        }, 5);
    }

    /**
     * Summary of restoreAll
     * @return bool
     */
    public function restoreSubscriptionPrices(): bool
    {
        return DB::transaction(function () {
            $prices = SubscriptionPrice::onlyTrashed()->get();
            if ($prices->isEmpty()) {
                throw new RuntimeException(
                    'No trashed subscription prices to restore.'
                );
            }
            foreach ($prices as $price) {
                if ($price->is_active) {
                    $activePriceExists = SubscriptionPrice::query()
                        ->where('plan_id', $price->plan_id)
                        ->where('is_active', true)
                        ->exists();
                    if ($activePriceExists) {
                        throw new RuntimeException(
                            "Cannot restore subscription price {$price->id} because its plan already has an active price."
                        );
                    }
                }
                $price->restore();
            }
            DB::afterCommit(function () {
                $this->cacheFlush();
            });
            return true;
        }, 5);
    }

    /**
     * Summary of forceDeleteAll
     * @return bool
     * @throws \Exception
     */
    public function forceDeleteSubscriptionPrices(): bool
    {
        return DB::transaction(function () {
            $prices = SubscriptionPrice::onlyTrashed()
                ->withCount('subscriptions')
                ->get();
            if ($prices->isEmpty()) {
                throw new RuntimeException(
                    'No trashed subscription prices to force delete.'
                );
            }
            $usedPrices = $prices->filter(
                fn(SubscriptionPrice $price) =>
                $price->subscriptions_count > 0
            );
            if ($usedPrices->isNotEmpty()) {
                throw new RuntimeException(
                    'Some trashed subscription prices are used by subscriptions and cannot be force deleted.'
                );
            }
            SubscriptionPrice::onlyTrashed()->forceDelete();
            DB::afterCommit(function () {
                $this->cacheFlush();
            });
            return true;
        }, 5);
    }

    /**
     * Summary of getTrashedSubscriptionPrice
     * @param array $data
     * @return array
     */
    public function getTrashedSubscriptionPrices(array $data = []): array
    {
        $cacheKey = $this->genKey($data, "_trashed_SubscriptionPrice_");
        return Cache::tags(NameOfCache::SUBSCRIPTION_PRICE->value)
            ->remember($cacheKey, self::TIME_TTL, function () use ($data) {
                $prices = SubscriptionPrice::query()->onlyTrashed();
                if (! empty($data)) {
                    $this->filterData($prices, $data);
                }
                return $prices->get()->toArray();
            });
    }

    /**
     *  Summary of getTrashedSubscriptionPrice
     * @param SubscriptionPrice $subscriptionPrice
     * @return SubscriptionPrice
     */
    public function getTrashedSubscriptionPrice(SubscriptionPrice $subscriptionPrice): SubscriptionPrice
    {
        if (!$subscriptionPrice->trashed()) {
            throw new RuntimeException('Subscription Price is not trashed');
        }
        return $subscriptionPrice->load(['plan']);
    }

    /**
     * Summary of preperData
     * @param array $data
     * @return array
     */
    protected function prepareData(array $data): array
    {
        if (isset($data['plan_id'])) {
            SubscriptionPlan::query()
                ->where('is_active', true)
                ->findOrFail($data['plan_id']);
        }

        return $data;
    }
}
