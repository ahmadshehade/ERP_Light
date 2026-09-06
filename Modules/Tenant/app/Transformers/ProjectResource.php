<?php

namespace Modules\Tenant\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,

            'name' => $this->name,
            'description' => $this->description,

            'status' => $this->status?->value,
            'priority' => $this->priority?->value,

            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),

            'is_active' => $this->is_active,

            'media' => MediaResource::collection($this->whenLoaded('media')),

            'teams' => TeamResource::collection(
                $this->whenLoaded('teams')
            ),

            'tasks' => TaskResource::collection(
                $this->whenLoaded('tasks')
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
