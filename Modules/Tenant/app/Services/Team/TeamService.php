<?php

namespace Modules\Tenant\Services\Team;

use App\Enums\NameOfCache;
use App\Traits\ApplyFilters;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
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
    protected function genKey(array $data = [], string $prefix = '', int $page = 1, int $perPage = 15)
    {
        $user = Auth::user();
        $userKey = $user ? $user->id . $prefix . tenant('id') . implode("_", $user->roles->pluck("name")->toArray()) : "";
        $cacheData = [
            'filters' => $data,
            'page' => $page,
            'per_page' => $perPage
        ];
        return  $userKey . "_" . NameOfCache::TEAM->value . "_" . md5(json_encode($cacheData));
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
        $page = request()->integer('page', 1);
        $perPage = request()->integer('per_page', 15);

        $cacheKey = $this->genKey(
            $data,
            'no_trashed',
            $page,
            $perPage
        );

        $cached = Cache::tags(NameOfCache::TEAM->value)
            ->remember(
                $cacheKey,
                self::TIME_TTL,
                function () use ($data, $perPage) {

                    $teams = Team::active(Auth::user())
                        ->with('media');

                    if (!empty($data)) {
                        $this->filterData($teams, $data);
                    }

                    $paginator = $teams->paginate($perPage);

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

        $teams = Team::active(Auth::user())
            ->with('media')
            ->whereIn('id', $cached['ids'])
            ->get()
            ->sortBy(
                fn($team) => array_search(
                    $team->id,
                    $cached['ids']
                )
            )
            ->values();

        return new LengthAwarePaginator(
            $teams,
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
     * Summary of get
     */
    public function get(Team $team)
    {
        return $team->active(Auth::user())->load('media');
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
