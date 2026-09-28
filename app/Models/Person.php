<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'document_type',
        'document_number',
        'first_names',
        'paternal_surname',
        'maternal_surname',
        'phone',
        'email',
        'birth_date',
        'address',
        'sex',
        'is_active'
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date:Y-m-d',
            'is_active' => 'boolean'
        ];
    }

    public function student(): HasOne {
        return $this->hasOne(Student::class);
    }
}
