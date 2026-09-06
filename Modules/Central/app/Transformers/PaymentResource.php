<?php

namespace Modules\Central\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,

            'subscription_id' => $this->subscription_id,

            'amount' => $this->amount,
            'currency' => $this->currency,

            'payment_method' => $this->payment_method,
            'status' => $this->status?->value,

            'gateway' => $this->gateway,
            'transaction_id' => $this->transaction_id,

            'paid_at' => $this->paid_at?->toISOString(),

            'subscription' => SubscriptionResource::make(
                $this->whenLoaded('subscription')
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
