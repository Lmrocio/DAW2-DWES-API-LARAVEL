<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use \Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    // Guía docente: ver docs/03_controladores.md.
    //Register, Login, Logout, Me
    //Register
    public function register(Request $request): JsonResponse
    {
        //Registro de usuario
        $validated =$request->validate([
            'name' => 'required|string|max:60',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user= User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ],201);
    }

    //Login de usuario
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Verificar credenciales, primero buscamos el usuario por email
        $user = User::where('email', $credentials['email'])->first();
        // Luego verificamos la contraseña
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ],200);
    }

    //Logout de usuario
    public function logout(Request $request): JsonResponse
    {
        //Logout de usuario, para ello eliminamos el token actual
        $request->user()->currentAccessToken()->delete();
        //devolvemos  OK
        return response()->json(['message' => 'Sesión cerrada con éxito'],200);
    }

    //Me
    public function me(Request $request): JsonResponse
    {
        //Devolvemos los datos del usuario autenticado
        return response()->json($request->user(),200);
    }

    //Refrescar un token (opcional)
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        // Eliminar el token actual
        $request->user()->currentAccessToken()->delete();

        // Crear un nuevo token
        $newToken = $user->createToken('api-token')->plainTextToken;

        return response()->json(['token' => $newToken], 200);
    }

}
