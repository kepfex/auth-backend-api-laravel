<?php

namespace App\Http\Requests\QrCard;

use Illuminate\Foundation\Http\FormRequest;

// Request para revocar una tarjeta QR
class RevokeQrCardRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' =>
            'Debe indicar el motivo de revocación.',

            'reason.min' =>
            'El motivo debe contener al menos 3 caracteres.',

            'reason.max' =>
            'El motivo no puede superar los 500 caracteres.',
        ];
    }
}
