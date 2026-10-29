<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class StoreInstitutionalUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('api');
        $allowed = array_filter(array_map('trim', explode(',', (string) config('institution.bootstrap_admin_emails', ''))));

        return $user !== null && in_array(strtolower((string) $user->email), array_map('strtolower', $allowed), true);
    }

    public function rules(): array
    {
        return [
            'person_id' => ['required', 'integer', Rule::exists('persons', 'id')],
            'profile_type' => ['required', Rule::in(['employee', 'student', 'guardian'])],
            'password' => ['required', 'string', 'confirmed', Password::min(10)->letters()->numbers()],
        ];
    }
}
