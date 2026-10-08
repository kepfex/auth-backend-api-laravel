<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class ScanAttendanceQrRequest extends FormRequest
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
            /*
             * Intencionalmente NO usamos:
             *
             * uuid
             *
             * porque queremos registrar también
             * intentos con códigos inválidos.
             */

            'qr' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'qr.required' =>
            'Debe proporcionar el código QR.',

            'qr.string' =>
            'El código QR no es válido.',

            'qr.max' =>
            'El código QR excede la longitud permitida.',
        ];
    }
}
