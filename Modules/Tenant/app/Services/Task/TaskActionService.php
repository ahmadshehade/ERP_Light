<?php

namespace Modules\Tenant\Services\Task;

use App\Enums\NameOfCache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Enum\TaskStatus;
use Modules\Tenant\Enum\TenantRoles;
use Modules\Tenant\Models\Task;
use Modules\Tenant\Models\TenantUser;
use App\Exceptions\BusinessRuleException;

class TaskActionService
{

    public function __construct(public TaskNotificationService $notify) {}

    /**
     * Summary of completeTask
     * @param Task $task
     * @return Task
     */
    public function completeTask(Task $task): Task
    {
        return DB::connection('tenant')->transaction(function () use ($task) {
            $this->ensureUserCanManageTask($task);
            if ($task->due_date->isPast()) {
                $task->update(['status' => TaskStatus::CANCELLED->value,]);
                throw new BusinessRuleException('This task has expired and cannot be completed.', 409);
            }

            if ($task->status !== TaskStatus::IN_PROGRESS) {
                throw new BusinessRuleException(
                    'Only tasks in progress can be completed.',
                    409
                );
            }
            $task->update([
                'status' => TaskStatus::COMPLETED->value,
                'completed_at' => now(),
            ]);
            DB::connection('tenant')->afterCommit(function () use ($task) {
                $this->flushCache();
                $this->notify->completeTaskNotify($task);
                activity()
                    ->performedOn($task)
                    ->withProperties([
                        'from_status' => TaskStatus::IN_PROGRESS->value,
                        'to_status' => TaskStatus::COMPLETED->value,
                        'project_id' => $task->project_id,
                        'team_id' => $task->team_id,
                    ])
                    ->log('Task completed');
            });
            return $task->load('project', 'team.tenantUsers.user');
        });
    }

    /**
     * Summary of cancelTask
     * @param Task $task
     * @return Task
     */
    public function cancelTask(Task $task): Task
    {
        return DB::connection('tenant')->transaction(function () use ($task) {

            if (in_array($task->status, [
                TaskStatus::COMPLETED,
                TaskStatus::CANCELLED,
            ], true)) {
                throw new BusinessRuleException(
                    'This task cannot be cancelled.',
                    409
                );
            }
            $fromStatus = $task->status;
            $task->update([
                'status' => TaskStatus::CANCELLED->value,
            ]);
            DB::connection('tenant')->afterCommit(function () use ($task, $fromStatus) {
                $this->flushCache();
                $this->notify->cancelTaskNotify($task);
                activity()
                    ->performedOn($task)
                    ->withProperties([
                        'from_status' => $fromStatus,
                        'to_status' => TaskStatus::CANCELLED->value,
                        'project_id' => $task->project_id,
                        'team_id' => $task->team_id,
                    ])
                    ->log('Task cancelled');
            });

            return $task->load('project', 'team.tenantUsers.user');
        });
    }

    /**
     * Summary of onHoldTask
     * @param Task $task
     * @return Task
     */
    public function onHoldTask(Task $task): Task
    {
        return DB::connection('tenant')->transaction(function () use ($task) {
            $this->ensureUserCanManageTask($task);
            if ($task->status !== TaskStatus::IN_PROGRESS) {
                throw new BusinessRuleException(
                    'Only tasks in progress can be put on hold.',
                    409
                );
            }
            $task->update([
                'status' => TaskStatus::OnHold->value,
            ]);
            DB::connection('tenant')->afterCommit(function () use ($task) {
                $this->flushCache();
                $this->notify->onHoldTaskNotify($task);
                activity()
                    ->performedOn($task)
                    ->withProperties([
                        'from_status' => TaskStatus::IN_PROGRESS->value,
                        'to_status' => TaskStatus::OnHold->value,
                        'project_id' => $task->project_id,
                        'team_id' => $task->team_id,
                    ])
                    ->log('Task put on hold');
            });
            return $task->load('project', 'team.tenantUsers.user');
        });
    }

    /**
     * Summary of ensureUserCanManageTask
     * @param Task $task
     * @return void
     */
    private function ensureUserCanManageTask(Task $task): void
    {
        $isMember = $task->team
            ->tenantUsers
            ->contains(function (TenantUser $tenantUser) {
                return $tenantUser->user?->id === Auth::id();
            });

        $owner = TenantUser::role(TenantRoles::Owner->value)
            ->where('user_id', Auth::id())
            ->exists();

        $manager = TenantUser::role(TenantRoles::Manager->value)
            ->where('user_id', Auth::id())
            ->exists();

        if (!$isMember && !$owner && !$manager) {
            throw new BusinessRuleException('You cant manage This Task', 403);
        }
    }

    /**
     * Summary of flushCache
     * @return void
     */
    protected function flushCache(): void
    {
        Cache::tags(NameOfCache::TASK->value)->flush();
    }
}
