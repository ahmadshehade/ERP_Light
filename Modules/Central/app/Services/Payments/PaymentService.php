<?php

namespace Modules\Central\Services\Payments;

use App\Enums\NameOfCache;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Traits\ApplyFilters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Central\Models\Payment;
use Modules\Central\Models\Subscription;
use RuntimeException;

class PaymentService
{

    use ApplyFilters;

    public const TIME_TTL = 60;
    /**
     * Generate cache key
     * @param array $data
     * @return string
     */
    protected function genKey(array $data = [], string $prefix): string
    {
        $user = Auth::user();
        $userKey = $user ? $user->id . implode('_', $user->roles->pluck('name')->toArray()) : '';
        $cacheKey = $userKey . $prefix . NameOfCache::PAYMENT->value . md5(json_encode($data));
        return $cacheKey;
    }

    /**
     * Flush cache
     * @return void
     */
    protected function flushCache(): void
    {
        Cache::tags(NameOfCache::PAYMENT->value)->flush();
    }

    /**
     * Get all payments
     * @param array $data
     * @return array
     */
    public function getAll(array $data = []): array
    {
        $cacheKey = $this->genKey($data, "_no_trashed_");
        return Cache::tags(NameOfCache::PAYMENT->value)->remember($cacheKey, self::TIME_TTL, function () use ($data) {
            $payments = Payment::query()->ownerPaymnets(Auth::user())->with('subscription.company.owner');
            if (!empty($data)) {
                $this->filterData($payments, $data);
            }
            return $payments->get()->toArray();
        });
    }

    /**
     * Get payment
     * @param Payment $payment
     * @return Payment
     */
    public function get(Payment $payment): Payment
    {
        return $payment->load('subscription.company.owner');
    }

    /**
     * Store payment
     * @param array $data
     * @return Payment
     */
    public function store(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $subscription =  Subscription::where('status', SubscriptionStatus::PENDING->value)
                ->findOrFail($data['subscription_id']);
            $exists = Payment::where('subscription_id', $subscription->id)
                ->where('status', PaymentStatus::PENDING->value)
                ->exists();

            if ($exists) {
                throw new RuntimeException(
                    'Subscription already has a pending payment.'
                );
            }
            $payment = Payment::create($data);
            DB::afterCommit(function () {
                $this->flushCache();
            });
            return $payment->load('subscription.company.owner');
        });
    }

    /**
     * Update payment
     * @param Payment $payment
     * @param array $data
     */
    public function update(Payment $payment, array $data): Payment
    {
        return DB::transaction(function () use ($payment, $data) {

            $payment->update([
                'amount' => $data['amount'] ?? $payment->amount,
                'currency' => $data['currency'] ?? $payment->currency,
                'payment_method' => $data['payment_method'] ?? $payment->payment_method,
                'status' => $data['status'] ?? $payment->status,
                'paid_at' => $data['paid_at'] ?? $payment->paid_at,
                'gateway' => $data['gateway'] ?? $payment->gateway,
                'transaction_id' => $data['transaction_id'] ?? $payment->transaction_id,
                'metadata' => $data['metadata'] ?? $payment->metadata,
            ]);

            DB::afterCommit(function () {
                $this->flushCache();
            });

            return $payment->load('subscription.company.owner');
        });
    }

    /**
     * Destroy payment
     * @param Payment $payment
     * @return bool
     */
    public function destroy(Payment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            $payment->delete();
            DB::afterCommit(function () {
                $this->flushCache();
            });
            return true;
        });
    }

    /**
     * Restore payment
     * @param Payment $payment
     * @return Payment
     */
    public function restore(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            if (!$payment->trashed()) {
                throw new RuntimeException(
                    'Payment is not trashed.'
                );
            }
            $payment->restore();
            DB::afterCommit(function () {
                $this->flushCache();
            });
            return $payment->load(['subscription.company.owner']);
        });
    }

    /**
     * Force delete payment
     * @param Payment $payment
     * @return bool
     */
    public function forceDelete(Payment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            if (!$payment->trashed()) {
                throw new RuntimeException('Payment is not trashed');
            }
            $payment->forceDelete();
            DB::afterCommit(function () {
                $this->flushCache();
            });
            return true;
        });
    }

    /**
     * Restore all payments
     * @return bool
     */
    public function restoreAll(): bool
    {
        return DB::transaction(function () {
            $payments = Payment::onlyTrashed()->get();
            if ($payments->isEmpty()) {
                throw new RuntimeException('No trashed payments found');
            }
            foreach ($payments as $payment) {
                $payment->restore();
            }
            DB::afterCommit(function () {
                $this->flushCache();
            });
            return true;
        });
    }

    /**
     * Force delete all payments
     * @return bool
     * @throws RuntimeException
     */
    public function forceDeleteAll(): bool
    {
        return DB::transaction(function () {
            $payments = Payment::onlyTrashed()->get();
            if ($payments->isEmpty()) {
                throw new RuntimeException('No trashed payments found');
            }
            foreach ($payments as $payment) {
                $payment->forceDelete();
            }
            DB::afterCommit(function () {
                $this->flushCache();
            });
            return true;
        });
    }


    /**
     * Get trashed payments
     * @param array $data
     * @return array
     */
    public function getAllTrashed(array $data = []): array
    {
        $cacheKey = $this->genKey($data, "_trashed_");
        return Cache::tags(NameOfCache::PAYMENT->value)->remember($cacheKey, self::TIME_TTL, function () use ($data) {
            $payments = Payment::onlyTrashed()->with('subscription.company.owner');
            if (!empty($data)) {
                $this->filterData($payments, $data);
            }
            return $payments->get()->toArray();
        });
    }

    /**
     * Get trashed payment
     * @param Payment $payment
     * @return Payment
     */
    public function getTrashed(Payment $payment): Payment
    {
        if (!$payment->trashed()) {
            throw new RuntimeException('Payment is not trashed');
        }
        return $payment->load('subscription.company.owner');
    }
}
