<?php

namespace Modules\Tenant\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Controller;
use Modules\Tenant\Services\Dashboards\MnangerDashboardService;
use Modules\Tenant\Transformers\Dashboards\ManagerDashboardResource;

class ManagerDashboardController extends Controller
{

    public function index(
        MnangerDashboardService $service
    ): ManagerDashboardResource {
        return new ManagerDashboardResource(
            $service->getDashboard()
        );
    }
}
