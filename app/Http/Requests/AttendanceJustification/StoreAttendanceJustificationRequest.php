<?php

namespace App\Http\Requests\AttendanceJustification;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceJustificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'attendance_day_id' => [
                'required',
                'integer',
                'exists:attendance_days,id',
            ],

            'attendance_mark_id' => [
                'nullable',
                'integer',
                'exists:attendance_marks,id',
            ],

            'reason' => [
                'required',
                'string',
                'min:10',
                'max:3000',
            ],

            'attachment' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'attendance_day_id.required' =>
            'Debe seleccionar una jornada de asistencia.',

            'attendance_day_id.exists' =>
            'La jornada de asistencia seleccionada no existe.',

            'attendance_mark_id.exists' =>
            'La marcación seleccionada no existe.',

            'reason.required' =>
            'El motivo de la justificación es obligatorio.',

            'reason.min' =>
            'El motivo debe contener al menos 10 caracteres.',

            'reason.max' =>
            'El motivo no puede superar los 3000 caracteres.',

            'attachment.file' =>
            'El archivo adjunto no es válido.',

            'attachment.mimes' =>
            'El archivo debe ser PDF, JPG, JPEG o PNG.',

            'attachment.max' =>
            'El archivo no puede superar los 5 MB.',
        ];
    }
}
