<?php

namespace Modules\Central\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;
use Modules\Tenant\Transformers\MediaResource;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,
            'subdomain' => $this->subdomain,
            'max_users' => $this->max_users,
            'is_active' => $this->is_active,

            'owner_id' => $this->owner_id,

            'owner' => UserResource::make(
                $this->whenLoaded('owner')
            ),

            'media' => MediaResource::collection(
                $this->whenLoaded('media')
            ),

            'subscriptions' => SubscriptionResource::collection(
                $this->whenLoaded('subscriptions')
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
