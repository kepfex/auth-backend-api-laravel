<?php

namespace App\Services\Auth;

use App\Models\User;

class AvailableProfilesService
{
    public function forUser(User $user): array
    {
        $person = $user->person;

        if (!$person || !$person->is_active || $person->trashed()) {
            return [];
        }

        $person->loadMissing(['employee', 'student', 'guardian']);
        $profiles = [];

        if ($person->employee?->is_active) {
            $profiles[] = [
                'type' => 'employee',
                'entity_id' => $person->employee->id,
                'code' => $person->employee->employee_code,
            ];
        }

        // Los perfiles de estudiante requieren una relación Student existente.
        // La autorización para actuar sobre datos de un año concreto se revisará
        // por separado en 7J-A4.1 (no se infiere de esta lista).
        if ($person->student !== null) {
            $profiles[] = [
                'type' => 'student',
                'entity_id' => $person->student->id,
                'code' => $person->student->student_code,
            ];
        }

        if ($person->guardian?->is_active) {
            $profiles[] = [
                'type' => 'guardian',
                'entity_id' => $person->guardian->id,
                'code' => $person->guardian->guardian_code,
            ];
        }

        return $profiles;
    }
}
