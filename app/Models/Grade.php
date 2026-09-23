<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'educational_level_id',
        'code',
        'name',
        'order',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    public function educationalLevel(): BelongsTo {
        return $this->belongsTo(EducationalLevel::class);
    }

    public function gradeSections(): HasMany {
        return $this->hasMany(GradeSection::class);
    }
}
