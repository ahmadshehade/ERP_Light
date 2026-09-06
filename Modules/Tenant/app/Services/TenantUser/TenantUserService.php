<?php

namespace Modules\Tenant\Services\TenantUser;

use App\Enums\NameOfCache;
use App\Traits\ApplyFilters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Enum\TenantRoles;
use Modules\Tenant\Models\TenantUser;

class TenantUserService
{
    use ApplyFilters;

    public const TIME_TTL = 30;

    public function __construct(public TenantUserNotificationService $notify) {}

    /**
     *Summary for genKey
     *  @prama array $data
     *@Prama String $prefix
     *@return string
     */
    protected function genKey(array $data = [], string $prefix = ''): string
    {
        $user = Auth::user();

        $userKey = $user
            ? $user->id . "_" . $prefix . "_" .
            implode("_", $user->roles->pluck('name')->toArray())
            : "";

        return $userKey .
            NameOfCache::TENANT_USER->value .
            "_" .
            md5(json_encode($data));
    }

    /**
     *Summary for flushCache
     *@return void
     */
    protected function flushCache(): void
    {
        Cache::tags(NameOfCache::TENANT_USER->value)->flush();
    }

    /**
     * Summary of getAll
     * @param array $data
     * @return array
     */
    public function getAll(array $data = []): array
    {
        $cacheKey = $this->genKey($data, "all_TenantUser_");

        return Cache::tags(NameOfCache::TENANT_USER->value)
            ->remember($cacheKey, self::TIME_TTL, function () use ($data) {
                $users = TenantUser::query()->with('user', 'departments', 'positions', 'teams');
                if (!empty($data)) {
                    $this->filterData($users, $data);
                }
                return $users
                    ->with('user')
                    ->paginate(15)
                    ->toArray();
            });
    }

    /**
     * Summary of get
     * @param TenantUser $tenantUser
     * @return TenantUser
     */
    public function get(TenantUser $tenantUser): TenantUser
    {
        return $tenantUser->load(['user', 'departments', 'positions', 'teams']);
    }

    /**
     * Summary of store
     * @param array $data
     * @return TenantUser
     */
    public function store(array $data): TenantUser
    {
        return DB::connection('tenant')->transaction(function () use ($data) {
            $departmentIds = $data['department_ids'] ?? [];
            $positionIds = $data['position_ids'] ?? [];
            $teamIds = $data['team_ids'] ?? [];
            unset($data['department_ids'], $data['team_ids'], $data['position_ids']);
            $tenantUser = TenantUser::create($data);
            if (!empty($departmentIds)) {
                $tenantUser->departments()->attach($departmentIds);
            }
            if (!empty($positionIds)) {
                $tenantUser->positions()->attach($positionIds);
            }
            if (!empty($teamIds)) {
                $tenantUser->teams()->attach($teamIds);
            }
            $tenantUser->assignRole(TenantRoles::Employee->value);
            DB::afterCommit(function () use ($tenantUser) {
                $this->flushCache();
                $this->notify->addUserToTenantNotify($tenantUser);
            });
            return $tenantUser->load(['departments', 'user', 'positions', 'teams']);
        });
    }

    /**
     * Summary of update
     * @param array $data
     * @param TenantUser $tenantUser
     * @return TenantUser
     */
    public function update(
        array $data,
        TenantUser $tenantUser
    ): TenantUser {
        return DB::connection('tenant')->transaction(function () use ($tenantUser, $data) {
            $departmentIds = $data['department_ids'] ?? [];
            $positionIds = $data['position_ids'] ?? [];
            $teamIds = $data['team_ids'] ?? [];
            unset($data['department_ids'], $data['position_ids'], $data['team_ids']);
            $tenantUser->update($data);
            $tenantUser->departments()->sync($departmentIds);
            $tenantUser->positions()->sync($positionIds);
            $tenantUser->teams()->sync($teamIds);
            DB::afterCommit(function () use ($tenantUser) {
                $this->flushCache();
                $this->notify->updateUserTenantNotify($tenantUser);
            });
            return $tenantUser
                ->fresh()
                ->load(['user', 'departments', 'positions', 'teams']);
        });
    }
    /**
     * Summary of destroy
     * @param TenantUser $tenantUser
     * @return bool
     *
     */
    public function destroy(TenantUser $tenantUser): bool
    {
        return DB::connection('tenant')->transaction(function () use ($tenantUser) {
            $data = [
                'tenant_user_id' => $tenantUser->id,
                'user' => $tenantUser->user,
                'name' => $tenantUser->user->name,
                'email' => $tenantUser->user->email,
            ];
            DB::afterCommit(function () use ($data) {
                $this->flushCache();
                $this->notify->removeUserFromTenantNotify($data);
            });
            $tenantUser->delete();
            return true;
        });
    }
}
