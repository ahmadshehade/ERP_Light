<?php

namespace Modules\Tenant\Services\Position;

use App\Enums\NameOfCache;
use App\Traits\ApplyFilters;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Models\Position;
use App\Exceptions\BusinessRuleException;
use Illuminate\Pagination\LengthAwarePaginator;

class PositionService
{
    use ApplyFilters;

    const TIME_TTL = 3600;

    public function __construct(public PositionNotificationService $notify) {}

    /**
     * Summary of genKey
     * @param array $data
     * @param string $prefix
     * @return string
     */
    protected  function genKey(array $data = [], string $prefix = '', int $page = 1, int $perPage = 15): string
    {
        $user = Auth::user();
        $userKey = $user ? $user->id . tenant('id') . $prefix . implode("_", $user->roles->pluck('name')->toArray()) : "";
        $cacheData = ['filters' => $data, 'page' => $page, 'per_page' => $perPage];

        return $userKey . "_" . NameOfCache::POSITION->value . "_" . md5(json_encode($cacheData));
    }


    /**
     * Clear cache
     * @return void
     */
    protected function fulshCahe(): void
    {
        Cache::tags([NameOfCache::POSITION->value])->flush();
    }

    /**
     * Summary of getAll
     * @param array $data
     * @return array
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

        $cached = Cache::tags(NameOfCache::POSITION->value)
            ->remember(
                $cacheKey,
                self::TIME_TTL,
                function () use ($data, $perPage) {

                    $positions = Position::active(Auth::user());

                    if (!empty($data)) {
                        $this->filterData($positions, $data);
                    }

                    $this->sortData(
                        $positions,
                        $data,
                        ['name', 'created_at']
                    );

                    $paginator = $positions->paginate($perPage);

                    return [
                        'ids' => $paginator->getCollection()->pluck('id')->all(),
                        'total' => $paginator->total(),
                        'per_page' => $paginator->perPage(),
                        'current_page' => $paginator->currentPage(),
                    ];
                }
            );

        $positions = Position::active(Auth::user())
            ->whereIn('id', $cached['ids'])
            ->get()
            ->sortBy(
                fn($position) => array_search(
                    $position->id,
                    $cached['ids']
                )
            )
            ->values();

        return new LengthAwarePaginator(
            $positions,
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
     * Summary of store
     * @param array $data
     * @return Position
     */
    public function store(array $data): Position
    {
        return DB::connection('tenant')->transaction(function () use ($data) {
            $position = Position::create($data);
            DB::connection('tenant')->afterCommit(function () use ($position) {
                $this->fulshCahe();
                $this->notify->createNewPositionNotify($position);
            });
            return $position;
        });
    }

    /**
     * Summary of get
     * @param Position $position
     * @return Position
     */
    public function get(Position $position): Position
    {
        return $position->active(Auth::user());
    }

    public  function update(Position $position, array $data): Position
    {
        return DB::connection('tenant')->transaction(function () use ($position, $data) {
            $position = Position::query()
                ->whereKey($position->id)
                ->lockForUpdate()
                ->first();
            if (!$position) {
                throw new BusinessRuleException('Position Not Found.', 404);
            }
            $position->update($data);
            DB::connection('tenant')->afterCommit(function () use ($position) {
                $this->fulshCahe();
                $this->notify->updatePositionNotify($position);
            });
            return $position;
        });
    }


    /**
     * Summary of destroy
     * @param Position $position
     * @return bool
     */
    public function destroy(Position $position): bool
    {
        return DB::connection('tenant')->transaction(function () use ($position) {
            $company = tenant()->company;
            $data = [
                'position_id' => $position->id,
                'position_name_en' => $position->getTranslation('name', 'en'),
                'position_name_ar' => $position->getTranslation('name', 'ar'),
                'position_description_en' => $position->getTranslation('description', 'en'),
                'position_description_ar' => $position->getTranslation('description', 'ar'),
                'is_active' => $position->is_active,
                'company_name_en' => $company->getTranslation('name', 'en'),
                'company_name_ar' => $company->getTranslation('name', 'ar'),
                'uuid' => $position->uuid
            ];
            $position->delete();
            DB::connection('tenant')->afterCommit(function () use ($data) {
                $this->fulshCahe();
                $this->notify->deletePositionNotify($data);
            });
            return true;
        });
    }


    /**
     * Summary of restore
     */
    public function restore(Position $position): Position
    {
        return DB::connection('tenant')->transaction(function () use ($position) {
            if (!$position->trashed()) {
                throw  new BusinessRuleException('Position is not trashed', 404);
            }
            $position->restore();
            DB::connection('tenant')->afterCommit(function () use ($position) {
                $this->fulshCahe();
                $this->notify->restoreNotify($position);
            });
            return $position;
        });
    }

    /**
     * Summary of forceDelete
     * @param Position $position
     * @return bool
     */
    public function forceDelete(Position $position): bool
    {
        return DB::connection('tenant')->transaction(function () use ($position) {
            if (!$position->trashed()) {
                throw  new BusinessRuleException('Position is not trashed', 404);
            }
            $position->forceDelete();
            DB::connection('tenant')->afterCommit(function () {
                $this->fulshCahe();
            });
            return true;
        });
    }

    /**
     * Summary of getTrashedPositon
     * @prama Postion $positon
     * @return Position
     */
    public function getTrashedPositon(Position $position): Position
    {
        if (!$position->trashed()) {
            throw new BusinessRuleException('Position is not trashed', 404);
        }
        return $position;
    }

    /**
     * Summary of getAllTrashed
     * @prama array $data
     * @return array
     *
     */
    public function getAllTrashed(array $data = [])
    {
        $page = request()->integer('page', 1);
        $perPage = request()->integer('per_page', 15);

        $cacheKey = $this->genKey(
            $data,
            'trashed',
            $page,
            $perPage
        );

        $cached = Cache::tags(NameOfCache::POSITION->value)
            ->remember(
                $cacheKey,
                self::TIME_TTL,
                function () use ($data, $perPage) {

                    $positions = Position::onlyTrashed();

                    if (!empty($data)) {
                        $this->filterData($positions, $data);
                    }

                    $this->sortData(
                        $positions,
                        $data,
                        ['name', 'created_at']
                    );

                    $paginator = $positions->paginate($perPage);

                    return [
                        'ids' => $paginator->getCollection()->pluck('id')->all(),
                        'total' => $paginator->total(),
                        'per_page' => $paginator->perPage(),
                        'current_page' => $paginator->currentPage(),
                    ];
                }
            );

        $positions = Position::onlyTrashed()
            ->whereIn('id', $cached['ids'])
            ->get()
            ->sortBy(
                fn($position) => array_search(
                    $position->id,
                    $cached['ids']
                )
            )
            ->values();

        return new LengthAwarePaginator(
            $positions,
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
     * Summary of restoreAll
     * @return bool
     */
    public function  restoreAll(): bool
    {
        return DB::connection('tenant')->transaction(function () {
            $count = Position::onlyTrashed()->restore();
            if ($count == 0) {
                throw new BusinessRuleException("No trashed positions to restore.", 404);
            }
            DB::connection('tenant')->afterCommit(function () {
                $this->fulshCahe();
            });
            return true;
        });
    }


    /**
     * Summary of forceDeleteAll
     * @return bool
     */
    public function forceDeleteAll(): bool
    {
        return DB::connection('tenant')->transaction(function () {
            $count = 0;
            Position::onlyTrashed()
                ->select('id')->chunkById(100, function ($positions) use (&$count) {
                    foreach ($positions as $position) {
                        $position->forceDelete();
                        $count++;
                    }
                });
            if ($count == 0) {
                throw new BusinessRuleException('No trashed positions to delete.', 404);
            }
            DB::connection('tenant')->afterCommit(function () {
                $this->fulshCahe();
            });
            return true;
        });
    }
}
