<?php

namespace Modules\Tenant\Transformers\Dashboards;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'role' => $this['role'],

            'departments' => $this['departments'],

            'positions' => $this['positions'],

            'teams' => $this['teams'],

            'projects' => $this['projects'],

            'tasks' => $this['tasks'],
        ];
    }
}
