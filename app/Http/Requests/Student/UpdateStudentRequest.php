<?php

namespace App\Http\Requests\Student;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
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
        $student = $this->route('student');

        return [
            'student_code' => [
                'sometimes',
                'required',
                'string',
                'max:25',
                Rule::unique('students', 'student_code')
                    ->ignore($student->id),
            ],

            'status' => [
                'sometimes',
                'required',
                Rule::in(['activo', 'inactivo', 'egresado']),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('student_code')) {
            $this->merge([
                'student_code' => strtoupper(
                    trim((string) $this->input('student_code'))
                ),
            ]);
        }
    }
}
