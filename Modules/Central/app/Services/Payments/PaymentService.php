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
use App\Exceptions\BusinessRuleException;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentService
{

    use ApplyFilters;

    public const TIME_TTL = 60;
    /**
     * Generate cache key
     * @param array $data
     * @return string
     */
    protected function genKey(string $prefix, array $data = [], int $page = 1, int $perPage = 15): string
    {
        $user = Auth::user();
        $userKey = $user
            ? $user->id . implode('_', $user->roles->pluck('name')->toArray())
            : '';
        $cacheData = [
            'filters' => $data,
            'page' => $page,
            'perPage' => $perPage,
        ];
        return $userKey . "_" . NameOfCache::PAYMENT->value . "_" . md5(json_encode($cacheData));
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
    public function getAll(array $data = [])
    {
        $page = request()->integer('page', 1);
        $perPage = request()->integer('per_page', 15);

        $cacheKey = $this->genKey('_no_trashed_', $data, $page, $perPage);

        $cached = Cache::tags(NameOfCache::PAYMENT->value)->remember(
            $cacheKey,
            self::TIME_TTL,
            function () use ($data, $perPage) {

                $payments = Payment::query()
                    ->ownerPaymnets(Auth::user())
                    ->with('subscription.company.owner');

                if (!empty($data)) {
                    $this->filterData($payments, $data);
                }

                $this->sortData(
                    $payments,
                    $data,
                    ['subscription_id', 'status', 'created_at', 'amount', 'paid_at']
                );

                $paginator = $payments->paginate($perPage);

                return [
                    'ids' => $paginator->getCollection()->pluck('id')->all(),
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                ];
            }
        );

        $payments = Payment::query()
            ->ownerPaymnets(Auth::user())
            ->with('subscription.company.owner')
            ->whereIn('id', $cached['ids'])
            ->get()
            ->sortBy(fn($payment) => array_search($payment->id, $cached['ids']))
            ->values();

        return new LengthAwarePaginator(
            $payments,
            $cached['total'],
            $cached['per_page'],
            $cached['current_page'],
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
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
                throw new BusinessRuleException(
                    'Subscription already has a pending payment.',
                    409
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
                'metadata' => $data['metadata'] ?? $payment->metadata,
            ]);

            DB::afterCommit(function () {
                $this->flushCache();
            });

            return $payment->fresh()->load('subscription.company.owner');
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
                throw new BusinessRuleException(
                    'Payment is not trashed.',
                    404
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
                throw new BusinessRuleException('Payment is not trashed', 404);
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
                throw new BusinessRuleException('No trashed payments found', 404);
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
     * @throws BusinessRuleException
     */
    public function forceDeleteAll(): bool
    {
        return DB::transaction(function () {
            $payments = Payment::onlyTrashed()->get();
            if ($payments->isEmpty()) {
                throw new BusinessRuleException('No trashed payments found', 404);
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

    public function getAllTrashed(array $data = [])
    {
        $page = request()->integer('page', 1);
        $perPage = request()->integer('per_page', 15);

        $cacheKey = $this->genKey("_trashed_", $data, $page, $perPage);

        $cached = Cache::tags(NameOfCache::PAYMENT->value)->remember(
            $cacheKey,
            self::TIME_TTL,
            function () use ($data, $perPage) {
                $payments = Payment::onlyTrashed()
                    ->with('subscription.company.owner');
                if (!empty($data)) {
                    $this->filterData($payments, $data);
                }
                $this->sortData(
                    $payments,
                    $data,
                    ['subscription_id', 'status', 'created_at', 'amount', 'paid_at']
                );
                $paginator = $payments->paginate($perPage);
                return [
                    'ids' => $paginator->getCollection()->pluck('id')->all(),
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                ];
            }
        );

        $payments = Payment::onlyTrashed()
            ->with('subscription.company.owner')
            ->whereIn('id', $cached['ids'])
            ->get()
            ->sortBy(fn($payment) => array_search($payment->id, $cached['ids']))
            ->values();

        return new LengthAwarePaginator(
            $payments,
            $cached['total'],
            $cached['per_page'],
            $cached['current_page'],
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    /**
     * Get trashed payment
     * @param Payment $payment
     * @return Payment
     */
    public function getTrashed(Payment $payment): Payment
    {
        if (!$payment->trashed()) {
            throw new BusinessRuleException('Payment is not trashed', 404);
        }
        return $payment->load('subscription.company.owner');
    }
}
