<?php

namespace App\Http\Requests\Student;

use App\Models\Student;
use App\Validation\PersonDocumentValidator;
use App\Validation\PersonValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStudentRequest extends FormRequest
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
        return array_merge([
            'person_id' => [
                'required_without:person',
                'prohibits:person',
                'integer',
                Rule::exists('persons', 'id')->whereNull('deleted_at'),
            ],

            'person' => [
                'required_without:person_id',
                'prohibits:person_id',
                'array',
            ],

            'student_code' => ['prohibited'],

            'status' => [
                'sometimes',
                Rule::in(['activo', 'inactivo', 'egresado']),
            ],

        ], array_map(
            fn(array $rules) => [
                'exclude_without:person',
                ...$rules,
            ],
            PersonValidationRules::rules('person.')
        ));
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if (is_array($this->input('person'))) {
            $person = $this->input('person');

            if (isset($person['document_number'])) {
                $person['document_number'] =
                    PersonDocumentValidator::normalize(
                        $person['document_number']
                    );
            }

            $data['person'] = $person;
        }

        $this->merge($data);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // Modalidad 1: persona existente.
            if ($this->filled('person_id')) {
                $alreadyStudent = Student::query()
                    ->where('person_id', $this->input('person_id'))
                    ->exists();

                if ($alreadyStudent) {
                    $validator->errors()->add(
                        'person_id',
                        'Esta persona ya está registrada como estudiante.'
                    );
                }

                return;
            }

            // Modalidad 2: registrar una persona nueva.
            if (!is_array($this->input('person'))) {
                return;
            }

            PersonDocumentValidator::validate(
                validator: $validator,
                type: $this->input('person.document_type'),
                number: $this->input('person.document_number'),
                field: 'person.document_number',
            );
        });
    }
}
