<?php

namespace Modules\Tenant\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Tenant\Models\Department;

class ProcessDepartmentMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public  int $tries = 3;
    /**
     * Create a new job instance.
     */
    public function __construct(public int $departmentId, public string $path) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $department = Department::find($this->departmentId);

        if (!$department) {
            Log::error('Department not found:' . $this->departmentId);
            return;
        }
        try {
            if (!Storage::disk('local')->exists($this->path)) {
                Log::error('Company media file not found.', [
                    'department_id' => $this->departmentId,
                    'media_path' => $this->path,
                ]);
                return;
            }
            $department->clearMediaCollection('department');
            $department
                ->addMediaFromDisk($this->path, 'local')
                ->toMediaCollection('department');

            Storage::disk('local')->delete($this->path);
        } catch (\Exception $e) {
            Log::error('ProccessCompanyMediaJob::handle', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
