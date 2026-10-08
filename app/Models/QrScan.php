<?php

namespace App\Models;

use App\Enums\QrScanResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrScan extends Model
{
    protected $fillable = [
        'qr_card_id',
        'attendance_mark_id',
        'token_fingerprint',
        'scanned_at',
        'result',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'scanned_at' =>
            'datetime',

            'result' =>
            QrScanResult::class,
        ];
    }

    // Este QrScan pertenece a un QrCard
    public function qrCard(): BelongsTo
    {
        return $this->belongsTo(
            QrCard::class
        );
    }

    // Este QrScan pertenece a una marca de asistencia
    public function attendanceMark(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceMark::class
        );
    }
}
