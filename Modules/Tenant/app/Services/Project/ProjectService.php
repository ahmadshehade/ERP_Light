<?php

namespace Modules\Tenant\Services\Project;

use App\Enums\NameOfCache;
use App\Traits\ApplyFilters;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Jobs\ProcessProjectMediaJob;
use Modules\Tenant\Models\Project;
use App\Exceptions\BusinessRuleException;
use Closure;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Modules\Tenant\Enum\ProjectStatus;
use Modules\Tenant\Enum\TaskStatus;
use Modules\Tenant\Events\ProjectTeams\AddProjectToTeamsEvent;
use Modules\Tenant\Events\ProjectTeams\RemoveProjectFromTeamsEvent;

class ProjectService
{
    use ApplyFilters;

    public const TIME_TTL = 60;

    public function __construct(public ProjectNotificationService $notify) {}


    /**
     * Summary of genKey
     * @param array $data
     * @return string
     */
    protected function genKey(array $data = [], string $prefix = '', int $page = 1, int $perPage = 15): string
    {
        $user = Auth::user();
        $userKey = $user ? $user->id . tenant('id') . $prefix . implode("_", $user->roles->pluck('name')->toArray()) : "";
        $cacheData = ['filters' => $data, 'page' => $page, 'per_page' => $perPage];
        return $userKey . NameOfCache::PROJECT->value . md5(json_encode($cacheData));
    }

    /**
     * Summary of flushCache
     * @return void
     */
    protected  function flushCache(): void
    {
        Cache::tags(NameOfCache::PROJECT->value)->flush();
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

        $cached = Cache::tags(NameOfCache::PROJECT->value)
            ->remember(
                $cacheKey,
                self::TIME_TTL,
                function () use ($data, $perPage) {

                    $projects = Project::active(Auth::user())
                        ->with('tasks', 'media');

                    if (!empty($data)) {
                        $this->filterData($projects, $data);
                    }

                    $this->sortData(
                        $projects,
                        $data,
                        [
                            'name',
                            'description',
                            'created_at',
                            'start_date',
                            'end_date',
                            'priority',
                            'status',
                        ]
                    );

                    $paginator = $projects->paginate($perPage);

                    return [
                        'ids' => $paginator->getCollection()->pluck('id')->all(),
                        'total' => $paginator->total(),
                        'per_page' => $paginator->perPage(),
                        'current_page' => $paginator->currentPage(),
                    ];
                }
            );

        $projects = Project::active(Auth::user())
            ->with('tasks', 'media')
            ->whereIn('id', $cached['ids'])
            ->get()
            ->sortBy(
                fn($project) => array_search(
                    $project->id,
                    $cached['ids']
                )
            )
            ->values();

        return new LengthAwarePaginator(
            $projects,
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
     *Summary of get
     *@param Project $project
     *@return Project
     */
    public function get(Project $project): Project
    {
        return $project->active(Auth::user())->load('media', 'tasks');
    }

    /**
     *Summary of store
     *@param array $data
     * @return Project
     */
    public function store(array $data): Project
    {
        return DB::connection('tenant')->transaction(function () use ($data) {
            $media = Arr::pull($data, 'media', []);
            $teamIds = Arr::pull($data, 'teamIds', []);
            $data['status'] = ProjectStatus::Planned->value;
            $project = Project::create($data);
            $mediaPaths = [];
            if ($media != []) {
                foreach ($media as $file) {
                    if ($file instanceof UploadedFile) {
                        $mediaPaths[] = $file->store(
                            'temp/projects/' . $project->id,
                            'local'
                        );
                    }
                }
            }
            $flag = false;
            if ($teamIds !== []) {
                $project->teams()->attach($teamIds);
                $flag = true;
            }
            DB::connection('tenant')->afterCommit(function () use ($project, $mediaPaths, $flag) {
                $this->flushCache();
                dispatch(new ProcessProjectMediaJob($project->id, $mediaPaths));
                if ($flag) {
                    event(new AddProjectToTeamsEvent(
                        $project->id,
                        $project->getTranslation('name', 'en'),
                        $project->getTranslation('name', 'ar'),
                    ));
                }
                $this->notify->makeNewProjectNotify($project);
            });
            return $project->load('media', 'tasks');
        });
    }

    /**
     *Summary of update
     *@param array $data
     *@param Project $project
     *@return Project
     */
    public function update(array $data, Project $project): Project
    {
        return DB::connection('tenant')->transaction(function () use ($project, $data) {
            $project = Project::query()
                ->whereKey($project->id)
                ->lockForUpdate()
                ->first();
            if (!$project) {
                throw new BusinessRuleException('Project Not Found.', 404);
            }
            $media = Arr::pull($data, 'media', []);
            $teamIds = Arr::pull($data, 'teamIds', []);
            $mediaPaths = [];
            if ($media != []) {
                foreach ($media as $file) {
                    if ($file instanceof UploadedFile) {
                        $mediaPaths[] = $file->store(
                            'temp/projects/' . $project->id,
                            'local'
                        );
                    }
                }
            }
            $addedTeamIds = [];
            $removedTeamIds = [];
            if ($teamIds !== []) {
                $oldTeamIds = $project->teams()->pluck('teams.id')->toArray();
                $project->teams()->sync($teamIds);
                $addedTeamIds = array_diff($teamIds, $oldTeamIds);
                $removedTeamIds = array_diff($oldTeamIds, $teamIds);
            }
            $project->update($data);
            DB::connection('tenant')->afterCommit(function () use ($project, $mediaPaths, $addedTeamIds, $removedTeamIds) {
                $this->flushCache();
                dispatch(new ProcessProjectMediaJob($project->id, $mediaPaths));
                if (!empty($addedTeamIds)) {
                    event(new AddProjectToTeamsEvent(
                        $project->id,
                        $project->getTranslation('name', 'en'),
                        $project->getTranslation('name', 'ar'),
                    ));
                }
                if (!empty($removedTeamIds)) {
                    event(new RemoveProjectFromTeamsEvent(
                        $project->id,
                        $removedTeamIds,
                        $project->getTranslation('name', 'en'),
                        $project->getTranslation('name', 'ar'),
                    ));
                }
                $this->notify->updateProjectNotify($project);
            });
            return $project->load('media', 'tasks');
        });
    }

    /**
     * Summary of destroy
     * @param Project $project
     * @return bool
     */
    public function destroy(Project $project): bool
    {
        return DB::connection('tenant')->transaction(function () use ($project) {
            $data = [
                'project_name_en' => $project->getTranslation('name', 'en'),
                'project_name_ar' => $project->getTranslation('name', 'ar'),
                'project_description_en' => $project->getTranslation('description', 'en'),
                'project_description_ar' => $project->getTranslation('description', 'ar'),
                'status' => $project->status,
                'priority' => $project->priority,
                'company_name_en' => tenant()->company->getTranslation('name', 'en'),
                'company_name_ar' => tenant()->company->getTranslation('name', 'ar'),
                'is_active' => $project->is_active

            ];
            $hasActiveTasks = $project->tasks()->whereIn('status', [
                TaskStatus::IN_PROGRESS->value,
                TaskStatus::OnHold->value
            ])->exists();

            if ($hasActiveTasks) {
                throw new BusinessRuleException('Cannot delete project with active tasks.', 409);
            }
            $project->tasks()->delete();
            $project->delete();
            DB::afterCommit(function () use ($data) {
                $this->flushCache();
                $this->notify->DeleteProjectNotify($data);
            });
            return true;
        });
    }

    /**
     * Summary of restore
     * @param Project $project
     * @return Project
     */
    public function restore(Project $project): Project
    {
        return DB::connection('tenant')->transaction(function () use ($project) {
            $project->onlyTrashed()->restore();
            $project->tasks()->onlyTrashed()->where(
                'status',
                '!=',
                TaskStatus::CANCELLED->value
            )->restore();
            DB::afterCommit(function () use ($project) {
                $this->flushCache();
                $this->notify->restoreProjectNotify($project);
            });
            return $project->load('media', 'tasks');
        });
    }

    /**
     * Summary of forceDelete
     * @param Project $project
     * @return bool
     */
    public function forceDelete(Project $project): bool
    {
        return DB::connection('tenant')->transaction(function () use ($project) {
            if ($project->trashed()) {
                $project->clearMediaCollection('project');
            }
            $project->onlyTrashed()->forceDelete();
            DB::afterCommit(function () use ($project) {
                $this->flushCache();
            });
            return true;
        });
    }

    /**
     * Summary of getAllTrashed
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

        $cached = Cache::tags(NameOfCache::PROJECT->value)
            ->remember(
                $cacheKey,
                self::TIME_TTL,
                function () use ($data, $perPage) {

                    $projects = Project::onlyTrashed()
                        ->with('media', 'tasks');

                    if (!empty($data)) {
                        $this->filterData($projects, $data);
                    }

                    $this->sortData(
                        $projects,
                        $data,
                        [
                            'name',
                            'description',
                            'created_at',
                            'start_date',
                            'end_date',
                            'priority',
                            'status',
                        ]
                    );

                    $paginator = $projects->paginate($perPage);

                    return [
                        'ids' => $paginator->getCollection()->pluck('id')->all(),
                        'total' => $paginator->total(),
                        'per_page' => $paginator->perPage(),
                        'current_page' => $paginator->currentPage(),
                    ];
                }
            );

        $projects = Project::onlyTrashed()
            ->with('media', 'tasks')
            ->whereIn('id', $cached['ids'])
            ->get()
            ->sortBy(
                fn($project) => array_search(
                    $project->id,
                    $cached['ids']
                )
            )
            ->values();

        return new LengthAwarePaginator(
            $projects,
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
     * Summary of getTrashed
     * @param Project $project
     * @return Project
     */
    public function getTrashed(Project $project): Project
    {
        if ($project->trashed()) {
            return $project->load('media', 'tasks');
        }
        throw new BusinessRuleException('Project is not trashed', 404);
    }

    /**
     * Summary of RestoreAll
     * @return bool
     */
    public function RestoreAll(): bool
    {
        return DB::connection('tenant')->transaction(function () {
            $count = Project::onlyTrashed()->count();
            if ($count === 0) {
                throw new BusinessRuleException("No trashed projects to restore.");
            }
            Project::select('id')->chunkById(100, function ($projects) {
                foreach ($projects as $project) {
                    $project->restore();
                    $project->tasks()->onlyTrashed()->where(
                        'status',
                        '!=',
                        TaskStatus::CANCELLED->value
                    )->restore();
                }
            });

            DB::connection('tenant')->afterCommit(function () {
                $this->flushCache();
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
            $count = Project::onlyTrashed()->count();
            if ($count === 0) {
                throw new BusinessRuleException("No trashed projects to force delete.", 404);
            }
            Project::select('id')->chunkById(100, function ($projects) {
                foreach ($projects as $project) {
                    $project->clearMediaCollection('project');
                    $project->forceDelete();
                }
            });

            DB::connection('tenant')->afterCommit(function () {
                $this->flushCache();
            });
            return true;
        });
    }
}
