<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentGuardianResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'student_id' => $this->student_id,
            'guardian_id' => $this->guardian_id,

            'relationship' => $this->relationship->value,
            'relationship_label' => $this->relationship->label(),

            'is_primary' => $this->is_primary,
            'receives_notifications' =>
                $this->receives_notifications,

            'guardian' => new GuardianResource(
                $this->whenLoaded('guardian')
            ),
        ];
    }
}
