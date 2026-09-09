<?php

namespace Modules\Central\Transformers\Dashboards;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuperAdminDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'users' => $this->resource['users'],

            'profiles' => $this->resource['profiles'],

            'companies' => $this->resource['companies'],

            'tenants' => $this->resource['tenants'],

            'subscription_plans' => $this->resource['subscription_plans'],

            'subscription_prices' => $this->resource['subscription_prices'],

            'subscriptions' => $this->resource['subscriptions'],

            'payments' => $this->resource['payments'],

            'revenue' => $this->resource['revenue'],

            'activity' => $this->resource['activity'],
        ];
    }
}
