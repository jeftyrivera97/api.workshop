<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use Illuminate\Http\Request;

class UserController extends Controller
{

    public function login(Request $request)
    {
        $loginUserData = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|min:8'
        ]);
        $user = User::where('email', $loginUserData['email'])->first();
        if (!$user || !Hash::check($loginUserData['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid Credentials'
            ], 401);
        }
        $token = $user->createToken($user->name . '-AuthToken')->plainTextToken;
       $expires_at = now()->addHour()->toIso8601String(); // Expira en 1 hora
        return response()->json([
            'access_token' => $token,
            'user' => $user,
            'expires_at' => $expires_at
        ]);
    }

    public function renewToken(Request $request)
    {
        $user = $request->user();
        $token = $user->createToken($user->name . '-AuthToken')->plainTextToken;
      $expires_at = now()->addHour()->toIso8601String(); // Expira en 1 hora

        return response()->json([
            'access_token' => $token,
            'user' => $user,
            'expires_at' => $expires_at
        ]);
    }

    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }
}
