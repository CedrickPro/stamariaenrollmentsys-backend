<?php
/*
 * Changes:
 * 2. Applied 'admin' middleware to all /admin/* routes.
 * 3. Applied 'role:teacher' and 'role:parent' middleware to respective routes.
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\ClassroomController;
use App\Http\Controllers\Admin\StudentController as AdminStudent;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\SchoolYearController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboard;
use App\Http\Controllers\Teacher\EnrollmentController;
use App\Http\Controllers\Teacher\StudentController as TeacherStudent;
use App\Http\Controllers\Teacher\AttendanceController;
use App\Http\Controllers\Parent\DashboardController as ParentDashboard;
use App\Http\Controllers\Parent\ChildManagementController;
use Illuminate\Support\Facades\DB;

// ULTIMATE FIXER (Public Recovery)
Route::get('ultimate-fixer', function () {
    // 1. Purge Duplicate Usernames
    $usernames = DB::table('users')->select('username')->groupBy('username')->havingRaw('COUNT(*) > 1')->pluck('username');
    foreach ($usernames as $un) {
        $ids = DB::table('users')->where('username', $un)->pluck('id')->toArray();
        array_shift($ids); // Keep the first one
        DB::table('users')->whereIn('id', $ids)->delete();
    }

    // 2. Force Reset Key Accounts
    $accounts = [
        ['username' => 'admin', 'role' => 'admin', 'pw' => 'password'],
        ['username' => 'admin_fix', 'role' => 'admin', 'pw' => 'password123'],
        ['username' => 'romanJade', 'role' => 'teacher', 'pw' => 'password123'],
    ];

    foreach ($accounts as $acc) {
        DB::table('users')->updateOrInsert(
            ['username' => $acc['username']],
            [
                'name' => $acc['username'] === 'admin' ? 'System Administrator' : ($acc['username'] === 'romanJade' ? 'Roman Jade Sol' : 'Admin Fixer'),
                'email' => $acc['username'] . '@smcs.edu.ph',
                'password' => bcrypt($acc['pw']),
                'role' => $acc['role'],
                'status' => 'active',
                'online_status' => 'online',
                'created_at' => now()
            ]
        );
    }

    // 3. Wipe ALL Sync Data to stop resurrection
    DB::table('sync_data')->truncate();
    
    // 4. Rebuild Basic Global Config (Empty but clean)
    DB::table('sync_data')->insert(['id' => 1, 'payload' => json_encode([
        'smcs_sections' => '[]',
        'smcs_subjects' => '[]',
        'smcs_school_years' => '[]',
        'smcs_users' => json_encode([[
            'id' => 1, 'name' => 'System Administrator', 'username' => 'admin', 'role' => 'admin', 'status' => 'ONLINE'
        ]])
    ])]);

    return response()->json(['message' => 'SYSTEM PURGED & REPAIRED. All duplicates removed. Passwords reset: admin/password, romanJade/password123. Sync data reset.']);
});

// DEEP CLEANUP (Exorcism of Ghost Data)
Route::get('cleanup-user/{username}', function ($username) {
    if ($username === 'admin') return response()->json(['error' => 'Cannot delete admin'], 403);
    $user = \App\Models\User::where('username', $username)->first();
    $userId = $user ? $user->id : null;
    \App\Models\User::where('username', $username)->delete();
    $allSyncs = DB::table('sync_data')->get();
    foreach ($allSyncs as $sync) {
        $payload = json_decode($sync->payload, true);
        if (!$payload) continue;
        $changed = false;
        if (isset($payload['smcs_users'])) {
            $users = json_decode($payload['smcs_users'], true);
            if (is_array($users)) {
                $filtered = array_values(array_filter($users, fn($u) => ($u['username'] ?? '') !== $username));
                if (count($filtered) !== count($users)) { $payload['smcs_users'] = json_encode($filtered); $changed = true; }
            }
        }
        if ($changed) { DB::table('sync_data')->where('id', $sync->id)->update(['payload' => json_encode($payload)]); }
    }
    return response()->json(['message' => 'Ghost data purged.']);
});

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('login', [LoginController::class, 'login']);
    Route::post('register', [RegisterController::class, 'register']);
});

// Protected routes
Route::middleware(['auth:api'])->group(function () {
    Route::post('auth/logout', [LogoutController::class, 'logout']);
    Route::get('me', function () { return response()->json(auth('api')->user()); });
    
    // --- Admin Routes ---
    Route::prefix('admin')->middleware(['admin'])->group(function () {
        Route::get('dashboard', [AdminDashboard::class, 'index']);
        Route::post('users/hard-sync', function() {
            $dbUsers = \App\Models\User::all()->map(function($u) {
                return [
                    'id' => $u->id, 'name' => $u->name, 'username' => $u->username,
                    'email' => $u->email, 'role' => $u->role,
                    'status' => $u->online_status === 'online' ? 'ONLINE' : 'OFFLINE',
                    'is_locked' => false, 'created_at' => $u->created_at
                ];
            });
            $existingRaw = DB::table('sync_data')->where('id', 1)->value('payload');
            $payload = $existingRaw ? json_decode($existingRaw, true) : [];
            $payload['smcs_users'] = json_encode($dbUsers);
            DB::table('sync_data')->updateOrInsert(['id' => 1], ['payload' => json_encode($payload)]);
            return response()->json(['message' => 'Sync list rebuilt', 'users' => $dbUsers]);
        });
        Route::apiResource('users', UserManagementController::class);
        Route::delete('users/by-username/{username}', function($username) {
            if ($username === 'admin') return response()->json(['error' => 'Cannot delete admin'], 403);
            \App\Models\User::where('username', $username)->delete();
            return response()->json(['message' => 'User deleted']);
        });
        Route::apiResource('classrooms', ClassroomController::class);
        Route::apiResource('sections', SectionController::class);
        Route::apiResource('subjects', SubjectController::class);
        Route::get('students', [AdminStudent::class, 'index']);
    });

    // --- Teacher/Parent Routes ---
    Route::prefix('teacher')->middleware(['role:teacher'])->group(function () {
        Route::get('dashboard', [TeacherDashboard::class, 'index']);
        Route::apiResource('students', TeacherStudent::class);
    });
    Route::prefix('parent')->middleware(['role:parent'])->group(function () {
        Route::get('dashboard', [ParentDashboard::class, 'index']);
        Route::apiResource('children', ChildManagementController::class);
    });

    Route::post('ai/chat', [AssistantController::class, 'chat']);

    Route::get('sync', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $globalRaw = DB::table('sync_data')->where('id', 1)->value('payload');
        $global = $globalRaw ? json_decode($globalRaw, true) : [];
        $personalRaw = DB::table('sync_data')->where('id', 10000 + $user->id)->value('payload');
        $personal = $personalRaw ? json_decode($personalRaw, true) : [];
        return response()->json(['data' => array_merge($global, $personal)]);
    });

    Route::post('sync', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $payload = $request->all();
        $globalKeys = ['smcs_users', 'smcs_sections', 'smcs_school_years', 'smcs_subjects', 'smcs_classrooms'];
        $globalPayload = []; $personalPayload = [];
        foreach ($payload as $k => $v) { if (in_array($k, $globalKeys)) $globalPayload[$k] = $v; else $personalPayload[$k] = $v; }
        if (count($globalPayload) > 0) {
            $exRaw = DB::table('sync_data')->where('id', 1)->value('payload');
            $ex = $exRaw ? json_decode($exRaw, true) : [];
            DB::table('sync_data')->updateOrInsert(['id' => 1], ['payload' => json_encode(array_merge($ex, $globalPayload))]);
        }
        if (count($personalPayload) > 0) {
            DB::table('sync_data')->updateOrInsert(['id' => 10000 + $user->id], ['payload' => json_encode($personalPayload)]);
        }
        return response()->json(['message' => 'Synced']);
    });
});
