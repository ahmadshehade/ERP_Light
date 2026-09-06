<?php

namespace Modules\Tenant\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'user_id' => $this->user_id,

            'name' => $this->whenLoaded(
                'user',
                fn() => $this->user->name
            ),

            'email' => $this->whenLoaded(
                'user',
                fn() => $this->user->email
            ),

            'is_active' => $this->is_active,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
