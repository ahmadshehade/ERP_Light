<?php

namespace Modules\Central\Services\Dashboards;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\Central\Models\Company;
use Modules\Central\Models\Payment;
use Modules\Central\Models\Subscription;
use Modules\Central\Models\Tenant;

class OwnerDashboardService
{
    private const CACHE_TTL = 300;

    /*
    |--------------------------------------------------------------------------
    | Cache Keys
    |--------------------------------------------------------------------------
    */

    private const COMPANIES_STATISTICS_CACHE_KEY =
    'central:dashboard:owner:%s:companies_statistics';

    private const TENANTS_STATISTICS_CACHE_KEY =
    'central:dashboard:owner:%s:tenants_statistics';

    private const SUBSCRIPTIONS_STATISTICS_CACHE_KEY =
    'central:dashboard:owner:%s:subscriptions_statistics';

    private const PAYMENTS_STATISTICS_CACHE_KEY =
    'central:dashboard:owner:%s:payments_statistics';

    private const REVENUE_STATISTICS_CACHE_KEY =
    'central:dashboard:owner:%s:revenue_statistics';


    /*
    |--------------------------------------------------------------------------
    | Full Dashboard
    |--------------------------------------------------------------------------
    */

    public function getDashboard(): array
    {
        $ownerId = Auth::id();

        return [
            'companies' => $this->getCompaniesStatistics($ownerId),
            'tenants' => $this->getTenantsStatistics($ownerId),
            'subscriptions' => $this->getSubscriptionsStatistics($ownerId),
            'payments' => $this->getPaymentsStatistics($ownerId),
            'revenue' => $this->getRevenueStatistics($ownerId),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Companies
    |--------------------------------------------------------------------------
    */

    public function getCompaniesStatistics(?int $ownerId = null): array
    {
        $ownerId ??= Auth::id();

        $cacheKey = sprintf(
            self::COMPANIES_STATISTICS_CACHE_KEY,
            $ownerId
        );

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            fn(): array => $this->calculateCompaniesStatistics($ownerId)
        );
    }

    private function calculateCompaniesStatistics(int $ownerId): array
    {
        $statistics = Company::withTrashed()
            ->where('owner_id', $ownerId)
            ->selectRaw(
                '
                COUNT(*) AS total,

                SUM(deleted_at IS NULL) AS active_records,

                SUM(deleted_at IS NOT NULL) AS deleted,

                SUM(
                    deleted_at IS NULL
                    AND is_active = 1
                ) AS active,

                SUM(
                    deleted_at IS NULL
                    AND is_active = 0
                ) AS inactive,

                SUM(
                    created_at >= ?
                    AND deleted_at IS NULL
                ) AS new
                ',
                [
                    now()->subDays(30),
                ]
            )
            ->first();

        return [
            'total' => (int) $statistics->total,
            'active_records' => (int) $statistics->active_records,
            'deleted' => (int) $statistics->deleted,
            'active' => (int) $statistics->active,
            'inactive' => (int) $statistics->inactive,
            'new' => (int) $statistics->new,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Tenants
    |--------------------------------------------------------------------------
    */

    public function getTenantsStatistics(?int $ownerId = null): array
    {
        $ownerId ??= Auth::id();

        $cacheKey = sprintf(
            self::TENANTS_STATISTICS_CACHE_KEY,
            $ownerId
        );

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            fn(): array => $this->calculateTenantsStatistics($ownerId)
        );
    }

    private function calculateTenantsStatistics(int $ownerId): array
    {
        $statistics = Tenant::query()
            ->whereHas('company', function ($query) use ($ownerId) {
                $query->where('owner_id', $ownerId);
            })
            ->selectRaw(
                '
                COUNT(*) AS total,

                SUM(
                    created_at >= ?
                ) AS new
                ',
                [
                    now()->subDays(30),
                ]
            )
            ->first();

        return [
            'total' => (int) $statistics->total,
            'new' => (int) $statistics->new,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Subscriptions
    |--------------------------------------------------------------------------
    */

    public function getSubscriptionsStatistics(?int $ownerId = null): array
    {
        $ownerId ??= Auth::id();

        $cacheKey = sprintf(
            self::SUBSCRIPTIONS_STATISTICS_CACHE_KEY,
            $ownerId
        );

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            fn(): array => $this->calculateSubscriptionsStatistics($ownerId)
        );
    }

    private function calculateSubscriptionsStatistics(int $ownerId): array
    {
        $statistics = Subscription::withTrashed()
            ->whereHas('company', function ($query) use ($ownerId) {
                $query->where('owner_id', $ownerId);
            })
            ->selectRaw(
                '
                COUNT(*) AS total,

                SUM(deleted_at IS NULL) AS active_records,

                SUM(deleted_at IS NOT NULL) AS deleted,

                SUM(
                    status = ?
                    AND deleted_at IS NULL
                ) AS active,

                SUM(
                    status = ?
                    AND deleted_at IS NULL
                ) AS pending,

                SUM(
                    status = ?
                    AND deleted_at IS NULL
                ) AS canceled,

                SUM(
                    status = ?
                    AND deleted_at IS NULL
                ) AS expired,

                SUM(
                    status = ?
                    AND deleted_at IS NULL
                ) AS trial,

                SUM(
                    status = ?
                    AND deleted_at IS NULL
                ) AS trial_expired,

                SUM(
                    created_at >= ?
                    AND deleted_at IS NULL
                ) AS new
                ',
                [
                    SubscriptionStatus::ACTIVE->value,
                    SubscriptionStatus::PENDING->value,
                    SubscriptionStatus::CANCELED->value,
                    SubscriptionStatus::EXPIRED->value,
                    SubscriptionStatus::TRAIL->value,
                    SubscriptionStatus::TRAIL_EXPIRED->value,
                    now()->subDays(30),
                ]
            )
            ->first();

        return [
            'total' => (int) $statistics->total,
            'active_records' => (int) $statistics->active_records,
            'deleted' => (int) $statistics->deleted,
            'active' => (int) $statistics->active,
            'pending' => (int) $statistics->pending,
            'canceled' => (int) $statistics->canceled,
            'expired' => (int) $statistics->expired,
            'trial' => (int) $statistics->trial,
            'trial_expired' => (int) $statistics->trial_expired,
            'new' => (int) $statistics->new,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    */

    public function getPaymentsStatistics(?int $ownerId = null): array
    {
        $ownerId ??= Auth::id();

        $cacheKey = sprintf(
            self::PAYMENTS_STATISTICS_CACHE_KEY,
            $ownerId
        );

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            fn(): array => $this->calculatePaymentsStatistics($ownerId)
        );
    }

    private function calculatePaymentsStatistics(int $ownerId): array
    {
        $statistics = Payment::withTrashed()
            ->whereHas(
                'subscription.company',
                function ($query) use ($ownerId) {
                    $query->where('owner_id', $ownerId);
                }
            )
            ->selectRaw(
                '
                COUNT(*) AS total,

                SUM(deleted_at IS NULL) AS active_records,

                SUM(deleted_at IS NOT NULL) AS deleted,

                SUM(
                    status = ?
                    AND deleted_at IS NULL
                ) AS pending,

                SUM(
                    status = ?
                    AND deleted_at IS NULL
                ) AS paid,

                SUM(
                    status = ?
                    AND deleted_at IS NULL
                ) AS failed,

                SUM(
                    status = ?
                    AND deleted_at IS NULL
                ) AS refunded,

                SUM(
                    status = ?
                    AND deleted_at IS NULL
                ) AS canceled,

                SUM(
                    created_at >= ?
                    AND deleted_at IS NULL
                ) AS new
                ',
                [
                    PaymentStatus::PENDING->value,
                    PaymentStatus::PAID->value,
                    PaymentStatus::FAILED->value,
                    PaymentStatus::REFUNDED->value,
                    PaymentStatus::CANCELED->value,
                    now()->subDays(30),
                ]
            )
            ->first();

        return [
            'total' => (int) $statistics->total,
            'active_records' => (int) $statistics->active_records,
            'deleted' => (int) $statistics->deleted,
            'pending' => (int) $statistics->pending,
            'paid' => (int) $statistics->paid,
            'failed' => (int) $statistics->failed,
            'refunded' => (int) $statistics->refunded,
            'canceled' => (int) $statistics->canceled,
            'new' => (int) $statistics->new,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Revenue
    |--------------------------------------------------------------------------
    */

    public function getRevenueStatistics(?int $ownerId = null): array
    {
        $ownerId ??= Auth::id();

        $cacheKey = sprintf(
            self::REVENUE_STATISTICS_CACHE_KEY,
            $ownerId
        );

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            fn(): array => $this->calculateRevenueStatistics($ownerId)
        );
    }

    private function calculateRevenueStatistics(int $ownerId): array
    {
        $revenue = Payment::query()
            ->where('status', PaymentStatus::PAID->value)
            ->whereNotNull('paid_at')
            ->whereHas(
                'subscription.company',
                function ($query) use ($ownerId) {
                    $query->where('owner_id', $ownerId);
                }
            )
            ->selectRaw(
                '
                currency,

                COUNT(*) AS transactions,

                COALESCE(SUM(amount), 0) AS total,

                COALESCE(
                    SUM(
                        CASE
                            WHEN paid_at >= ? THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS last_30_days
                ',
                [
                    now()->subDays(30),
                ]
            )
            ->groupBy('currency')
            ->get();

        return [
            'currencies' => $revenue
                ->map(
                    static fn($row): array => [
                        'currency' => $row->currency,
                        'transactions' => (int) $row->transactions,
                        'total' => (string) $row->total,
                        'last_30_days' => (string) $row->last_30_days,
                    ]
                )
                ->values()
                ->all(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Cache Clearing
    |--------------------------------------------------------------------------
    */

    public function clearCompaniesStatisticsCache(
        ?int $ownerId = null
    ): void {
        $ownerId ??= Auth::id();

        Cache::forget(
            sprintf(
                self::COMPANIES_STATISTICS_CACHE_KEY,
                $ownerId
            )
        );
    }

    public function clearTenantsStatisticsCache(
        ?int $ownerId = null
    ): void {
        $ownerId ??= Auth::id();

        Cache::forget(
            sprintf(
                self::TENANTS_STATISTICS_CACHE_KEY,
                $ownerId
            )
        );
    }

    public function clearSubscriptionsStatisticsCache(
        ?int $ownerId = null
    ): void {
        $ownerId ??= Auth::id();

        Cache::forget(
            sprintf(
                self::SUBSCRIPTIONS_STATISTICS_CACHE_KEY,
                $ownerId
            )
        );
    }

    public function clearPaymentsStatisticsCache(
        ?int $ownerId = null
    ): void {
        $ownerId ??= Auth::id();

        Cache::forget(
            sprintf(
                self::PAYMENTS_STATISTICS_CACHE_KEY,
                $ownerId
            )
        );
    }

    public function clearRevenueStatisticsCache(
        ?int $ownerId = null
    ): void {
        $ownerId ??= Auth::id();

        Cache::forget(
            sprintf(
                self::REVENUE_STATISTICS_CACHE_KEY,
                $ownerId
            )
        );
    }

    public function clearAllDashboardCaches(
        ?int $ownerId = null
    ): void {
        $ownerId ??= Auth::id();

        $this->clearCompaniesStatisticsCache($ownerId);
        $this->clearTenantsStatisticsCache($ownerId);
        $this->clearSubscriptionsStatisticsCache($ownerId);
        $this->clearPaymentsStatisticsCache($ownerId);
        $this->clearRevenueStatisticsCache($ownerId);
    }
}
