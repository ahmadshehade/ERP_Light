<?php

namespace App\Listeners;

use App\Events\SubscriptionActivated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Central\Services\Tenant\TenantProvisioningService;

class ProvisionTenant implements ShouldQueue
{

    public function __construct(
        private TenantProvisioningService $tenantProvisioningService
    ) {}

    public function handle(SubscriptionActivated $event): void
    {
        $this->tenantProvisioningService->provision(
            $event->subscription
        );
    }
}
