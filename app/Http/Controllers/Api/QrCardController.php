<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\QrCard\IssueQrCardRequest;
use App\Http\Requests\QrCard\ReissueQrCardRequest;
use App\Http\Requests\QrCard\RevokeQrCardRequest;
use App\Http\Resources\QrCardResource;
use App\Models\QrCard;
use App\Models\Student;
use App\Services\Qr\QrCardService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class QrCardController extends Controller
{
    private const RELATIONS = [
        'issuedBy',
        'revokedBy',
    ];

    /**
     * Historial de tarjetas del estudiante
     */
    public function indexForStudent(
        Student $student
    ): AnonymousResourceCollection {
        $cards =
            $student
            ->qrCards()
            ->with(
                self::RELATIONS
            )
            ->latest('issued_at')
            ->get();

        return QrCardResource::collection(
            $cards
        );
    }

    /**
     * Tarjeta actualmente utilizable.
     */
    public function current(
        Student $student,
        QrCardService $service
    ): QrCardResource|JsonResponse {
        $qrCard =
            $service->current(
                $student
            );

        if (!$qrCard) {
            return response()->json([
                'message' =>
                'El estudiante no tiene una tarjeta QR activa.',
            ], 404);
        }

        $qrCard->load(
            self::RELATIONS
        );

        return new QrCardResource(
            $qrCard
        );
    }

    /**
     * Emitir primera/nueva tarjeta.
     */
    public function store(
        IssueQrCardRequest $request,
        Student $student,
        QrCardService $service
    ): JsonResponse {
        $data =
            $request->validated();

        $expiresAt =
            isset($data['expires_at'])
            ? CarbonImmutable::parse(
                $data['expires_at'],
                config('app.timezone')
            )
            : null;

        $qrCard =
            $service->issue(
                student: $student,

                issuedBy: $request->user('api'),

                expiresAt: $expiresAt,
            );

        $qrCard->load(
            self::RELATIONS
        );

        return (
            new QrCardResource(
                $qrCard
            )
        )
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar tarjeta.
     */
    public function show(
        QrCard $qrCard
    ): QrCardResource {
        $qrCard->load(
            self::RELATIONS
        );

        return new QrCardResource(
            $qrCard
        );
    }

    /**
     * Revocar.
     */
    public function revoke(
        RevokeQrCardRequest $request,
        QrCard $qrCard,
        QrCardService $service
    ): QrCardResource {
        $data =
            $request->validated();

        $qrCard =
            $service->revoke(
                qrCard: $qrCard,

                revokedBy: $request->user('api'),

                reason: $data['reason'],
            );

        $qrCard->load(
            self::RELATIONS
        );

        return new QrCardResource(
            $qrCard
        );
    }

    /**
     * Revocar la actual y emitir otra
     * en una sola transacción.
     */
    public function reissue(
        ReissueQrCardRequest $request,
        Student $student,
        QrCardService $service
    ): QrCardResource {
        $data =
            $request->validated();

        $expiresAt =
            isset($data['expires_at'])
            ? CarbonImmutable::parse(
                $data['expires_at'],
                config('app.timezone')
            )
            : null;

        $qrCard =
            $service->reissue(
                student: $student,

                issuedBy: $request->user('api'),

                reason: $data['reason'],

                expiresAt: $expiresAt,
            );

        $qrCard->load(
            self::RELATIONS
        );

        return new QrCardResource(
            $qrCard
        );
    }
}
