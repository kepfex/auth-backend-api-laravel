<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

class StudentGuardian extends Model
{
    protected $fillable = [
        'student_id',
        'guardian_id',
        'relationship',
        'is_primary',
        'receives_notifications',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'receives_notifications' => 'boolean',
        ];
    }

    public function student(): BelongsTo {
        return $this->belongsTo((Student::class));
    }

    public function guardian(): BelongsTo {
        return $this->belongsTo(Guardian::class);
    }
}
