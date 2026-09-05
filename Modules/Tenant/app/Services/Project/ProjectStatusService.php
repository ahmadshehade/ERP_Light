<?php

namespace Modules\Tenant\Services\Project;

use App\Enums\NameOfCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Enum\ProjectStatus;
use Modules\Tenant\Enum\TaskStatus;
use Modules\Tenant\Models\Project;
use App\Exceptions\BusinessRuleException;

class ProjectStatusService
{

    public function __construct(public ProjectNotificationService $notify) {}
    protected function flushCache(): void
    {
        Cache::tags(NameOfCache::PROJECT->value)->flush();
    }

    /**
     * Summary of start
     * @param Project $project
     * @return Project
     */
    public function start(Project $project): Project
    {
        return DB::connection('tenant')->transaction(function () use ($project) {
            if ($project->status !== ProjectStatus::Planned) {
                throw new BusinessRuleException(
                    'Project status is not planned.',
                    409
                );
            }
            $project->status = ProjectStatus::InProgress;
            if ($project->start_date === null) {
                $project->start_date = now();
            }
            $project->end_date = null;
            $project->save();

            DB::connection('tenant')->afterCommit(function () use ($project) {
                $this->flushCache();
                $this->notify->startProjectNotify($project);
                activity()
                    ->performedOn($project)
                    ->withProperties([
                        'from_status' => ProjectStatus::Planned->value,
                        'to_status' => ProjectStatus::InProgress->value,
                    ])
                    ->log('Project started');
            });

            return $project->refresh();
        });
    }

    /**
     * Summary of hold
     * @param Project $project
     * @return Project
     */
    public function hold(Project $project): Project
    {
        return DB::connection('tenant')->transaction(function () use ($project) {

            if ($project->status !== ProjectStatus::InProgress) {
                throw new BusinessRuleException(
                    'Only in-progress projects can be put on hold.',
                    409
                );
            }

            $project->status = ProjectStatus::OnHold;
            $project->end_date = null;

            $project->save();

            DB::connection('tenant')->afterCommit(function () use ($project) {
                $this->flushCache();
                $this->notify->onHoldProjectNotify($project);
                activity()
                    ->performedOn($project)
                    ->withProperties([
                        'from_status' => ProjectStatus::InProgress->value,
                        'to_status' => ProjectStatus::OnHold->value,
                    ])
                    ->log('Project put on hold');
            });

            return $project->refresh();
        });
    }

    /**
     * Summary of resume
     * @param Project $project
     * @return Project
     */
    public function resume(Project $project): Project
    {
        return DB::connection('tenant')->transaction(function () use ($project) {

            if ($project->status !== ProjectStatus::OnHold) {
                throw new BusinessRuleException(
                    'Only on-hold projects can be resumed.',
                    409
                );
            }

            $project->status = ProjectStatus::InProgress;
            $project->end_date = null;

            $project->save();

            DB::connection('tenant')->afterCommit(function () use ($project) {
                $this->flushCache();
                $this->notify->resumeProjectNotify($project);
                activity()
                    ->performedOn($project)
                    ->withProperties([
                        'from_status' => ProjectStatus::OnHold->value,
                        'to_status' => ProjectStatus::InProgress->value,
                    ])
                    ->log('Project resumed');
            });

            return $project->refresh();
        });
    }

    /**
     * Summary of cancel
     * @param Project $project
     * @return Project
     */
    public function cancel(Project $project): Project
    {
        return DB::connection('tenant')->transaction(function () use ($project) {

            if ($project->status !== ProjectStatus::InProgress) {
                throw new BusinessRuleException(
                    'Only in-progress projects can be cancelled.',
                    409
                );
            }
            $project->status = ProjectStatus::Cancelled;
            $project->end_date = now();
            $project->save();
            $project->tasks()
                ->whereNotIn('status', [TaskStatus::COMPLETED->value, TaskStatus::CANCELLED->value])
                ->update([
                    'status' => TaskStatus::CANCELLED->value,
                ]);

            DB::connection('tenant')->afterCommit(function () use ($project) {
                $this->flushCache();
                $this->notify->cancelProjectNotify($project);
                activity()
                    ->performedOn($project)
                    ->withProperties([
                        'from_status' => ProjectStatus::InProgress->value,
                        'to_status' => ProjectStatus::Cancelled->value,
                    ])
                    ->log('Project cancelled');
            });

            return $project->refresh();
        });
    }

    /**
     * Summary: Complete a project
     *@param Project $project
     *@return Project
     */
    public function complete(Project $project): Project
    {
        return DB::connection('tenant')->transaction(function () use ($project) {

            if ($project->status !== ProjectStatus::InProgress) {
                throw new BusinessRuleException(
                    'Only in-progress projects can be completed.',
                    409
                );
            }
            $hasIncompleteTasks = $project->tasks()
                ->whereNotIn('status', [
                    TaskStatus::COMPLETED->value,
                    TaskStatus::CANCELLED->value,
                ])
                ->exists();
            if ($hasIncompleteTasks) {
                throw new BusinessRuleException(
                    'Project cannot be completed because it has incomplete tasks.',
                    409
                );
            }
            $project->status = ProjectStatus::Completed;
            $project->end_date = now();
            $project->save();
            DB::connection('tenant')->afterCommit(function () use ($project) {
                $this->flushCache();
                $this->notify->completeProjectNotify($project);
                activity()
                    ->performedOn($project)
                    ->withProperties([
                        'from_status' => ProjectStatus::InProgress->value,
                        'to_status' => ProjectStatus::Completed->value,
                    ])
                    ->log('Project completed');
            });
            return $project->refresh();
        });
    }
}
