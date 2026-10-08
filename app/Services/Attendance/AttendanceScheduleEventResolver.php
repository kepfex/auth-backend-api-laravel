<?php

namespace App\Services\Attendance;

use App\Models\AttendanceDay;
use App\Models\AttendanceSchedule;
use App\Models\AttendanceScheduleEvent;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class AttendanceScheduleEventResolver
{
    /**
     * @return array{
     *     event: AttendanceScheduleEvent|null,
     *     reason: string|null
     * }
     */
    public function resolve(
        AttendanceSchedule $schedule,
        CarbonInterface $recordedAt,
        ?AttendanceDay $attendanceDay = null
    ): array {
        $weekday =
            $recordedAt->dayOfWeekIso;

        /*
        |--------------------------------------------------------------------------
        | Eventos esperados para ese día
        |--------------------------------------------------------------------------
        */

        $events =
            $schedule
            ->events()
            ->where(
                'day_of_week',
                $weekday
            )
            ->orderBy('sequence')
            ->get();

        if ($events->isEmpty()) {
            return [
                'event' => null,
                'reason' => 'no_events_today',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Eventos cuya ventana contiene la hora actual
        |--------------------------------------------------------------------------
        */

        $candidates =
            $events
            ->map(
                function (
                    AttendanceScheduleEvent $event
                ) use ($recordedAt) {
                    $expectedAt =
                        CarbonImmutable::parse(
                            $recordedAt->toDateString()
                                . ' '
                                . $event->expected_time,
                            $recordedAt->getTimezone()
                        );

                    $windowStartsAt =
                        $expectedAt->subMinutes(
                            $event
                                ->window_before_minutes
                        );

                    $windowEndsAt =
                        $expectedAt->addMinutes(
                            $event
                                ->window_after_minutes
                        );

                    if (
                        $recordedAt->lt(
                            $windowStartsAt
                        ) ||
                        $recordedAt->gt(
                            $windowEndsAt
                        )
                    ) {
                        return null;
                    }

                    return [
                        'event' =>
                        $event,

                        'distance' =>
                        abs(
                            $recordedAt
                                ->getTimestamp()
                                -
                                $expectedAt
                                ->getTimestamp()
                        ),
                    ];
                }
            )
            ->filter()
            ->sortBy([
                ['distance', 'asc'],
                [
                    fn($candidate) =>
                    $candidate['event']
                        ->sequence,
                    'asc',
                ],
            ])
            ->values();

        if ($candidates->isEmpty()) {
            return [
                'event' => null,
                'reason' => 'outside_window',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Si aún no existe AttendanceDay
        |--------------------------------------------------------------------------
        */

        if (!$attendanceDay) {
            return [
                'event' =>
                $candidates
                    ->first()['event'],

                'reason' =>
                null,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Eventos ya satisfechos
        |--------------------------------------------------------------------------
        */

        $registeredEventIds =
            $attendanceDay
            ->marks()
            ->whereNotNull(
                'attendance_schedule_event_id'
            )
            ->pluck(
                'attendance_schedule_event_id'
            )
            ->all();

        /*
        |--------------------------------------------------------------------------
        | Tomar candidato aún no registrado
        |--------------------------------------------------------------------------
        |
        | Esto también resuelve ventanas parcialmente superpuestas.
        |
        */

        foreach ($candidates as $candidate) {
            /** @var AttendanceScheduleEvent $event */
            $event =
                $candidate['event'];

            if (
                !in_array(
                    $event->id,
                    $registeredEventIds,
                    true
                )
            ) {
                return [
                    'event' =>
                    $event,

                    'reason' =>
                    null,
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Todos los candidatos ya fueron marcados
        |--------------------------------------------------------------------------
        */

        return [
            'event' => null,
            'reason' => 'duplicate_mark',
        ];
    }
}
