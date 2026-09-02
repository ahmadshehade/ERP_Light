<?php

namespace Modules\Tenant\Console;

use Illuminate\Console\Command;
use Modules\Tenant\Enum\ProjectStatus;
use Modules\Tenant\Models\Project;
use Modules\Tenant\Services\Project\ProjectStatusService;
use Modules\Central\Models\Tenant;

class ProccessScheduledProjects extends Command


{
    protected $signature = 'tenant:process-scheduled-projects';

    protected $description = 'Process scheduled tenant projects';

    public function handle(ProjectStatusService $projectStatusService): int
    {
        $tenants = Tenant::query()->get();

        $this->info('Tenants found: ' . $tenants->count());

        foreach ($tenants as $tenant) {

            $this->info("Processing tenant: {$tenant->id}");

            tenancy()->initialize($tenant);

            try {

                $projects = Project::query()
                    ->where('status', ProjectStatus::Planned)
                    ->whereNotNull('start_date')
                    ->where('start_date', '<=', now())
                    ->get();

                $this->info(
                    'Projects ready to start: ' . $projects->count()
                );

                foreach ($projects as $project) {

                    $this->info(
                        "Starting project #{$project->id}"
                    );

                    $projectStatusService->start($project);

                    $this->info(
                        "Project #{$project->id} started successfully."
                    );
                }
            } finally {

                tenancy()->end();
            }
        }

        return Command::SUCCESS;
    }
}
