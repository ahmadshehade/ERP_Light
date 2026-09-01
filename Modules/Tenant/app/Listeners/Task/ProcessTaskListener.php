<?php

namespace Modules\Tenant\Listeners\Task;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Tenant\Enum\TenantRoles;
use Modules\Tenant\Events\Task\ProcessTaskEvent;
use Modules\Tenant\Models\Task;
use Modules\Tenant\Models\TenantUser;
use Modules\Tenant\Notifications\Tasks\ProcessTaskNotification;
use Throwable;

class ProcessTaskListener implements ShouldQueue
{
    public function handle(ProcessTaskEvent $event): void
    {
        try {

            $task = Task::query()
                ->with('team.tenantUsers.user')
                ->find($event->taskId);

            if (!$task) {
                Log::warning('ProcessTaskListener: Task not found.', [
                    'task_id' => $event->taskId,
                    'tenant_id' => tenant('id'),
                ]);

                return;
            }

            $users = $task->team
                ?->tenantUsers
                ->load('user')
                ->pluck('user')
                ->filter();

            $owner = TenantUser::role(TenantRoles::Owner->value)
                ->with('user')
                ->first()
                ?->user;

            $receivers = $users
                ->push($owner)
                ->filter()
                ->unique('id')
                ->values();

            if ($receivers->isEmpty()) {
                Log::info('ProcessTaskListener: No receivers found.', [
                    'task_id' => $task->id,
                    'tenant_id' => tenant('id'),
                ]);

                return;
            }

            $company = tenant()->company;

            Notification::send(
                $receivers,
                new ProcessTaskNotification(
                    task: $task,
                    companyNameEn: $company->getTranslation('name', 'en'),
                    companyNameAr: $company->getTranslation('name', 'ar'),
                )
            );

            Log::info('ProcessTaskListener: Notification sent.', [
                'task_id' => $task->id,
                'tenant_id' => tenant('id'),
                'receivers_count' => $receivers->count(),
            ]);
        } catch (Throwable $e) {

            Log::error('ProcessTaskListener::handle failed.', [
                'task_id' => $event->taskId,
                'tenant_id' => tenant('id'),
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
