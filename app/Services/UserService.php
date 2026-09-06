<?php

namespace App\Services;

use App\Enums\NameOfCache;
use App\Models\User;
use App\Traits\ApplyFilters;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Pagination\LengthAwarePaginator;
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
    private function genKey(
        User $user,
        array $filters = [],
        string $prefix = '',
        int $page = 1,
        int $perPage = 15
    ): string {
        ksort($filters);

        $roles = $user->roles
            ->pluck('name')
            ->sort()
            ->implode('_');

        $cacheData = [
            'filters' => $filters,
            'page' => $page,
            'per_page' => $perPage,
        ];

        return $user->id
            . '_'
            . $roles
            . '_'
            . NameOfCache::USER->value
            . '_'
            . $prefix
            . '_'
            . md5(json_encode($cacheData));
    }
    /**
     * Get all users.
     */
    public function getAllUsers(array $filters = [])
    {
        $user = Auth::user();

        $page = request()->integer('page', 1);
        $perPage = 15;

        $cacheKey = $this->genKey(
            $user,
            $filters,
            'all_users',
            $page,
            $perPage
        );

        $cached = Cache::tags([
            NameOfCache::USER->value
        ])->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($filters, $perPage) {

                $query = User::query();

                if (!empty($filters)) {
                    $this->filterData($query, $filters);
                }

                $this->sortData(
                    $query,
                    $filters,
                    [
                        'name',
                        'created_at',
                        'updated_at',
                    ]
                );

                $paginator = $query->paginate($perPage);

                return [
                    'ids' => $paginator
                        ->getCollection()
                        ->pluck('id')
                        ->all(),

                    'total' => $paginator->total(),

                    'per_page' => $paginator->perPage(),

                    'current_page' => $paginator->currentPage(),
                ];
            }
        );

        $users = User::query()
            ->whereIn('id', $cached['ids'])
            ->get()
            ->sortBy(
                fn($user) => array_search(
                    $user->id,
                    $cached['ids']
                )
            )
            ->values();

        return new LengthAwarePaginator(
            $users,
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
    public function trashedUsers(array $filters = [])
    {
        $user = Auth::user();

        $page = request()->integer('page', 1);
        $perPage = 15;

        $cacheKey = $this->genKey(
            $user,
            $filters,
            'trashed_users',
            $page,
            $perPage
        );

        $cached = Cache::tags([
            NameOfCache::USER->value
        ])->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($filters, $perPage) {

                $query = User::onlyTrashed()
                    ->with([
                        'profile',
                        'roles',
                    ]);

                if (!empty($filters)) {
                    $this->filterData($query, $filters);
                }

                $this->sortData(
                    $query,
                    $filters,
                    [
                        'name',
                        'created_at',
                        'updated_at',
                    ]
                );

                $paginator = $query->paginate($perPage);

                return [
                    'ids' => $paginator
                        ->getCollection()
                        ->pluck('id')
                        ->all(),

                    'total' => $paginator->total(),

                    'per_page' => $paginator->perPage(),

                    'current_page' => $paginator->currentPage(),
                ];
            }
        );

        $users = User::onlyTrashed()
            ->with([
                'profile',
                'roles',
            ])
            ->whereIn('id', $cached['ids'])
            ->get()
            ->sortBy(
                fn($user) => array_search(
                    $user->id,
                    $cached['ids']
                )
            )
            ->values();

        return new LengthAwarePaginator(
            $users,
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
