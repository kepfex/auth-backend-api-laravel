<?php

namespace App\Http\Requests\Person;

use App\Validation\PersonDocumentValidator;
use App\Validation\PersonValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return PersonValidationRules::rules();
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
            PersonDocumentValidator::validate(
                validator: $validator,
                type: $this->input('document_type'),
                number: $this->input('document_number'),
            );
        });
    }
}
