<?php

namespace Modules\Tenant\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Controller;

use Modules\Tenant\Services\Dashboards\EmployeeDashboardService;
use Modules\Tenant\Transformers\Dashboards\EmployeeDashboardResource;

class EmployeeDashboardController extends Controller
{
    public function index(
        EmployeeDashboardService $service
    ): EmployeeDashboardResource {
        return new EmployeeDashboardResource(
            $service->getDashboard()
        );
    }
}
