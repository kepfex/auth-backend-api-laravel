<?php

namespace App\Services\Qr;

use App\Enums\QrScanResult;
use App\Models\AttendanceMark;
use App\Models\QrCard;
use App\Models\QrScan;
use Carbon\CarbonInterface;

class QrScanLogger
{
    public function log(
        string $rawToken,
        QrScanResult $result,
        ?QrCard $qrCard = null,
        ?AttendanceMark $attendanceMark = null,
        ?string $reason = null,
        ?CarbonInterface $scannedAt = null
    ): QrScan {
        return QrScan::create([
            'qr_card_id' =>
            $qrCard?->id,

            'attendance_mark_id' =>
            $attendanceMark?->id,

            'token_fingerprint' =>
            hash(
                'sha256',
                trim($rawToken)
            ),

            'scanned_at' =>
            $scannedAt ?? now(),

            'result' =>
            $result->value,

            'reason' =>
            $reason,
        ]);
    }
}
