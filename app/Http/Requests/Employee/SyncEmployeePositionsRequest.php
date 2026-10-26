<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncEmployeePositionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'position_ids' => [
                'required',
                'array',
                'max:20',
            ],

            'position_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('positions', 'id')
                    ->where('is_active', true),
            ],
        ];
    }
}
