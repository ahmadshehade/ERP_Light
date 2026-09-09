<?php

namespace Modules\Central\Services\Dashboards;

use App\Enums\PaymentStatus;
use App\Enums\PriceInterval;
use App\Enums\SubscriptionStatus;
use App\Models\ActivityCentral;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Modules\Central\Models\Company;
use Modules\Central\Models\Payment;
use Modules\Central\Models\Subscription;
use Modules\Central\Models\SubscriptionPlan;
use Modules\Central\Models\SubscriptionPrice;
use Modules\Central\Models\Tenant;

class SuperAdminDashboardService
{
    private const CACHE_TTL = 300;

    /*
    |--------------------------------------------------------------------------
    | Cache Keys
    |--------------------------------------------------------------------------
    */

    private const USERS_STATISTICS_CACHE_KEY =
    'central:dashboard:super_admin:users_statistics';

    private const PROFILES_STATISTICS_CACHE_KEY =
    'central:dashboard:super_admin:profiles_statistics';

    private const COMPANIES_STATISTICS_CACHE_KEY =
    'central:dashboard:super_admin:companies_statistics';

    private const TENANTS_STATISTICS_CACHE_KEY =
    'central:dashboard:super_admin:tenants_statistics';

    private const SUBSCRIPTION_PLANS_STATISTICS_CACHE_KEY =
    'central:dashboard:super_admin:subscription_plans_statistics';

    private const SUBSCRIPTION_PRICES_STATISTICS_CACHE_KEY =
    'central:dashboard:super_admin:subscription_prices_statistics';

    private const SUBSCRIPTIONS_STATISTICS_CACHE_KEY =
    'central:dashboard:super_admin:subscriptions_statistics';

    private const PAYMENTS_STATISTICS_CACHE_KEY =
    'central:dashboard:super_admin:payments_statistics';

    private const REVENUE_STATISTICS_CACHE_KEY =
    'central:dashboard:super_admin:revenue_statistics';

    private const ACTIVITY_STATISTICS_CACHE_KEY =
    'central:dashboard:super_admin:activity_statistics';


    /*
    |--------------------------------------------------------------------------
    | Full Dashboard
    |--------------------------------------------------------------------------
    */

    public function getDashboard(): array
    {
        return [
            'users' => $this->getUsersStatistics(),
            'profiles' => $this->getProfilesStatistics(),
            'companies' => $this->getCompaniesStatistics(),
            'tenants' => $this->getTenantsStatistics(),
            'subscription_plans' => $this->getSubscriptionPlansStatistics(),
            'subscription_prices' => $this->getSubscriptionPricesStatistics(),
            'subscriptions' => $this->getSubscriptionsStatistics(),
            'payments' => $this->getPaymentsStatistics(),
            'revenue' => $this->getRevenueStatistics(),
            'activity' => $this->getActivityStatistics(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */

    public function getUsersStatistics(): array
    {
        return Cache::remember(
            self::USERS_STATISTICS_CACHE_KEY,
            self::CACHE_TTL,
            fn(): array => $this->calculateUsersStatistics()
        );
    }

    private function calculateUsersStatistics(): array
    {
        $statistics = User::withTrashed()
            ->selectRaw(
                '
                COUNT(*) AS total,

                SUM(deleted_at IS NULL) AS active,

                SUM(deleted_at IS NOT NULL) AS deleted,

                SUM(
                    deleted_at IS NULL
                    AND email_verified_at IS NOT NULL
                ) AS verified,

                SUM(
                    deleted_at IS NULL
                    AND email_verified_at IS NULL
                ) AS unverified,

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
            'active' => (int) $statistics->active,
            'deleted' => (int) $statistics->deleted,
            'verified' => (int) $statistics->verified,
            'unverified' => (int) $statistics->unverified,
            'new' => (int) $statistics->new,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Profiles
    |--------------------------------------------------------------------------
    */

    public function getProfilesStatistics(): array
    {
        return Cache::remember(
            self::PROFILES_STATISTICS_CACHE_KEY,
            self::CACHE_TTL,
            fn(): array => $this->calculateProfilesStatistics()
        );
    }

    private function calculateProfilesStatistics(): array
    {
        $statistics = Profile::query()
            ->selectRaw(
                '
                COUNT(*) AS total,

                SUM(
                    phone IS NOT NULL
                    AND timezone IS NOT NULL
                    AND language IS NOT NULL
                    AND birth_date IS NOT NULL
                ) AS completed,

                SUM(
                    phone IS NULL
                    OR timezone IS NULL
                    OR language IS NULL
                    OR birth_date IS NULL
                ) AS incomplete
                '
            )
            ->first();

        return [
            'total' => (int) $statistics->total,
            'completed' => (int) $statistics->completed,
            'incomplete' => (int) $statistics->incomplete,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Companies
    |--------------------------------------------------------------------------
    */

    public function getCompaniesStatistics(): array
    {
        return Cache::remember(
            self::COMPANIES_STATISTICS_CACHE_KEY,
            self::CACHE_TTL,
            fn(): array => $this->calculateCompaniesStatistics()
        );
    }

    private function calculateCompaniesStatistics(): array
    {
        $statistics = Company::withTrashed()
            ->selectRaw(
                '
                COUNT(*) AS total,

                SUM(deleted_at IS NULL) AS active,

                SUM(
                    deleted_at IS NOT NULL
                ) AS deleted,

                SUM(
                    deleted_at IS NULL
                    AND is_active = 1
                ) AS enabled,

                SUM(
                    deleted_at IS NULL
                    AND is_active = 0
                ) AS disabled,

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
            'active' => (int) $statistics->active,
            'deleted' => (int) $statistics->deleted,
            'enabled' => (int) $statistics->enabled,
            'disabled' => (int) $statistics->disabled,
            'new' => (int) $statistics->new,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Tenants
    |--------------------------------------------------------------------------
    */

    public function getTenantsStatistics(): array
    {
        return Cache::remember(
            self::TENANTS_STATISTICS_CACHE_KEY,
            self::CACHE_TTL,
            fn(): array => $this->calculateTenantsStatistics()
        );
    }

    private function calculateTenantsStatistics(): array
    {
        $statistics = Tenant::query()
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
    | Subscription Plans
    |--------------------------------------------------------------------------
    */

    public function getSubscriptionPlansStatistics(): array
    {
        return Cache::remember(
            self::SUBSCRIPTION_PLANS_STATISTICS_CACHE_KEY,
            self::CACHE_TTL,
            fn(): array => $this->calculateSubscriptionPlansStatistics()
        );
    }

    private function calculateSubscriptionPlansStatistics(): array
    {
        $statistics = SubscriptionPlan::withTrashed()
            ->selectRaw(
                '
                COUNT(*) AS total,

                SUM(
                    deleted_at IS NULL
                    AND is_active = 1
                ) AS active,

                SUM(
                    deleted_at IS NULL
                    AND is_active = 0
                ) AS inactive,

                SUM(
                    deleted_at IS NOT NULL
                ) AS deleted,

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
            'active' => (int) $statistics->active,
            'inactive' => (int) $statistics->inactive,
            'deleted' => (int) $statistics->deleted,
            'new' => (int) $statistics->new,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Subscription Prices
    |--------------------------------------------------------------------------
    */

    public function getSubscriptionPricesStatistics(): array
    {
        return Cache::remember(
            self::SUBSCRIPTION_PRICES_STATISTICS_CACHE_KEY,
            self::CACHE_TTL,
            fn(): array => $this->calculateSubscriptionPricesStatistics()
        );
    }

    private function calculateSubscriptionPricesStatistics(): array
    {
        $statistics = SubscriptionPrice::withTrashed()
            ->selectRaw(
                '
            COUNT(*) AS total,

            SUM(
                deleted_at IS NULL
                AND is_active = 1
            ) AS active,

            SUM(
                deleted_at IS NULL
                AND is_active = 0
            ) AS inactive,

            SUM(
                deleted_at IS NOT NULL
            ) AS deleted,

            SUM(
                deleted_at IS NULL
                AND has_trial = 1
            ) AS with_trial,

            SUM(
                deleted_at IS NULL
                AND has_trial = 0
            ) AS without_trial
            '
            )
            ->first();

        $intervals = SubscriptionPrice::query()
            ->selectRaw(
                '
            SUM(
                CASE
                    WHEN `interval` = ? THEN 1
                    ELSE 0
                END
            ) AS day,

            SUM(
                CASE
                    WHEN `interval` = ? THEN 1
                    ELSE 0
                END
            ) AS week,

            SUM(
                CASE
                    WHEN `interval` = ? THEN 1
                    ELSE 0
                END
            ) AS month,

            SUM(
                CASE
                    WHEN `interval` = ? THEN 1
                    ELSE 0
                END
            ) AS year
            ',
                [
                    PriceInterval::DAY->value,
                    PriceInterval::WEEK->value,
                    PriceInterval::MONTH->value,
                    PriceInterval::YEAR->value,
                ]
            )
            ->first();

        return [
            'total' => (int) $statistics->total,
            'active' => (int) $statistics->active,
            'inactive' => (int) $statistics->inactive,
            'deleted' => (int) $statistics->deleted,
            'with_trial' => (int) $statistics->with_trial,
            'without_trial' => (int) $statistics->without_trial,

            'intervals' => [
                'day' => (int) ($intervals->day ?? 0),
                'week' => (int) ($intervals->week ?? 0),
                'month' => (int) ($intervals->month ?? 0),
                'year' => (int) ($intervals->year ?? 0),
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Subscriptions
    |--------------------------------------------------------------------------
    */

    public function getSubscriptionsStatistics(): array
    {
        return Cache::remember(
            self::SUBSCRIPTIONS_STATISTICS_CACHE_KEY,
            self::CACHE_TTL,
            fn(): array => $this->calculateSubscriptionsStatistics()
        );
    }

    private function calculateSubscriptionsStatistics(): array
    {
        $statistics = Subscription::withTrashed()
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

    public function getPaymentsStatistics(): array
    {
        return Cache::remember(
            self::PAYMENTS_STATISTICS_CACHE_KEY,
            self::CACHE_TTL,
            fn(): array => $this->calculatePaymentsStatistics()
        );
    }

    private function calculatePaymentsStatistics(): array
    {
        $statistics = Payment::withTrashed()
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

    public function getRevenueStatistics(): array
    {
        return Cache::remember(
            self::REVENUE_STATISTICS_CACHE_KEY,
            self::CACHE_TTL,
            fn(): array => $this->calculateRevenueStatistics()
        );
    }

    private function calculateRevenueStatistics(): array
    {
        /*
         * لا نجمع العملات المختلفة مع بعضها.
         * مثال: USD + EUR لا يمكن جمعهما في رقم واحد.
         */
        $revenue = Payment::query()
            ->where('status', PaymentStatus::PAID->value)
            ->whereNotNull('paid_at')
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
            'currencies' => $revenue->map(
                static fn($row): array => [
                    'currency' => $row->currency,
                    'transactions' => (int) $row->transactions,
                    'total' => (string) $row->total,
                    'last_30_days' => (string) $row->last_30_days,
                ]
            )->values()->all(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Activity
    |--------------------------------------------------------------------------
    */

    public function getActivityStatistics(): array
    {
        return Cache::remember(
            self::ACTIVITY_STATISTICS_CACHE_KEY,
            self::CACHE_TTL,
            fn(): array => $this->calculateActivityStatistics()
        );
    }

    private function calculateActivityStatistics(): array
    {
        $statistics = ActivityCentral::query()
            ->selectRaw(
                '
                COUNT(*) AS total,

                SUM(
                    created_at >= ?
                ) AS last_30_days
                ',
                [
                    now()->subDays(30),
                ]
            )
            ->first();

        return [
            'total' => (int) $statistics->total,
            'last_30_days' => (int) $statistics->last_30_days,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Clear Cache
    |--------------------------------------------------------------------------
    */

    public function clearUsersStatisticsCache(): void
    {
        Cache::forget(self::USERS_STATISTICS_CACHE_KEY);
    }

    public function clearProfilesStatisticsCache(): void
    {
        Cache::forget(self::PROFILES_STATISTICS_CACHE_KEY);
    }

    public function clearCompaniesStatisticsCache(): void
    {
        Cache::forget(self::COMPANIES_STATISTICS_CACHE_KEY);
    }

    public function clearTenantsStatisticsCache(): void
    {
        Cache::forget(self::TENANTS_STATISTICS_CACHE_KEY);
    }

    public function clearSubscriptionPlansStatisticsCache(): void
    {
        Cache::forget(self::SUBSCRIPTION_PLANS_STATISTICS_CACHE_KEY);
    }

    public function clearSubscriptionPricesStatisticsCache(): void
    {
        Cache::forget(self::SUBSCRIPTION_PRICES_STATISTICS_CACHE_KEY);
    }

    public function clearSubscriptionsStatisticsCache(): void
    {
        Cache::forget(self::SUBSCRIPTIONS_STATISTICS_CACHE_KEY);
    }

    public function clearPaymentsStatisticsCache(): void
    {
        Cache::forget(self::PAYMENTS_STATISTICS_CACHE_KEY);
    }

    public function clearRevenueStatisticsCache(): void
    {
        Cache::forget(self::REVENUE_STATISTICS_CACHE_KEY);
    }

    public function clearActivityStatisticsCache(): void
    {
        Cache::forget(self::ACTIVITY_STATISTICS_CACHE_KEY);
    }

    public function clearAllDashboardCaches(): void
    {
        Cache::forget(self::USERS_STATISTICS_CACHE_KEY);
        Cache::forget(self::PROFILES_STATISTICS_CACHE_KEY);
        Cache::forget(self::COMPANIES_STATISTICS_CACHE_KEY);
        Cache::forget(self::TENANTS_STATISTICS_CACHE_KEY);
        Cache::forget(self::SUBSCRIPTION_PLANS_STATISTICS_CACHE_KEY);
        Cache::forget(self::SUBSCRIPTION_PRICES_STATISTICS_CACHE_KEY);
        Cache::forget(self::SUBSCRIPTIONS_STATISTICS_CACHE_KEY);
        Cache::forget(self::PAYMENTS_STATISTICS_CACHE_KEY);
        Cache::forget(self::REVENUE_STATISTICS_CACHE_KEY);
        Cache::forget(self::ACTIVITY_STATISTICS_CACHE_KEY);
    }
}
