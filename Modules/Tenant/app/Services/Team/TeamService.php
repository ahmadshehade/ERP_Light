<?php

namespace Modules\Tenant\Services\Team;

use App\Enums\NameOfCache;
use App\Traits\ApplyFilters;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Models\Team;
use Illuminate\Support\Arr;

class TeamService
{
    use ApplyFilters;

    public const TIME_TTL = 60;


    public function __construct(public TeamNotificationService $notify) {}


    /**
     * Summary of genKey
     * @param array $data
     * @param string $prefix
     * @return string
     */
    protected function genKey(array $data = [], string $prefix = '')
    {

        return implode('_', [
            tenant('id'),
            Auth::id(),
            $prefix,
            NameOfCache::TEAM->value,
            md5(json_encode($data)),
        ]);
    }

    /**
     * Summary of flushCache
     */
    protected function flushCache(): void
    {
        Cache::tags(NameOfCache::TEAM->value)->flush();
    }

    /**
     * Summary of getALl
     */
    public function getAll(array $data = [])
    {
        $cacheKey = $this->genKey($data, 'no_trashed');
        return Cache::tags(NameOfCache::TEAM->value)->remember($cacheKey, self::TIME_TTL, function () use ($data) {
            $teams = Team::active(Auth::user())->with('media');
            if (!empty($data)) {
                $this->filterData($teams, $data);
            }
            return $teams->get()->toArray();
        });
    }

    /**
     * Summary of get
     */
    public function get(Team $team)
    {
        return $team;
    }

    /**
     * Summary of store
     */
    public function store(array $data): Team
    {
        return DB::connection('tenant')->transaction(function () use ($data) {

            $photo = Arr::pull($data, 'photo');
            $team = Team::create($data);
            if ($photo instanceof UploadedFile) {
                $team->addMedia($photo)
                    ->toMediaCollection('team');
            }
            DB::connection('tenant')->afterCommit(function () use ($team) {
                $this->flushCache();

                $this->notify->createNewTeamNotify($team);
            });
            return $team->load('media');
        });
    }

    /**
     * Summary of update
     */
    public function update(array $data, Team $team): Team
    {
        return DB::connection('tenant')->transaction(function () use ($team, $data) {

            $photo = Arr::pull($data, 'photo');
            $team->update($data);
            if ($photo instanceof UploadedFile) {
                $team->clearMediaCollection('team');

                $team->addMedia($photo)
                    ->toMediaCollection('team');
            }
            DB::connection('tenant')->afterCommit(function () use ($team) {
                $this->flushCache();
                $this->notify->updateTeamNotify($team);
            });

            return $team->load('media');
        });
    }

    /**
     * Summary of destroy
     */
    public function destroy(Team $team): bool
    {
        return DB::connection('tenant')->transaction(function () use ($team) {

            $data = [
                'name_en' => $team->getTranslation('name', 'en'),
                'name_ar' => $team->getTranslation('name', 'ar'),
                'description_en' => $team->getTranslation('description', 'en'),
                'description_ar' => $team->getTranslation('description', 'ar'),
                'is_active' => $team->is_active,
            ];
            $team->clearMediaCollection('team');
            $team->delete();
            DB::connection('tenant')->afterCommit(function () use ($data) {
                $this->flushCache();

                $this->notify->removeTeamNotify($data);
            });
            return true;
        });
    }
}
