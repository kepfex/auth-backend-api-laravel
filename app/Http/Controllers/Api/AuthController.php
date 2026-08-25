<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Función para registrar un nuevo usuario
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Usuario registrado correctamente',
            'user' => $user
        ], 201);
    }

    // Función para autenticar al usuario y generar un token JWT
    public function login(Request $request)
    {
        $credentials = $request->only(['email', 'password']);

        $token = Auth::attempt($credentials);

        if (!$token) {
            return response()->json([
                'error' => 'Credenciales inválidas'
            ], 401);
        }

        return $this->respondWithToken($token);
    }

    // Función que devuelve los datos del usuario autenticado
    public function me ()
    {
        return response()->json(Auth::user());
    }

    // Función para cerrar sesión y invalidar el token JWT
    public function logout()
    {
        Auth::logout();

        return response()->json([
            'message' => 'Usuario desconectado correctamente'
        ]);
    }

    // Función para refrescar el token JWT
    public function refresh()
    {
        return $this->respondWithToken(Auth::refresh());
    }

    // Función privada para formatear la respuesta del token
    protected function respondWithToken(string $token)
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
            'user'       => Auth::user(),
        ]);
    }
}
