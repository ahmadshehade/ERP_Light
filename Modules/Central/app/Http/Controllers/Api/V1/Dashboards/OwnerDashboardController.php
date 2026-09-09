<?php

namespace Modules\Central\Http\Controllers\Api\V1\Dashboards;

use App\Http\Controllers\Controller;
use Modules\Central\Services\Dashboards\OwnerDashboardService;
use Modules\Central\Transformers\Dashboards\OwnerDashboardResource;

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
