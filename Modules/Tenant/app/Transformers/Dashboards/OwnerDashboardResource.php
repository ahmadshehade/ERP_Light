<?php

namespace Modules\Tenant\Transformers\Dashboards;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OwnerDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'role' => $this['role'],

            'company' => $this['company'],

            'users' => $this['users'],

            'departments' => $this['departments'],

            'positions' => $this['positions'],

            'teams' => $this['teams'],

            'projects' => $this['projects'],

            'tasks' => $this['tasks'],

            'activity' => $this['activity'],
        ];
    }
}
