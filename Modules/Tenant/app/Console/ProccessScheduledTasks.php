<?php

namespace Modules\Tenant\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Central\Models\Tenant;
use Modules\Tenant\Enum\TaskStatus;
use Modules\Tenant\Events\Task\CancelTaskEvent;
use Modules\Tenant\Events\Task\ProcessTaskEvent;
use Modules\Tenant\Models\Task;

class ProccessScheduledTasks extends Command
{
    protected $signature = 'tenant:process-scheduled-tasks';

    protected $description = 'Process scheduled tenant tasks';

    public function handle(): int
    {
        Tenant::query()->each(function (Tenant $tenant) {

            tenancy()->initialize($tenant);

            try {

                $now = now();

                /*
                 * 1. Cancel expired tasks
                 */
                $expiredTasks = Task::query()
                    ->whereIn('status', [
                        TaskStatus::OPEN,
                        TaskStatus::IN_PROGRESS,
                    ])
                    ->where('due_date', '<', $now)
                    ->get();

                foreach ($expiredTasks as $task) {

                    Log::info('Cancelling expired task', [
                        'tenant_id' => $tenant->id,
                        'task_id' => $task->id,
                        'status' => $task->status,
                        'due_date' => $task->due_date,
                    ]);

                    $task->update([
                        'status' => TaskStatus::CANCELLED->value,
                    ]);
                    event(new CancelTaskEvent($task->id));
                }


                /*
                 * 2. Start scheduled tasks
                 */
                $tasks = Task::query()
                    ->where('status', TaskStatus::OPEN->value)
                    ->where('start_date', '<=', $now)
                    ->where('due_date', '>=', $now)
                    ->get();

                Log::info('Scheduled tasks check', [
                    'tenant_id' => $tenant->id,
                    'database' => config('database.connections.tenant.database'),
                    'now' => $now->toDateTimeString(),
                    'tasks_count' => $tasks->count(),
                ]);

                foreach ($tasks as $task) {

                    Log::info('Processing scheduled task', [
                        'tenant_id' => $tenant->id,
                        'task_id' => $task->id,
                        'status' => $task->status,
                        'start_date' => $task->start_date,
                    ]);

                    $task->update([
                        'status' => TaskStatus::IN_PROGRESS->value,
                    ]);

                    event(
                        new ProcessTaskEvent($task->id)
                    );
                }
            } finally {

                tenancy()->end();
            }
        });

        return self::SUCCESS;
    }
}
