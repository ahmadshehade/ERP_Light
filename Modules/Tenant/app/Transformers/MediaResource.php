<?php

namespace Modules\Tenant\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,

            'collection' => $this->collection_name,

            'name' => $this->name,
            'file_name' => $this->file_name,

            'mime_type' => $this->mime_type,
            'size' => $this->size,

            'url' => $this->getUrl(),

            'disk' => $this->disk,

            'conversions' => [
                'original' => $this->getUrl(),
                'thumb' => $this->getUrl('thumb'),
                'medium' => $this->getUrl('medium'),
            ],

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
