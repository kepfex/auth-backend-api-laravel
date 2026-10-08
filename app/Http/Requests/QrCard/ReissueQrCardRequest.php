<?php

namespace App\Http\Requests\QrCard;

use Illuminate\Foundation\Http\FormRequest;

// Request para reemitir una tarjeta QR a un estudiante
class ReissueQrCardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'min:3',
                'max:500',
            ],

            'expires_at' => [
                'nullable',
                'date',
                'after:now',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' =>
            'Debe indicar el motivo de reemisión.',

            'expires_at.after' =>
            'La fecha de expiración debe ser posterior al momento actual.',
        ];
    }
}
