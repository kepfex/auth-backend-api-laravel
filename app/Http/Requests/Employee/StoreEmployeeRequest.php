<?php

namespace App\Http\Requests\Employee;

use App\Models\Employee;
use App\Models\Person;
use App\Validation\PersonValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $personRules = array_map(
            fn(array $rules) => [
                'exclude_without:person',
                ...$rules,
            ],
            PersonValidationRules::rules('person.')
        );

        return array_merge([
            'person_id' => [
                'required_without:person',
                'prohibits:person',
                'integer',
                Rule::exists('persons', 'id')
                    ->whereNull('deleted_at'),
            ],

            'person' => [
                'required_without:person_id',
                'prohibits:person_id',
                'array',
            ],
        ], $personRules);
    }


    protected function prepareForValidation(): void
    {
        if (!is_array($this->input('person'))) {
            return;
        }

        $person = $this->input('person');

        if (isset($person['document_number'])) {
            $person['document_number'] = strtoupper(
                trim((string) $person['document_number'])
            );
        }

        $this->merge(['person' => $person]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->filled('person_id')) {
                $alreadyEmployee = Employee::query()
                    ->where('person_id', $this->input('person_id'))
                    ->exists();

                if ($alreadyEmployee) {
                    $validator->errors()->add(
                        'person_id',
                        'Esta persona ya está registrada como empleado.'
                    );
                }

                return;
            }

            if (!is_array($this->input('person'))) {
                return;
            }

            $type = $this->input('person.document_type');
            $number = $this->input('person.document_number');

            if ($type === 'DNI' && !preg_match(
                '/^[0-9]{8}$/',
                (string) $number
            )) {
                $validator->errors()->add(
                    'person.document_number',
                    'El DNI debe contener ocho dígitos.'
                );

                return;
            }

            $exists = Person::withTrashed()
                ->where('document_type', $type)
                ->where('document_number', $number)
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'person.document_number',
                    'El documento ya existe. Busca y selecciona la persona registrada.'
                );
            }
        });
    }
}
