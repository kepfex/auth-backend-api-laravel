<?php

namespace App\Http\Requests\Student;

use App\Http\Requests\Person\StorePersonRequest;
use Illuminate\Contracts\Validation\ValidationRule;
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
    public function rules(): array {
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

            'student_code' => [
                'required',
                'string',
                'max:25',
                Rule::unique('students', 'student_code'),
            ],

            'status' => [
                'sometimes',
                Rule::in(['activo', 'inactivo', 'egresado']),
            ],

        ], array_map(
            fn($rules) => array_merge(
                ['exclude_without:person'],
                $rules
            ),
            StorePersonRequest::personRules('person.')
        ));
    }

    protected function prepareForValidation(): void
    {
        $data = [];
        if (is_array($this->input('person'))) {
            $person = $this->input('person');
            $person['document_number'] = strtoupper(trim((string) ($person['document_number'] ?? '')));
            $data['person'] = $person;
        }
        if ($this->has('student_code')) $data['student_code'] = strtoupper(trim((string) $this->input('student_code')));
        $this->merge($data);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('person.document_type') === 'DNI' && !preg_match('/^[0-9]{8}$/', (string) $this->input('person.document_number'))) {
                $validator->errors()->add('person.document_number', 'El DNI debe contener ocho dígitos.');
            }
            if ($this->filled('person_id') && \App\Models\Student::where('person_id', $this->input('person_id'))->exists()) {
                $validator->errors()->add('person_id', 'Esta persona ya está registrada como estudiante.');
            }
            if (is_array($this->input('person')) && \App\Models\Person::withTrashed()->where('document_type', $this->input('person.document_type'))->where('document_number', $this->input('person.document_number'))->exists()) {
                $validator->errors()->add('person.document_number', 'El documento ya existe. Busca y selecciona la persona registrada.');
            }
        });
    }
}
