<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

class EducationalLevel extends Model
{
    use HasFactory;

    protected $fillable = [
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

    public function grades(): HasMany {
        return $this->hasMany(Grade::class);
    }
}
