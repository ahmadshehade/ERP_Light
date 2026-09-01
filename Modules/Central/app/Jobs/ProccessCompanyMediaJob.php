<?php

namespace Modules\Central\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Central\Models\Company;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProccessCompanyMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $companyId, public string $path) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $company = Company::find($this->companyId);

        if (!$company) {
            Log::error('Company not found:' . $this->companyId);
            return;
        }
        try {
            if (!Storage::disk('local')->exists($this->path)) {
                Log::error('Company media file not found.', [
                    'company_id' => $this->companyId,
                    'media_path' => $this->path,
                ]);
                return;
            }
            $company->clearMediaCollection('company');
            $company
                ->addMediaFromDisk($this->path, 'local')
                ->toMediaCollection('company');

            Storage::disk('local')->delete($this->path);
        } catch (\Exception $e) {
            Log::error('ProccessCompanyMediaJob::handle', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
