<?php

namespace App\Http\Requests\Person;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePersonRequest extends FormRequest
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
        $person = $this->route('person');

        return [
            'document_type' => [
                'sometimes',
                'required',
                Rule::in(['DNI', 'CE', 'PASSPORT']),
            ],

            'document_number' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('persons', 'document_number')
                    ->where(
                        'document_type',
                        $this->input(
                            'document_type',
                            $person->document_type
                        )
                    )
                    ->ignore($person->id),
            ],

            'first_names' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            'paternal_surname' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            'maternal_surname' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
            ],

            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:150',
            ],

            'birth_date' => [
                'sometimes',
                'nullable',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],

            'address' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'sex' => [
                'sometimes',
                'nullable',
                Rule::in(['M', 'F']),
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('document_number')) {
            $this->merge([
                'document_number' => strtoupper(
                    trim((string) $this->input('document_number'))
                ),
            ]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $person = $this->route('person');

            $documentType = $this->input(
                'document_type',
                $person->document_type
            );

            $documentNumber = $this->input(
                'document_number',
                $person->document_number
            );

            if (
                $documentType === 'DNI'
                && !preg_match('/^[0-9]{8}$/', (string) $documentNumber)
            ) {
                $validator->errors()->add(
                    'document_number',
                    'El DNI debe contener ocho dígitos.'
                );
            }
        });
    }
}
