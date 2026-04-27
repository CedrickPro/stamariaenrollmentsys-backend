<?php
/*
 * Changes:
 * 1. Added extends Controller and proper namespace import.
 * 7. Added username to update() allowed fields with unique validation.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserManagementController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => User::all(),
            'message' => 'Users retrieved'
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string',
            'username' => 'required|string|unique:users,username',
            'email'    => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role'     => 'required|in:admin,teacher,parent',
            'contact'  => 'nullable|string',
            'gender'   => 'nullable|string',
            'profile_pic' => 'nullable|string',
        ]);
        $data['password'] = bcrypt($data['password']);
        $user = User::create($data);
        return response()->json([
            'status' => 'success',
            'data' => $user,
            'message' => 'User created'
        ], 201);
    }

    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => User::findOrFail($id),
            'message' => 'User details retrieved'
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validate([
            'name'     => 'sometimes|required|string',
            'username' => 'sometimes|required|string|unique:users,username,' . $id,
            'email'    => 'sometimes|nullable|email|unique:users,email,' . $id,
            'role'     => 'sometimes|required|in:admin,teacher,parent',
            'password' => 'sometimes|nullable|string|min:6',
            'contact'  => 'sometimes|nullable|string',
            'gender'   => 'sometimes|nullable|string',
            'profile_pic' => 'sometimes|nullable|string',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        return response()->json([
            'status' => 'success',
            'data' => $user,
            'message' => 'User updated'
        ]);
    }

    public function destroy($id)
    {
        if ($id == 1) {
            return response()->json(['status' => 'error', 'message' => 'The primary administrator cannot be deleted.'], 403);
        }
        User::destroy($id);
        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ]);
    }

    public function toggleLock($id)
    {
        if ($id == 1) {
            return response()->json(['status' => 'error', 'message' => 'The primary administrator cannot be locked.'], 403);
        }
        $user = User::findOrFail($id);
        $user->is_locked = !$user->is_locked;
        $user->status = $user->is_locked ? 'locked' : 'active';
        $user->save();
        return response()->json([
            'status' => 'success',
            'data' => $user,
            'message' => 'User status toggled'
        ]);
    }
}
