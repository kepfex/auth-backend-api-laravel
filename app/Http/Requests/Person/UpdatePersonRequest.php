<?php

namespace App\Http\Requests\Person;

use App\Models\Person;
use App\Validation\PersonDocumentValidator;
use App\Validation\PersonValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return PersonValidationRules::rules(
            partial: true
        );
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('document_number')) {
            $this->merge([
                'document_number' =>
                    PersonDocumentValidator::normalize(
                        $this->input('document_number')
                    ),
            ]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // El parámetro {person} utiliza
            // Route Model Binding.
            $person = $this->route('person');

            if (! $person instanceof Person) {
                return;
            }

            // Comprobar documento solamente cuando
            // cambie alguno de sus componentes.
            if (
                ! $this->exists('document_type') &&
                ! $this->exists('document_number')
            ) {
                return;
            }

            // Combinar campos enviados con los
            // valores actuales de la persona.
            $documentType = $this->input(
                'document_type',
                $person->document_type
            );

            $documentNumber = $this->input(
                'document_number',
                $person->document_number
            );

            PersonDocumentValidator::validate(
                validator: $validator,
                type: $documentType,
                number: $documentNumber,
                field: 'document_number',
                ignorePersonId: $person->id,
            );
        });
    }
}
