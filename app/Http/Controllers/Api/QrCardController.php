<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QrCardResource;
use App\Models\Student;
use App\Services\Qr\QrCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrCardController extends Controller
{
    /**
     * Obtener el código QR de un estudiante.
     */
    public function current(
        Student $student,
        QrCardService $service
    ): QrCardResource|JsonResponse {
        $qrCard = $service->current($student);

        if (! $qrCard) {
            return response()->json([
                'message' => 'El estudiante no tiene un código QR asignado.',
            ], 404);
        }

        return new QrCardResource($qrCard);
    }

    /**
     * Activar o desactivar el código QR.
     */
    public function updateStatus(
        Request $request,
        Student $student,
        QrCardService $service
    ): QrCardResource|JsonResponse {
        $data = $request->validate([
            'is_active' => [
                'required',
                'boolean',
            ],
        ]);

        $qrCard = $service->current($student);

        if (! $qrCard) {
            return response()->json([
                'message' => 'El estudiante no tiene un código QR asignado.',
            ], 404);
        }

        $qrCard = $service->setActive(
            $qrCard,
            $data['is_active']
        );

        return new QrCardResource($qrCard);
    }
}
