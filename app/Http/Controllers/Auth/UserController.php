<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    public function login(Request $request): JsonResponse
    {
        $login = $request->input('email');
        $password = $request->input('password');

        $credentialsEmail = ['email' => $login, 'password' => $password];
        if ($token = Auth::attempt($credentialsEmail)) {
            return $this->respondWithToken($token);
        }

        $credentialsUsername = ['username' => $login, 'password' => $password];
        if ($token = Auth::attempt($credentialsUsername)) {
            return $this->respondWithToken($token);
        }

        return response()->json([
            'status' => 'error',
            'code' => 401,
            'message' => 'Unauthorized'
        ], 401);
    }

    public function profile(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'data' => Auth::user()
        ]);
    }

    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(Auth::refresh());
    }

    public function logout(): JsonResponse
    {
        Auth::logout();
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Successfully logged out'
        ]);
    }

    protected function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'code' => 200,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
            'profile' => Auth::user()
        ]);
    }
}
