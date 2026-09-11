<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

class GradeSection extends Model
{
    protected $fillable = [
        'academic_year_id',
        'grade_id',
        'section_id',
        'shift',
        'capacity',
        'is_active',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo {
        return $this->belongsTo(AcademicYear::class);
    }

    public function grade(): BelongsTo {
        return $this->belongsTo(Grade::class);
    }

    public function section(): BelongsTo {
        return $this->belongsTo(Section::class);
    }
}
