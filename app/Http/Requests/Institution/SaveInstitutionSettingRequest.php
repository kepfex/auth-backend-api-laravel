<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;

class SaveInstitutionSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('api');
        if (!$user) return false;

        $emails = array_filter(array_map(
            static fn (string $email): string => strtolower(trim($email)),
            explode(',', (string) config('institution.bootstrap_admin_emails', ''))
        ));

        return in_array(strtolower((string) $user->email), $emails, true);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('abbreviation') && is_string($this->input('abbreviation'))) {
            $this->merge(['abbreviation' => strtolower(trim($this->input('abbreviation')))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'abbreviation' => ['required', 'string', 'min:2', 'max:20', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'modular_code' => ['nullable', 'string', 'max:20'],
            'local_code' => ['nullable', 'string', 'max:20'],
            'ugel' => ['nullable', 'string', 'max:100'],
            'dre' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'contact_email' => ['nullable', 'email', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return ['abbreviation.regex' => 'Usa letras minúsculas, números y guiones simples; sin espacios ni caracteres especiales.'];
    }
}
