<?php

namespace Modules\Central\Services;

use App\Enums\NameOfCache;
use App\Traits\ApplyFilters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Central\Models\SubscriptionPlan;
use Modules\Central\Services\SubscriptionPlans\SubscriptionPlanNotification;
use App\Exceptions\BusinessRuleException;

class SubscriptionPlanService
{
    use  ApplyFilters;

    public function __construct(public SubscriptionPlanNotification $planNotify) {}

    public const CACHE_TTL = 60;

    /**
     * Summary of genKey
     * @param array $data
     * @return string
     */
    public function genKey(
        array $data = [],
        string $prefix = "",
        int $page = 1,
        int $perPage = 15
    ): string {
        $user = Auth::user();
        $userKey = $user
            ? $user->id . "_" . $prefix . implode(
                "_",
                $user->roles->pluck('name')->toArray()
            )
            : '';
        $cacheData = [
            'filters' => $data,
            'page' => $page,
            'per_page' => $perPage,
        ];
        return $userKey
            . "_"
            . NameOfCache::SUBSCRIPTION_PLAN->value
            . "_"
            . md5(json_encode($cacheData));
    }

    /**
     * Summary of flushCache
     * @return void
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function flushCache(): void
    {
        Cache::tags(NameOfCache::SUBSCRIPTION_PLAN->value)->flush();
    }


    /**
     * Summary of getAllPlans
     * @param array $data
     *  @return array
     */
    public function getAllPlans(array $data = []): array
    {
        $page = request()->integer('page', 1);
        $perPage = request()->integer('per_page', 15);
        $cacheKey = $this->genKey(
            $data,
            "_no_trashed_",
            $page,
            $perPage
        );

        return Cache::tags(NameOfCache::SUBSCRIPTION_PLAN->value)
            ->remember(
                $cacheKey,
                self::CACHE_TTL,
                function () use ($data, $perPage) {
                    $plans = SubscriptionPlan::query()
                        ->active(Auth::user());
                    if (!empty($data)) {
                        $this->filterData($plans, $data);
                    }
                    $this->sortData(
                        $plans,
                        $data,
                        ['name', 'created_at']
                    );
                    return $plans
                        ->paginate($perPage)
                        ->toArray();
                }
            );
    }

    /**
     * Summary of get
     * @param SubscriptionPlan $subscriptionPlan
     * @return SubscriptionPlan
     */
    public function get(SubscriptionPlan $subscriptionPlan): SubscriptionPlan
    {
        return $subscriptionPlan;
    }

    /**
     * Summary of store
     * @param array $data
     *
     */
    public function store(array $data): SubscriptionPlan
    {
        return DB::transaction(function () use ($data) {
            $plan = SubscriptionPlan::create($data);

            DB::afterCommit(function () use ($plan) {
                $this->flushCache();
                if ($plan->is_active) {
                    $this->planNotify->activeNotification($plan);
                }
            });

            return $plan;
        });
    }

    /**
     * Summary of update
     * @param SubscriptionPlan $subscriptionPlan
     * @param array $data
     * @return SubscriptionPlan
     */
    public function update(SubscriptionPlan $subscriptionPlan, array $data): SubscriptionPlan
    {
        return DB::transaction(function () use ($subscriptionPlan, $data) {
            $subscriptionPlan->update($data);
            $subscriptionPlan->refresh();
            $wasChanged = $subscriptionPlan->wasChanged('is_active') && ! $subscriptionPlan->is_active;
            DB::afterCommit(function () use ($subscriptionPlan, $wasChanged) {
                $this->flushCache();
                if ($subscriptionPlan->is_active) {
                    $this->planNotify->updateNotification($subscriptionPlan);
                }
                if ($wasChanged) {
                    $this->planNotify->deActivePlanNotification($subscriptionPlan);
                }
            });
            return $subscriptionPlan;
        });
    }

    /**
     * Summary of delete
     * @param SubscriptionPlan $subscriptionPlan
     * @return bool
     */
    public function delete(SubscriptionPlan $subscriptionPlan): bool
    {
        return DB::transaction(function () use ($subscriptionPlan) {
            $subscriptionPlan->delete();
            $this->flushCache();
            return true;
        });
    }


    /**
     * Summary of restore
     * @param SubscriptionPlan $subscriptionPlan
     * @return SubscriptionPlan
     */
    public function restore(SubscriptionPlan $subscriptionPlan): SubscriptionPlan
    {
        return DB::transaction(function () use ($subscriptionPlan) {
            if (!$subscriptionPlan->trashed()) {
                throw new BusinessRuleException('The Plan Not Trashed Yeat !', 403);
            }
            $subscriptionPlan->restore();
            $this->flushCache();
            return $subscriptionPlan;
        });
    }

    /**
     * Summary of forceDelete
     * @param SubscriptionPlan $subscriptionPlan
     * @return bool
     */
    public function forceDelete(SubscriptionPlan $subscriptionPlan): bool
    {
        return DB::transaction(function () use ($subscriptionPlan) {
            if (!$subscriptionPlan->trashed()) {
                throw new BusinessRuleException('The Plan Not Trashed Yeat !', 403);
            }
            $subscriptionPlan->forceDelete();
            $this->flushCache();
            return true;
        });
    }

    /**
     * Summary of restoreAll
     * @return bool
     */
    public function restoreAll(): bool
    {
        return DB::transaction(function () {
            $trashed = SubscriptionPlan::onlyTrashed();
            if (!$trashed->exists()) {
                throw new BusinessRuleException("No trashed plans to restore.", 403);
            }
            $trashed->restore();
            $this->flushCache();
            return true;
        });
    }

    /**
     * Summary of forceDeleteAll
     * @return bool
     * @throws \Exception
     */
    public function forceDeleteAll(): bool
    {
        return DB::transaction(function () {
            $trashed = SubscriptionPlan::onlyTrashed();
            if (!$trashed->exists()) {
                throw new BusinessRuleException("No trashed plans to force delete.", 403);
            }
            $trashed->forceDelete();
            $this->flushCache();
            return true;
        });
    }

    /**
     * Summary of viewTrashedPlans
     * @param array $data
     * @return array
     */
    public  function viewTrashedPlans(array $data = []): array
    {

        $page = request()->integer('page', 1);
        $perPage = request()->integer('per_page', 15);
        $cacheKey = $this->genKey(
            $data,
            "_no_trashed_",
            $page,
            $perPage
        );
        return Cache::tags(NameOfCache::SUBSCRIPTION_PLAN->value)->remember($cacheKey, self::CACHE_TTL, function () use ($data) {
            $plans = SubscriptionPlan::query()->onlyTrashed()->active(Auth::user());
            if (! empty($data)) {
                $this->filterData($plans, $data);
            }
            $this->sortData($plans, $data, ['name', 'created_at']);
            return $plans->paginate(15)->toArray();
        });
    }

    /**
     * Summary of viewTrashedPlan
     * @param SubscriptionPlan $subscriptionPlan
     * @return SubscriptionPlan
     */
    public function viewTrashedPlan(SubscriptionPlan $subscriptionPlan): SubscriptionPlan
    {
        if (!$subscriptionPlan->trashed()) {
            throw new BusinessRuleException('The Plan Not Trashed Yeat !', 403);
        }

        return $subscriptionPlan;
    }
}
