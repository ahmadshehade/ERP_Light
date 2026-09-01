<?php

namespace App\Services;

use App\Enums\NameOfCache;
use App\Models\User;
use App\Traits\ApplyFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    use ApplyFilters;

    private const CACHE_TTL = 3600;

    /**
     * Generate cache key.
     */
    private function genKey(User $user, array $filters = [], string $prefix = ''): string
    {
        ksort($filters);
        $userKey = $user->id . "_" . implode("_", $user->roles->pluck('name')->sort()->toArray()) . "_" . md5(json_encode($filters));
        $cacheKey = $userKey . "_" . NameOfCache::USER->value . "_" . $prefix . "_" . md5(json_encode($filters));
        return $cacheKey;
    }
    /**
     * Get all users.
     */
    public function getAllUsers(array $filters = []): array
    {
        $user = Auth::user();
        $cacheKey = $this->genKey($user, $filters, 'all_users');
        return Cache::tags([NameOfCache::USER->value])
            ->remember($cacheKey, self::CACHE_TTL, function () use ($filters) {

                $query = User::query();
                if (!empty($filters)) {
                    $this->filterData($query, $filters);
                }
                return $query->get()->toArray();
            });
    }

    /**
     * Get user.
     */
    public function getUser(User $user): User
    {
        return $user->load([
            'profile',
            'roles',
        ]);
    }

    /**
     * Update user.
     */
    public function updateUser(User $user, array $data): User
    {
        DB::transaction(function () use ($user, $data) {
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }
            $user->update($data);
        });
        $this->clearUserCache();
        return $user->fresh([
            'profile',
            'roles',
        ]);
    }

    /**
     * Soft delete user.
     */
    public function deleteUser(User $user): bool
    {
        DB::transaction(function () use ($user) {
            $user->delete();
        });
        $this->clearUserCache();
        return true;
    }

    /**
     * Force delete user.
     */
    public function forceDelete(User $user): bool
    {
        DB::transaction(function () use ($user) {
            if ($user->trashed()) {
                $user->forceDelete();
            } else {
                throw new HttpClientException('User is not trashed', 400);
            }
        });
        $this->clearUserCache();
        return true;
    }

    /**
     * Restore user.
     */
    public function restore(User $user): bool
    {
        DB::transaction(function () use ($user) {
            if ($user->trashed()) {
                $user->restore();
            } else {
                throw new HttpClientException('User is not trashed', 400);
            }
        });
        $this->clearUserCache();
        return true;
    }

    /**
     * Get trashed users.
     */
    public function trashedUsers(array $filters = []): array
    {
        $user = Auth::user();
        $cacheKey = $this->genKey($user, $filters, 'trashed_users');
        return Cache::tags([NameOfCache::USER->value])
            ->remember($cacheKey, self::CACHE_TTL, function () use ($filters) {

                $query = User::onlyTrashed()
                    ->with([
                        'profile',
                        'roles',
                    ]);
                if (! empty($filters)) {
                    $this->filterData($query, $filters);
                }
                return $query->get()->toArray();
            });
    }

    /**
     * Restore all users.
     */
    public function restoreAll(): bool
    {
        DB::transaction(function () {
            if (User::onlyTrashed()->count() > 0) {
                User::onlyTrashed()->restore();
            } else {
                throw new HttpClientException('No trashed users found.', 400);
            }
        });
        $this->clearUserCache();
        return true;
    }

    /**
     * Empty trash.
     */
    public function emptyTrash(): bool
    {
        DB::transaction(function () {
            $users = User::onlyTrashed()->get();
            if ($users->count() > 0) {
                $users->forceDelete();
            } else {
                throw new HttpClientException('No trashed users found.');
            }
        });
        $this->clearUserCache();
        return true;
    }

    /**
     * Clear cache.
     */
    private function clearUserCache(): void
    {
        Cache::tags([NameOfCache::USER->value])->flush();
    }
}
