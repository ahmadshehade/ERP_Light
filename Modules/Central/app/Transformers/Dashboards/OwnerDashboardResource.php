<?php

namespace Modules\Central\Transformers\Dashboards;

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
            'companies' => $this->resource['companies'],

            'tenants' => $this->resource['tenants'],

            'subscriptions' => $this->resource['subscriptions'],

            'payments' => $this->resource['payments'],

            'revenue' => $this->resource['revenue'],
        ];
    }
}
