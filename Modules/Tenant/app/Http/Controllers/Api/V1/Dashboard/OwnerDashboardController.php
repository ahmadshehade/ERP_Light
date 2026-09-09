<?php

namespace Modules\Tenant\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Controller;

use Modules\Tenant\Services\Dashboards\OwnerDashboardService;
use Modules\Tenant\Transformers\Dashboards\OwnerDashboardResource;

class OwnerDashboardController extends Controller
{
    public function index(
        OwnerDashboardService $service
    ): OwnerDashboardResource {
        return new OwnerDashboardResource(
            $service->getDashboard()
        );
    }
}
