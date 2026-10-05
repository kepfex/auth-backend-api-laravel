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

        $personId = $student?->person_id;

        return [
            'student_code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('students', 'student_code')
                    ->ignore($student?->id),
            ],

            'status' => [
                'sometimes',
                Rule::in([
                    'activo',
                    'inactivo',
                    'egresado',
                ]),
            ],

            'person' => [
                'sometimes',
                'array',
            ],

            'person.document_type' => [
                'required_with:person',
                Rule::in([
                    'DNI',
                    'CE',
                    'PASSPORT',
                ]),
            ],

            'person.document_number' => [
                'required_with:person',
                'string',
                'max:20',

                Rule::unique(
                    'persons',
                    'document_number',
                )
                    ->ignore($personId)
                    ->where(
                        fn($query) =>
                        $query->where(
                            'document_type',
                            $this->input(
                                'person.document_type',
                            ),
                        )
                    ),
            ],

            'person.first_names' => [
                'required_with:person',
                'string',
                'max:100',
            ],

            'person.paternal_surname' => [
                'required_with:person',
                'string',
                'max:100',
            ],

            'person.maternal_surname' => [
                'nullable',
                'string',
                'max:100',
            ],

            'person.phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'person.email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'person.birth_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],

            'person.address' => [
                'nullable',
                'string',
                'max:255',
            ],

            'person.sex' => [
                'nullable',
                Rule::in([
                    'M',
                    'F',
                ]),
            ],

            'person.is_active' => [
                'sometimes',
                'boolean',
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
