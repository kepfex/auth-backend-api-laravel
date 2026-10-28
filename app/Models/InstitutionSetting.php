<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstitutionSetting extends Model
{
    public const SINGLETON_ID = 1;

    protected $fillable = [
        'name', 'abbreviation', 'modular_code', 'local_code',
        'ugel', 'dre', 'address', 'phone', 'contact_email',
    ];
}
