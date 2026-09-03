<?php

namespace Modules\Tenant\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Modules\Tenant\Models\Task;
use RuntimeException;

class ProcessTaskMediJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $taskId,
        public array $mediaPaths = []
    ) {}

    public function handle(): void
    {
        $task = Task::find($this->taskId);

        if (!$task) {
            throw new RuntimeException(
                "Task {$this->taskId} not found."
            );
        }

        if (empty($this->mediaPaths)) {
            return;
        }

        foreach ($this->mediaPaths as $path) {

            if (!Storage::disk('local')->exists($path)) {
                throw new RuntimeException(
                    "Media file not found: {$path}"
                );
            }

            $task
                ->addMediaFromDisk($path, 'local')
                ->toMediaCollection('task');

            Storage::disk('local')->delete($path);
        }
    }
}
