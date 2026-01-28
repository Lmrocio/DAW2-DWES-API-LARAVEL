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

    /**
     * @OA\Post(
     *     path="/auth/register",
     *     tags={"Autenticación"},
     *     summary="Registrar un nuevo usuario",
     *     description="Crea una nueva cuenta de usuario y devuelve un token de autenticación",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="name", type="string", example="Juan Pérez"),
     *             @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuario registrado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string")
     *             ),
     *             @OA\Property(property="token", type="string", example="1|abcdefghijklmnopqrstuvwxyz")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     tags={"Autenticación"},
     *     summary="Iniciar sesión",
     *     description="Autentica un usuario con email y contraseña, retorna un token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@demo.local"),
     *             @OA\Property(property="password", type="string", format="password", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login exitoso",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string")
     *             ),
     *             @OA\Property(property="token", type="string", example="1|abcdefghijklmnopqrstuvwxyz")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Credenciales inválidas"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
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
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     tags={"Autenticación"},
     *     summary="Cerrar sesión",
     *     description="Invalida el token de autenticación actual",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Sesión cerrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Sesión cerrada con éxito")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        //Logout de usuario, para ello eliminamos el token actual
        $request->user()->currentAccessToken()->delete();
        //devolvemos  OK
        return response()->json(['message' => 'Sesión cerrada con éxito'],200);
    }

    /**
     * @OA\Get(
     *     path="/auth/me",
     *     tags={"Autenticación"},
     *     summary="Obtener datos del usuario autenticado",
     *     description="Devuelve la información del usuario actual autenticado",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Datos del usuario obtenidos",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Admin User"),
     *             @OA\Property(property="email", type="string", example="admin@demo.local")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        //Devolvemos los datos del usuario autenticado
        return response()->json($request->user(),200);
    }

    /**
     * @OA\Post(
     *     path="/auth/refresh",
     *     tags={"Autenticación"},
     *     summary="Refrescar token de autenticación",
     *     description="Invalida el token actual y genera uno nuevo",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token refrescado",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="2|newtoken123456789")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
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
