<?php
/*
 * Changes:
 * 1. Added extends Controller and proper namespace import.
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'role' => 'required|string|in:admin,teacher,parent',
        ]);

        // Find user by username (case-insensitive)
        $user = \App\Models\User::where('username', $credentials['username'])->first();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Use the exact username from DB for attempt
        $attemptCredentials = [
            'username' => $user->username,
            'password' => $credentials['password']
        ];

        if (!$token = auth('api')->attempt($attemptCredentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        if ($user->role !== $request->role) {
            auth('api')->logout();
            return response()->json(['error' => 'Role mismatch'], 403);
        }

        // Set status to online
        $user->online_status = 'online';
        $user->save();

        return $this->respondWithToken($token);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => auth('api')->user()
        ]);
    }
}
