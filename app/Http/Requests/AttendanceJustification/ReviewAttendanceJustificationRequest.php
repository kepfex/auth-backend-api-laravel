<?php

namespace App\Http\Requests\AttendanceJustification;

use App\Enums\AttendanceJustificationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewAttendanceJustificationRequest extends FormRequest
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
            'status' => [
                'required',
                Rule::in([
                    AttendanceJustificationStatus::Approved->value,
                    AttendanceJustificationStatus::Rejected->value,
                ]),
            ],

            'review_comment' => [
                'nullable',
                'string',
                'max:2000',
                'required_if:status,rejected',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' =>
            'Debe indicar el resultado de la revisión.',

            'status.in' =>
            'El estado de revisión debe ser aprobado o rechazado.',

            'review_comment.required_if' =>
            'Debe indicar el motivo cuando rechaza una justificación.',

            'review_comment.max' =>
            'El comentario no puede superar los 2000 caracteres.',
        ];
    }
}
