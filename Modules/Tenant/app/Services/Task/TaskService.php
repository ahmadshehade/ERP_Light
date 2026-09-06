<?php

namespace Modules\Tenant\Services\Task;

use App\Enums\NameOfCache;
use App\Traits\ApplyFilters;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Models\Task;
use RuntimeException;
use Illuminate\Support\Arr;
use Modules\Tenant\Enum\ProjectStatus;
use Modules\Tenant\Jobs\ProcessTaskMediJob;
use Modules\Tenant\Models\Project;

class TaskService
{
    use ApplyFilters;

    public const TIME_TTL = 60;


    public function __construct(public  TaskNotificationService $notify) {}
    /**
     * Summary of genKey
     * @param array $data
     * @param string $prefix
     * @return string
     */
    protected function genKey(array $data = [], string $prefix = ''): string
    {
        return implode('_', [
            tenant('id'),
            Auth::id(),
            $prefix,
            NameOfCache::TASK->value,
            md5(json_encode($data)),
        ]);
    }

    /**
     *Summary of flushCache
     *@return void
     */
    private function flushCache()
    {
        Cache::tags(NameOfCache::TASK->value)->flush();
    }

    /**
     * Summary of getAll
     * @param array $data
     * @return array
     */
    public function getAll(array $data = []): array
    {
        $cacheKey = $this->genKey($data, 'no_trashed');
        return Cache::tags(NameOfCache::TASK->value)->remember($cacheKey, self::TIME_TTL, function () use ($data) {
            $tasks = Task::active(Auth::user());
            if (!empty($data)) {
                $this->filterData($tasks, $data);
            }
            return $tasks->paginate(15)->toArray();
        });
    }

    /**
     * Summary of get
     * @param Task $task
     * @return Task
     */
    public  function get(Task $task): Task
    {
        return $task->active(Auth::user())->load('project', 'team.tenantUsers.user');
    }

    /**
     * Summary of store
     * @param array $data
     * @return Task
     */
    public function store(array $data): Task
    {
        return DB::connection('tenant')->transaction(function () use ($data) {
            $this->checkProjectStatus($data['project_id']);
            $media = Arr::pull($data, 'media', []);
            if (isset($data['status'])) {
                unset($data['status']);
            }
            $task = Task::create($data);
            $mediaPaths = [];
            if ($media != []) {
                foreach ($media as $file) {
                    if ($file instanceof UploadedFile) {
                        $mediaPaths[] = $file->store(
                            'temp/task/' . $task->id,
                            'local'
                        );
                    }
                }
            }
            DB::connection('tenant')->afterCommit(function () use ($task, $mediaPaths) {
                $this->flushCache();
                $this->notify->addNewTaskNotify($task);
                if (!empty($mediaPaths)) {
                    dispatch(new ProcessTaskMediJob($task->id, $mediaPaths));
                }
            });
            return $task->load('project', 'team.tenantUsers.user');
        });
    }

    /**
     *Summary of update
     *@param array $data
     *@param Task $task
     *@return Task
     */
    public  function update(array $data, Task $task): Task
    {
        return DB::connection('tenant')->transaction(function () use ($data, $task) {

            $this->checkProjectStatus($task->project_id);
            $task = Task::query()
                ->whereKey($task->id)
                ->lockForUpdate()
                ->first();

            if (!$task) {
                throw new RuntimeException('Task Not Found.');
            }
            $media = Arr::pull($data, 'media', []);
            if (isset($data['status'])) {
                unset($data['status']);
            }
            $task->update($data);
            $mediaPaths = [];
            if ($media != []) {
                foreach ($media as $file) {
                    if ($file instanceof UploadedFile) {
                        $mediaPaths[] = $file->store(
                            'temp/task/' . $task->id,
                            'local'
                        );
                    }
                }
            }
            DB::connection('tenant')->afterCommit(function () use ($task, $mediaPaths) {
                $this->flushCache();
                $this->notify->updateTaskNotify($task);
                if (!empty($mediaPaths)) {
                    dispatch(new ProcessTaskMediJob($task->id, $mediaPaths));
                }
            });
            return $task->load('project', 'team.tenantUsers.user');
        });
    }

    /**
     * Summary of destroy
     * @param Task $task
     *@return bool
     */
    public function destroy(Task $task): bool
    {
        return  DB::connection('tenant')->transaction(function () use ($task) {
            $data = [
                'task' => $task,
                'title_en' => $task->getTranslation('title', 'en'),
                'title_ar' => $task->getTranslation('title', 'ar'),
                'task_id' => $task->id,
                'copmany_name_en' => tenant()->company->getTranslation('name', 'en'),
                'copmany_name_ar' => tenant()->company->getTranslation('name', 'ar')
            ];
            $task->delete();
            DB::connection('tenant')->afterCommit(function () use ($data) {
                $this->flushCache();
                $this->notify->removeTaskNotify($data);
            });
            return true;
        });
    }

    /**
     * Summary of restore
     * @param Task $task
     * @return Task
     */
    public function restore(Task $task): Task
    {
        return DB::connection('tenant')->transaction(function () use ($task) {
            if (!$task->trashed()) {
                throw new RuntimeException('Task NoT Trashed.');
            }
            $task->onlyTrashed()->restore();
            DB::connection('tenant')->afterCommit(function () use ($task) {
                $this->flushCache();
                $this->notify->restoreNotify($task);
            });
            return $task;
        });
    }

    /**
     * Summary of forceDelete
     * @param Task $task
     * @return bool
     */
    public function forceDelete(Task $task): bool
    {
        return DB::connection('tenant')->transaction(function () use ($task) {
            if (!$task->trashed()) {
                throw new RuntimeException('Task Not Trashed.');
            }
            $task->forceDelete();
            DB::connection('tenant')->afterCommit(function () {
                $this->flushCache();
            });
            return true;
        });
    }

    /**
     * Summary of getTrashed
     * @param Task $task
     * @return Task
     */
    public function getTrashed(Task $task): Task
    {
        if (!$task->trashed()) {
            throw new RuntimeException("Task Not Trashed");
        }
        return $task;
    }

    /**
     *Summary of getAllTashed
     *@param array $data
     *@return array
     */
    public function getAllTrashed(array $data = []): array
    {
        $caheKey = $this->genKey($data, 'trashed');
        return Cache::tags(NameOfCache::TASK->value)->remember($caheKey, self::TIME_TTL, function () use ($data) {
            $count = Task::withTrashed()->count();
            if ($count == 0) {
                throw new RuntimeException("No trashed tasks.");
            }
            $trashedTask = Task::onlyTrashed();
            if (!empty($data)) {
                $this->filterData($trashedTask, $data);
            }
            return $trashedTask->paginate(15)->toArray();
        });
    }

    /**
     * Summary of restoreAll
     * @return bool
     */
    public function restoreAll(): bool
    {
        return DB::connection('tenant')->transaction(function () {
            $count = Task::onlyTrashed()->count();
            if ($count === 0) {
                throw new RuntimeException("No trashed tasks to restore.");
            }
            Task::onlyTrashed()->select('id')
                ->chunkById(100, function ($tasks) {
                    foreach ($tasks as $task) {
                        $task->restore();
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
            $count = Task::onlyTrashed()->count();
            if ($count == 0) {
                throw new RuntimeException('Not Found Trashed Tasks.');
            }
            Task::onlyTrashed()->select('id')
                ->chunkById(100, function ($tasks) {
                    foreach ($tasks as $task) {
                        $task->forceDelete();
                    }
                });

            DB::connection('tenant')->afterCommit(function () {
                $this->flushCache();
            });
            return true;
        });
    }



    /**
     * Summary of checkProjectStatus
     * @param int $projectId
     * @return bool
     * @throws RuntimeException
     */
    private function checkProjectStatus(int $projectId): bool
    {
        $project = Project::query()
            ->whereKey($projectId)
            ->lockForUpdate()
            ->first();
        if (!$project) {
            throw new RuntimeException('Project Not Found.');
        }
        if (
            $project->status == ProjectStatus::Completed
            || $project->status == ProjectStatus::Cancelled
        ) {
            throw new RuntimeException('Project Is Completed Or Cancelled.');
        }
        return true;
    }
}
