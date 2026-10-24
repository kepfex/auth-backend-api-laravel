<?php

namespace App\Http\Requests\Person;

use App\Models\Person;
use App\Models\Student;
use App\Validation\PersonValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePersonRequest extends FormRequest
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
        return array_merge(PersonValidationRules::rules(), [
            'document_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('persons', 'document_number')
                    ->where('document_type', $this->input('document_type'))
            ],
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['document_number' => strtoupper(trim((string) $this->input('document_number')))]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Validar formato del DNI
            if (
                $this->input('document_type') === 'DNI'
                && !preg_match(
                    '/^[0-9]{8}$/',
                    (string) $this->input('document_number')
                )
            ) {
                $validator->errors()->add('document_number', 'El DNI debe contener ocho dígitos.');
            }

            // Evitar que una persona existente vuelva a ser estudiante
            if (
                $this->filled('person_id')
                && Student::where('person_id', $this->input('person_id'))->exists()
            ) {
                $validator->errors()->add(
                    'person_id',
                    'Esta persona ya está registrada como estudiante.'
                );
            }

            // Evitar crear otra persona con el mismo documento
            if (
                is_array($this->input('person'))
                && Person::withTrashed()
                ->where(
                    'document_type',
                    $this->input('person.document_type')
                )
                ->where(
                    'document_number',
                    $this->input('person.document_number')
                )
                ->exists()
            ) {
                $validator->errors()->add(
                    'person.document_number',
                    'El documento ya existe. Busca y selecciona la persona registrada.'
                );
            }
        });
    }
}
