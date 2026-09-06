<?php

namespace App\Services;

use App\Enums\NameOfCache;
use App\Enums\PermissionManagementPermissions;
use App\Jobs\ProcessProfileMediaJob;
use App\Models\Profile;
use App\Traits\ApplyFilters;
use Illuminate\Http\UploadedFile;
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
    private function generateCacheKey(array $data = []): string
    {
        $user = Auth::user();
        $userKey = $user ? $user->id . "_" . implode("_", $user->roles->pluck('name')->sort()->toArray()) : "";
        $cacheKey = $userKey . "_" . NameOfCache::PROFILE->value . "_" . md5(json_encode($data));
        return $cacheKey;
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
    public function getAllProfiles(array $data = []): array
    {
        $cacheKey = $this->generateCacheKey($data);
        return Cache::tags([NameOfCache::PROFILE->value])
            ->remember($cacheKey, self::CACHE_TTL, function () use ($data) {
                $query = Profile::query()->with([
                    'user' => function ($query) {
                        $query->withTrashed();
                    }
                ]);
                if (!empty($data)) {
                    $this->filterData($query, $data);
                }
                return $query->paginate(15)->toArray();
            });
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
