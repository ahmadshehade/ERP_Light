<?php

namespace Modules\Tenant\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'title' => $this->title,
            'description' => $this->description,

            'status' => $this->status?->value,
            'priority' => $this->priority?->value,

            'start_date' => $this->start_date?->toISOString(),
            'due_date' => $this->due_date?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),

            'is_active' => $this->is_active,

            'project_id' => $this->project_id,
            'team_id' => $this->team_id,
            'media' => MediaResource::collection($this->whenLoaded('media')),

            'project' => ProjectResource::make(
                $this->whenLoaded('project')
            ),

            'team' => TeamResource::make(
                $this->whenLoaded('team')
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
