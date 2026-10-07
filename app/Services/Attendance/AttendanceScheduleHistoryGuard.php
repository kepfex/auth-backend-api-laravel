<?php

namespace App\Services\Attendance;

use App\Models\AttendanceSchedule;
use Carbon\Carbon;
use Illuminate\Validation\Validator;

/* Una vez que:
        AttendanceSchedule
                ↓
        AttendanceDay

ya existe, no deberíamos permitir modificar sus eventos históricos. */

class AttendanceScheduleHistoryGuard
{
    public function validateUpdate(
        Validator $validator,
        AttendanceSchedule $schedule,
        array $data
    ): void {
        /*
        |--------------------------------------------------------------------------
        | ¿El horario ya fue utilizado?
        |--------------------------------------------------------------------------
        */

        $usage =
            $schedule
                ->attendanceDays()
                ->selectRaw(
                    'MIN(date) as first_date, MAX(date) as last_date'
                )
                ->first();

        if (
            !$usage ||
            !$usage->first_date
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Eventos históricos inmutables
        |--------------------------------------------------------------------------
        */

        if (
            array_key_exists(
                'events',
                $data
            )
        ) {
            $validator
                ->errors()
                ->add(
                    'events',
                    'La estructura del horario no puede modificarse porque ya posee asistencias registradas. Crea una nueva vigencia.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | No excluir días históricos cambiando valid_from
        |--------------------------------------------------------------------------
        */

        if (
            array_key_exists(
                'valid_from',
                $data
            )
        ) {
            $newValidFrom =
                Carbon::parse(
                    $data['valid_from']
                );

            $firstUsedDate =
                Carbon::parse(
                    $usage->first_date
                );

            if (
                $newValidFrom->gt(
                    $firstUsedDate
                )
            ) {
                $validator
                    ->errors()
                    ->add(
                        'valid_from',
                        'La nueva fecha de inicio excluiría asistencias históricas registradas.'
                    );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | No excluir días históricos cambiando valid_until
        |--------------------------------------------------------------------------
        */

        if (
            array_key_exists(
                'valid_until',
                $data
            )
        ) {
            $newValidUntil =
                Carbon::parse(
                    $data['valid_until']
                );

            $lastUsedDate =
                Carbon::parse(
                    $usage->last_date
                );

            if (
                $newValidUntil->lt(
                    $lastUsedDate
                )
            ) {
                $validator
                    ->errors()
                    ->add(
                        'valid_until',
                        'La nueva fecha final excluiría asistencias históricas registradas.'
                    );
            }
        }
    }
}

// La regla queda:
/* 
    Horario nunca usado
    → puede modificar eventos

    Horario usado
    → nombre ✓
    → activar/desactivar ✓
    → extender vigencia ✓
    → cambiar eventos ✗
    → excluir días históricos ✗ 
*/