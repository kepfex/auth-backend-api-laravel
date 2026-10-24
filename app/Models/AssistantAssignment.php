<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AssistantAssignment extends Model
{
    protected $fillable = [
        'employee_id',
        'grade_section_id',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'is_active' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function gradeSection(): BelongsTo
    {
        return $this->belongsTo(GradeSection::class);
    }

    /**
     * Asignaciones vigentes para una fecha.
     */
    public function scopeEffectiveOn(
        Builder $query,
        Carbon|string $date
    ): Builder {
        $date = Carbon::parse($date)->toDateString();

        return $query
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $date)
            ->where(function (Builder $query) use ($date) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $date);
            });
    }
}
