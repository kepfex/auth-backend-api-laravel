<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstitutionSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'abbreviation' => $this->abbreviation,
            'modular_code' => $this->modular_code,
            'local_code' => $this->local_code,
            'ugel' => $this->ugel,
            'dre' => $this->dre,
            'address' => $this->address,
            'phone' => $this->phone,
            'contact_email' => $this->contact_email,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
