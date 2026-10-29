<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AcademicYearResource;
use App\Models\AcademicYear;
use App\Services\Auth\AvailableProfilesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\JWTGuard;

class AuthController extends Controller
{
    // Autenticación JWT: solo cuentas habilitadas.
    public function login(Request $request, AvailableProfilesService $profiles)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $token = Auth::guard('api')->attempt([
            ...$credentials,
            'is_active' => true,
        ]);

        if (!$token) {
            return response()->json(['error' => 'Credenciales inválidas'], 401);
        }

        return $this->respondWithToken($token, $profiles);
    }

    // Mantener el JSON del usuario como raíz para no romper consumidores de /me.

    public function me(AvailableProfilesService $profiles): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::guard('api')->user();

        if ($user === null) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        }

        return response()->json([
            ...$user->toArray(),
            'available_profiles' => $profiles->forUser($user),
        ]);
    }


    // Función para cerrar sesión y invalidar el token JWT
    public function logout()
    {
        Auth::logout();

        return response()->json([
            'message' => 'Sesión cerrada correctamente'
        ]);
    }

    // Función para refrescar el token JWT

    public function refresh(
        AvailableProfilesService $profiles
    ): JsonResponse {
        /** @var JWTGuard $guard */
        $guard = Auth::guard('api');

        $token = $guard->refresh();

        return $this->respondWithToken($token, $profiles);
    }

    // Respuesta unificada: token + user + año académico activo
    protected function respondWithToken(string $token, AvailableProfilesService $profiles, int $status = 200)
    {
        // Busca el año activo; si no hay ninguno, toma el más reciente por nombre
        $academicYear = AcademicYear::currentAcademicYear();

        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
            'user'       => Auth::guard('api')->user(),
            'available_profiles' => $profiles->forUser(Auth::guard('api')->user()),
            'academic_year' => $academicYear
                ? new AcademicYearResource($academicYear)
                : null,
        ], $status);
    }
}
