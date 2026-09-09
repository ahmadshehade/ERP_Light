<?php

namespace Modules\Central\Http\Controllers\Api\V1\Dashboards;

use App\Http\Controllers\Controller;
use Modules\Central\Services\Dashboards\SuperAdminDashboardService;
use Modules\Central\Transformers\Dashboards\SuperAdminDashboardResource;

class SuperAdminDashboardController extends Controller
{
    public function index(
        SuperAdminDashboardService $service
    ): SuperAdminDashboardResource {
        return new SuperAdminDashboardResource(
            $service->getDashboard()
        );
    }
}
