<?php

namespace Modules\Tenant\Services\Department;

use App\Enums\NameOfCache;
use App\Traits\ApplyFilters;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Modules\Tenant\Jobs\ProcessDepartmentMediaJob;
use Modules\Tenant\Models\Department;
use Modules\Tenant\Models\TenantUser;
use App\Exceptions\BusinessRuleException;
use Illuminate\Pagination\LengthAwarePaginator;

class DepartmentService
{
    public const TIME_TTL = 3600;

    use ApplyFilters;

    public function __construct(public DepartmentNotificationService $departmentService) {}

    private function genKey(array $data = [], string $prefix = '', int $page = 1, int $perPage = 15): string
    {
        $user = Auth::user();
        $userKey = $user ? $user->id . "_" . $prefix . implode("_", $user->roles->pluck('name')->toArray()) : "";
        $cacheData = [
            'filters' => $data,
            'page' => $page,
            'per_page' => $perPage
        ];
        return $userKey . "_" . NameOfCache::TENANT_DEPARTMENT->value . "_" . md5(json_encode($cacheData));
    }


    /**
     * Clear cache
     */
    private function clearCache(): void
    {
        Cache::tags([NameOfCache::TENANT_DEPARTMENT->value])->flush();
    }

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

        $cached = Cache::tags(NameOfCache::TENANT_DEPARTMENT->value)
            ->remember(
                $cacheKey,
                self::TIME_TTL,
                function () use ($data, $perPage) {

                    $departments = Department::active(Auth::user())
                        ->with(['media']);

                    if (!empty($data)) {
                        $this->filterData($departments, $data);
                    }

                    $this->sortData(
                        $departments,
                        $data,
                        ['name', 'created_at']
                    );

                    $paginator = $departments->paginate($perPage);

                    return [
                        'ids' => $paginator->getCollection()->pluck('id')->all(),
                        'total' => $paginator->total(),
                        'per_page' => $paginator->perPage(),
                        'current_page' => $paginator->currentPage(),
                    ];
                }
            );

        $departments = Department::active(Auth::user())
            ->with(['media'])
            ->whereIn('id', $cached['ids'])
            ->get()
            ->sortBy(
                fn($department) => array_search(
                    $department->id,
                    $cached['ids']
                )
            )
            ->values();

        return new LengthAwarePaginator(
            $departments,
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
     * @param int $departmentId
     * @return Department
     */
    public function get(int $departmentId): Department
    {
        $tenantUser = TenantUser::where('user_id', Auth::id())->firstOrFail();
        return Department::query()
            ->with('media')
            ->findOrFail($departmentId);
    }

    /**
     * Summary of store
     * @param array $data
     * @return Department
     */
    public function store(array $data): Department
    {
        return DB::connection('tenant')->transaction(function () use ($data) {
            $photo = Arr::pull($data, 'photo', []);
            $department = Department::create($data);
            $path = null;
            if ($photo instanceof UploadedFile) {
                $path = $photo->store('temp/department/' . $department->id, 'local');
            }
            DB::connection('tenant')->afterCommit(function () use ($department, $path) {
                $this->clearCache();
                if ($path !== null) {
                    dispatch(new ProcessDepartmentMediaJob($department->id, $path));
                }
                $this->departmentService->createDepartmentNotify($department);
            });
            return $department->load(['media']);
        });
    }

    /**
     * Summary of update
     * @param array $data
     * @param Department $department
     * @return Department
     */
    public function update(array $data, Department $department): Department
    {
        return DB::connection('tenant')->transaction(function () use ($data, $department) {
            $department = Department::query()->whereKey($department->id)
                ->lockForUpdate()
                ->first();
            if (!$department) {
                throw new BusinessRuleException('Department Not Found.', 404);
            }
            $photo = Arr::pull($data, 'photo', []);
            $department->update($data);
            $path = null;
            if ($photo instanceof UploadedFile) {
                $path = $photo->store('temp/department/' . $department->id, 'local');
            }
            DB::connection('tenant')->afterCommit(function () use ($department, $path) {
                $this->clearCache();
                if ($path !== null) {
                    dispatch(new ProcessDepartmentMediaJob($department->id, $path));
                }
                $this->departmentService->updatedDepartmentNotify($department);
            });
            return $department->load(['media']);
        });
    }

    /**
     * Summary of destroy
     * @param Department $department
     * @return bool
     */
    public function destroy(Department $department): bool
    {
        return DB::connection('tenant')->transaction(function () use ($department) {
            $data = $this->prepareData($department);
            $department->delete();
            DB::afterCommit(function () use ($data) {
                $this->departmentService->deleteDepartmentNotify($data);
                $this->clearCache();
            });
            return true;
        });
    }

    /**
     * Summary of restore
     * @param Department $department
     * @return Department
     */
    public function restore(Department $department): Department
    {
        return DB::connection('tenant')->transaction(function () use ($department) {
            if ($department->trashed()) {
                $department->restore();
                $this->clearCache();
                $data = [
                    'department_id' => $department->id,
                    'name_en' => $department->getTranslation('name', 'en'),
                    'name_ar' => $department->getTranslation('name', 'ar'),
                    'description_ar' => $department->getTranslation('description', 'ar'),
                    'description_en' => $department->getTranslation('description', 'en'),
                    'is_active' => $department->is_active

                ];
                DB::afterCommit(function () use ($data) {
                    $this->departmentService->restoreNotify($data);
                });
                return $department->load(['media']);
            }
            throw new BusinessRuleException('Department is not trashed', 404);
        });
    }

    /**
     * Summary of forceDelete
     * @param Department $department
     * @return bool
     */
    public function forceDelete(Department $department): bool
    {
        return DB::connection('tenant')->transaction(function () use ($department) {
            if ($department->trashed()) {
                $department->clearMediaCollection('department');
                $department->forceDelete();
                $this->clearCache();
                return true;
            }
            throw new BusinessRuleException('Department is not trashed', 404);
        });
    }

    /**
     * Summary of getTrashed
     * @param Department $department
     * @return Department
     */
    public function getTrashed(Department $department): Department
    {
        if (!$department->trashed()) {
            throw new BusinessRuleException('Department is not trashed', 404);
        }
        return $department->load(['media']);
    }

    /**
     * Summary of getAllTrahed
     * @param array $data
     * @return array
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

        $cached = Cache::tags(NameOfCache::TENANT_DEPARTMENT->value)
            ->remember(
                $cacheKey,
                self::TIME_TTL,
                function () use ($data, $perPage) {

                    $departments = Department::onlyTrashed()
                        ->with(['media']);

                    if (!empty($data)) {
                        $this->filterData($departments, $data);
                    }

                    $this->sortData(
                        $departments,
                        $data,
                        ['name', 'created_at']
                    );

                    $paginator = $departments->paginate($perPage);

                    return [
                        'ids' => $paginator->getCollection()->pluck('id')->all(),
                        'total' => $paginator->total(),
                        'per_page' => $paginator->perPage(),
                        'current_page' => $paginator->currentPage(),
                    ];
                }
            );

        $departments = Department::onlyTrashed()
            ->with(['media'])
            ->whereIn('id', $cached['ids'])
            ->get()
            ->sortBy(
                fn($department) => array_search(
                    $department->id,
                    $cached['ids']
                )
            )
            ->values();

        return new LengthAwarePaginator(
            $departments,
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
    public function restoreAll(): bool
    {
        return DB::connection('tenant')->transaction(function () {
            $count = Department::onlyTrashed()->restore();
            if ($count == 0) {
                throw new BusinessRuleException("No trashed departments to restore.", 404);
            }
            $this->clearCache();
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
            Department::onlyTrashed()
                ->select('id')
                ->chunkById(100, function ($departments) use (&$count) {
                    foreach ($departments as $department) {
                        $department->clearMediaCollection('department');
                        $department->forceDelete();

                        $count++;
                    }
                });
            if ($count === 0) {
                throw new BusinessRuleException(
                    'No trashed departments to delete.',
                    404
                );
            }
            $this->clearCache();
            return true;
        });
    }


    /**
     * Summary of prepareData
     * @prama Deartment $department
     * @return array
     */
    protected  function prepareData(Department $department): array
    {
        $data = [
            'department_id' => $department->id,
            'name_en' => $department->getTranslation('name', 'en'),
            'name_ar' => $department->getTranslation('name', 'ar'),
            'description_ar' => $department->getTranslation('description', 'ar'),
            'description_en' => $department->getTranslation('description', 'en'),
            'is_active' => $department->is_active

        ];
        return $data;
    }
}
