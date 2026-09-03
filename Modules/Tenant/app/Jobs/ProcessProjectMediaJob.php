<?php

namespace Modules\Tenant\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Tenant\Models\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessProjectMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    /**
     * Create a new job instance.
     */
    public function __construct(public int $projectId, public array $mediaPaths) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $project = Project::find($this->projectId);
        if (!$project) {
            Log::error('Project not found:' . $this->projectId);
            return;
        }
        foreach ($this->mediaPaths as $path) {
            if (!Storage::disk('local')->exists($path)) {
                continue;
            }
            $project->clearMediaCollection('project');
            $project->addMediaFromDisk($path, 'local')
                ->toMediaCollection('project');

            Storage::disk('local')->delete($path);
        }
    }
}
