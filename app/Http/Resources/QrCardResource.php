<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QrCardResource extends JsonResource
{
    /**
     * Transformar el QR en una respuesta JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'student_id' => $this->student_id,

            'uuid' => $this->uuid,

            // Valor que React convertirá en imagen QR.
            'qr_value' => $this->uuid,

            'is_active' => $this->is_active,

            'created_at' => $this->created_at
                ?->toISOString(),

            'updated_at' => $this->updated_at
                ?->toISOString(),
        ];
    }
}
