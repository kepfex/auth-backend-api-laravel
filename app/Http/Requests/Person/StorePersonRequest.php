<?php

namespace App\Http\Requests\Person;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public static function personRules(string $prefix = ''): array
    {
        $p = $prefix;
        return [
            $p . 'document_type' => ['required', Rule::in(['DNI', 'CE', 'PASSPORT'])],
            $p . 'document_number' => ['required', 'string', 'max:20'],
            $p . 'first_names' => ['required', 'string', 'max:100'],
            $p . 'paternal_surname' => ['required', 'string', 'max:100'],
            $p . 'maternal_surname' => ['nullable', 'string', 'max:100'],
            $p . 'phone' => ['nullable', 'string', 'max:20'],
            $p . 'email' => ['nullable', 'email', 'max:150'],
            $p . 'birth_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            $p . 'address' => ['nullable', 'string', 'max:255'],
            $p . 'sex' => ['nullable', Rule::in(['M', 'F'])],
            $p . 'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(self::personRules(), [
            'document_number' => ['required', 'string', 'max:20', Rule::unique('persons')->where('document_type', $this->input('document_type'))],
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['document_number' => strtoupper(trim((string) $this->input('document_number')))]);
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('document_type') === 'DNI' && !preg_match('/^[0-9]{8}$/', (string) $this->input('document_number'))) {
                $validator->errors()->add('document_number', 'El DNI debe contener ocho dígitos.');
            }
        });
    }
}
