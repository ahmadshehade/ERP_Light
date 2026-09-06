<?php

namespace Modules\Central\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'company_id' => $this->company_id,
            'price_id' => $this->price_id,

            'status' => $this->status?->value,

            'start_date' => $this->start_date?->toISOString(),
            'end_date' => $this->end_date?->toISOString(),
            'trial_end_date' => $this->trial_end_date?->toISOString(),
            'canceled_at' => $this->canceled_at?->toISOString(),

            'company' => CompanyResource::make(
                $this->whenLoaded('company')
            ),

            'price' => SubscriptionPriceResource::make(
                $this->whenLoaded('price')
            ),

            'payments' => PaymentResource::collection(
                $this->whenLoaded('payments')
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
