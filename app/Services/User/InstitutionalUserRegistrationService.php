<?php

namespace App\Services\User;

use App\Models\Employee;
use App\Models\Guardian;
use App\Models\InstitutionSetting;
use App\Models\Person;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InstitutionalUserRegistrationService
{
    public function create(int $personId, string $profileType, string $password): User
    {
        return DB::transaction(function () use ($personId, $profileType, $password): User {
            // Bloquea la identidad para serializar dos solicitudes simultáneas.
            $person = Person::query()->lockForUpdate()->findOrFail($personId);

            if (! $person->is_active || $person->trashed()) {
                throw ValidationException::withMessages(['person_id' => 'La persona debe estar activa.']);
            }

            if (User::query()->where('person_id', $person->id)->exists()) {
                throw ValidationException::withMessages(['person_id' => 'La persona ya tiene una cuenta de acceso.']);
            }

            $institution = InstitutionSetting::query()->find(InstitutionSetting::SINGLETON_ID);
            $abbreviation = strtolower(trim((string) ($institution?->abbreviation ?? '')));
            if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{0,18}[a-z0-9])?$/', $abbreviation)) {
                throw ValidationException::withMessages(['institution' => 'Configura una abreviatura institucional válida antes de generar cuentas.']);
            }

            $code = match ($profileType) {
                'employee' => $this->employeeCode($person->id),
                'student' => $this->studentCode($person->id),
                'guardian' => $this->guardianCode($person->id),
                default => throw ValidationException::withMessages(['profile_type' => 'Perfil institucional no válido.']),
            };

            // No se consulta un correo del cliente, ni el correo de contacto de Person.
            $identifier = strtolower($code) . '@' . $abbreviation . '.com';
            if (User::query()->where('email', $identifier)->exists()) {
                throw ValidationException::withMessages(['profile_type' => 'Este identificador institucional ya pertenece a otra cuenta.']);
            }

            // User utiliza cast hashed en password; no aplicar Hash::make aquí.
            return User::query()->create([
                'person_id' => $person->id,
                'name' => trim(implode(' ', array_filter([
                    $person->first_names,
                    $person->paternal_surname,
                    $person->maternal_surname,
                ]))),
                'email' => $identifier,
                'password' => $password,
                'is_active' => true,
            ])->load('person');
        }, 3);
    }

    private function employeeCode(int $personId): string
    {
        $profile = Employee::query()->where('person_id', $personId)->first();
        if (! $profile || ! $profile->is_active) {
            throw ValidationException::withMessages(['profile_type' => 'La persona no tiene un perfil de empleado activo.']);
        }
        return $this->requireCode($profile->employee_code, 'empleado', 'E');
    }

    private function studentCode(int $personId): string
    {
        $profile = Student::query()->where('person_id', $personId)->first();
        if (! $profile) {
            throw ValidationException::withMessages(['profile_type' => 'La persona no tiene un perfil de estudiante.']);
        }
        return $this->requireCode($profile->student_code, 'estudiante', 'A');
    }

    private function guardianCode(int $personId): string
    {
        $profile = Guardian::query()->where('person_id', $personId)->first();
        if (! $profile || ! $profile->is_active || $profile->trashed()) {
            throw ValidationException::withMessages(['profile_type' => 'La persona no tiene un perfil de apoderado activo.']);
        }
        return $this->requireCode($profile->guardian_code, 'apoderado', 'P');
    }

    private function requireCode(?string $code, string $profile, string $prefix): string
    {
        if (! $code || ! preg_match('/^' . $prefix . '[0-9]{7}$/i', $code)) {
            throw ValidationException::withMessages([
                'profile_type' => "El código de {$profile} no tiene el formato institucional esperado (prefijo + año + correlativo).",
            ]);
        }
        return strtoupper($code);
    }
}
