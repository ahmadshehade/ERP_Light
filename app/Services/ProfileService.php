<?php

namespace App\Services;

use App\Enums\NameOfCache;
use App\Enums\PermissionManagementPermissions;
use App\Jobs\ProcessProfileMediaJob;
use App\Models\Profile;
use App\Traits\ApplyFilters;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Arr;

class ProfileService
{
    protected const CACHE_TTL = 60;


    use  ApplyFilters;
    /**
     * Generate cache key.
     */
    private function generateCacheKey(
        array $data = [],
        int $page = 1,
        int $perPage = 15
    ): string {
        $user = Auth::user();

        $userKey = $user
            ? $user->id . '_' . implode(
                '_',
                $user->roles->pluck('name')->sort()->toArray()
            )
            : '';

        $cacheData = [
            'filters' => $data,
            'page' => $page,
            'per_page' => $perPage,
        ];

        return $userKey
            . '_'
            . NameOfCache::PROFILE->value
            . '_'
            . md5(json_encode($cacheData));
    }

    /**
     * Flush cache
     */
    private function flushCache(): void
    {
        Cache::tags([NameOfCache::PROFILE->value])->flush();
    }

    /**
     * Get all profiles
     */
    public function getAllProfiles(array $data = [])
    {
        $page = request()->integer('page', 1);
        $perPage = 15;

        $cacheKey = $this->generateCacheKey(
            $data,
            $page,
            $perPage
        );

        $cached = Cache::tags([
            NameOfCache::PROFILE->value
        ])->remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($data, $perPage) {

                $query = Profile::query()
                    ->with([
                        'user' => function ($query) {
                            $query->withTrashed();
                        }
                    ]);

                if (!empty($data)) {
                    $this->filterData($query, $data);
                }

                $this->sortData(
                    $query,
                    $data,
                    [
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

        $profiles = Profile::query()
            ->with([
                'user' => function ($query) {
                    $query->withTrashed();
                }
            ])
            ->whereIn('id', $cached['ids'])
            ->get()
            ->sortBy(
                fn($profile) => array_search(
                    $profile->id,
                    $cached['ids']
                )
            )
            ->values();

        return new LengthAwarePaginator(
            $profiles,
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
     * Get profile
     * @prama Profile $profile
     * @return Profile
     */
    public function getProfile(Profile $profile): Profile
    {
        if (Gate::allows(
            PermissionManagementPermissions::ViewProfileForDeleteUsers->value
        )) {
            return $profile->load([
                'user' => function ($query) {
                    $query->withTrashed();
                }
            ]);
        }

        return $profile->load('user');
    }
    /**
     * Get profile
     */

    /**
     * Update
     * @prama array $data
     * @prama Profile $profile
     * @return Profile
     */
    public function update(Profile $profile, array $data = []): Profile
    {
        return DB::transaction(function () use ($profile, $data) {
            $avatar = Arr::pull($data, 'avatar', []);
            $profile->update($data);
            $mediaPath = null;
            if ($avatar instanceof UploadedFile) {
                $mediaPath = $avatar->store('temp/profiles/' . $profile->id, 'local');
            }

            DB::afterCommit(function ()  use ($profile, $mediaPath) {
                $this->flushCache();
                if ($mediaPath) {
                    dispatch(new ProcessProfileMediaJob($profile->id, $mediaPath));
                }
            });
            return $profile->load('user');
        }, 5);
    }
}
