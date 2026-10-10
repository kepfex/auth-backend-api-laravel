<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\ScanAttendanceQrRequest;
use App\Http\Resources\PublicQrScanResource;
use App\Http\Resources\QrScanResource;
use App\Services\Qr\QrAttendanceScanService;
use Illuminate\Http\JsonResponse;

class AttendanceScanController extends Controller
{
    public function store(
        ScanAttendanceQrRequest $request,
        QrAttendanceScanService $scanService
    ): JsonResponse {
        $scan =
            $scanService->process(
                $request->validated()['qr']
            );

        /*
        |--------------------------------------------------------------------------
        | Siempre respondemos 201
        |--------------------------------------------------------------------------
        |
        | Incluso:
        |
        | invalid
        | rejected
        | duplicate
        |
        | porque el intento fue procesado y generó
        | un QrScan de auditoría.
        |
        | Esto simplifica muchísimo el futuro kiosco React.
        |
        */

        return (
            new PublicQrScanResource(
                $scan
            )
        )
            ->response()
            ->setStatusCode(201);
    }
}
