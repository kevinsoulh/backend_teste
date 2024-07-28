<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            if($request->wantsJson()) {

                $request->validate([
                    'email' => 'required|email',
                    'password' => 'required',
                ]);

                $user = User::whereEmail($request->email)->first();

                if (! $user || ! Hash::check($request->password, $user->password)) {
                    throw ValidationException::withMessages([
                        'email' => ['Credenciais incorretas.'],
                    ]);
                }

                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json(['access_token' => $token, 'token_type' => 'Bearer']);
            }
        } catch(\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json(['message' => 'Deslogado com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

