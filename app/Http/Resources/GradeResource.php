<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'educational_level_id'  => $this->educational_level_id,
            'code'                  => $this->code,
            'name'                  => $this->name,
            'order'                 => $this->order,
            'educational_level'     => new EducationalLevelResource($this->whenLoaded('educationalLevel')),
        ];
    }
}
