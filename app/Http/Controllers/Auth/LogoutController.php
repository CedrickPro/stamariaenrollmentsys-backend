<?php
/*
 * Changes:
 * 1. Added extends Controller and proper namespace import.
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function logout()
    {
        try {
            // Get user from token explicitly
            $user = auth('api')->user();
            if ($user) {
                \App\Models\User::where('id', $user->id)->update(['online_status' => 'offline']);
            }
        } catch (\Exception $e) {
            // Log error or ignore
        }
        
        try {
            auth('api')->logout();
        } catch (\Exception $e) {
            // Ignore if token already invalid
        }

        return response()->json(['message' => 'Successfully logged out']);
    }
}
