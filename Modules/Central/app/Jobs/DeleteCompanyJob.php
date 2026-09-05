<?php

namespace Modules\Central\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Central\Models\Company;
use Modules\Central\Models\Tenant;
use Modules\Central\Services\Tenant\TenantProvisioningService;

class DeleteCompanyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $companyId
    ) {}

    public function handle(
        TenantProvisioningService $tenantProvisioningService
    ): void {
        $company = Company::withTrashed()
            ->find($this->companyId);

        if (!$company) {
            return;
        }

        if (!$company->trashed()) {
            return;
        }

        $tenant = Tenant::query()
            ->where('company_id', $company->id)
            ->first();

        if ($tenant) {
            $tenantProvisioningService->delete($tenant);
        }

        $company->forceDelete();
    }
}
