<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QrCardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->lifecycleStatus();
        return [
            'id' =>
            $this->id,

            'student_id' =>
            $this->student_id,

            /*
            |--------------------------------------------------------------------------
            | Valor que se codificará dentro de la imagen QR
            |--------------------------------------------------------------------------
            */

            'uuid' =>
            $this->uuid,

            'qr_value' =>
            $this->uuid,

            'status' =>
            $status->value,

            'status_label' =>
            $status->label(),

            'issued_at' =>
            $this->issued_at
                ?->toISOString(),

            'expires_at' =>
            $this->expires_at
                ?->toISOString(),

            'revoked_at' =>
            $this->revoked_at
                ?->toISOString(),

            'revocation_reason' =>
            $this->revocation_reason,

            'issued_by' =>
            $this->whenLoaded(
                'issuedBy',
                fn() => [
                    'id' =>
                    $this->issuedBy?->id,

                    'username' =>
                    $this->issuedBy?->username,
                ]
            ),

            'revoked_by' =>
            $this->whenLoaded(
                'revokedBy',
                fn() =>
                $this->revokedBy
                    ? [
                        'id' =>
                        $this->revokedBy->id,

                        'username' =>
                        $this->revokedBy->username,
                    ]
                    : null
            ),

            'created_at' =>
            $this->created_at
                ?->toISOString(),

            'updated_at' =>
            $this->updated_at
                ?->toISOString(),
        ];
    }
}
