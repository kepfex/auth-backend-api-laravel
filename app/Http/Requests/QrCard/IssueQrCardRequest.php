<?php

namespace App\Http\Requests\QrCard;

use Illuminate\Foundation\Http\FormRequest;

// Request para emitir una tarjeta QR a un estudiante
class IssueQrCardRequest extends FormRequest
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
            'expires_at.date' =>
            'La fecha de expiración no es válida.',

            'expires_at.after' =>
            'La fecha de expiración debe ser posterior al momento actual.',
        ];
    }
}
