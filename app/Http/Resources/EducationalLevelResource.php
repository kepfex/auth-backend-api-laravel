<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EducationalLevelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'code'        => $this->code,
            'name'      => $this->name,
            'order'     => $this->order,

            'grades'    => GradeResource::collection(
                $this->whenLoaded('grades')
            ),
            'created_at'=> $this->created_at?->toISOString(),
        ];
    }
}
