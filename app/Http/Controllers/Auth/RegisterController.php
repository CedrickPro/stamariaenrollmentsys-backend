<?php
/*
 * Changes:
 * 1. Added extends Controller and proper namespace import.
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|unique:users,username',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role'     => 'required|in:admin,teacher,parent',
            'contact'  => 'nullable|string',
            'gender'   => 'nullable|string',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'username' => $data['username'],
            'email'    => $data['email'],
            'password' => bcrypt($data['password']),
            'role'     => $data['role'],
            'contact'  => $data['contact'] ?? null,
            'gender'   => $data['gender'] ?? null,
            'status'   => 'active',
            'online_status' => 'online',
        ]);

        $token = auth('api')->login($user);

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'user'         => $user,
        ], 201);
    }
}
