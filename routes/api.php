<?php

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

// Deep Cleanup for Maintenance
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
    return response()->json(['message' => 'User purged from database and sync vaults.']);
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
        Route::apiResource('school-years', SchoolYearController::class);
        Route::post('school-years/{id}/activate', [SchoolYearController::class, 'activate']);
    });

    // --- Teacher/Parent Routes ---
    Route::get('teachers', function() {
        return response()->json(\App\Models\User::where('role', 'teacher')->get(['id', 'name']));
    });
    Route::get('sections', [SectionController::class, 'index']);

    Route::prefix('teacher')->middleware(['role:teacher'])->group(function () {
        Route::get('dashboard', [TeacherDashboard::class, 'index']);
        Route::apiResource('students', TeacherStudent::class);
        Route::apiResource('enrollments', EnrollmentController::class);
        Route::post('enrollments/{id}/approve', [EnrollmentController::class, 'approve']);
        Route::post('enrollments/{id}/reject', [EnrollmentController::class, 'reject']);
        Route::post('students/{id}/medical', [TeacherStudent::class, 'medical']);
        Route::get('students/{id}/grades', [TeacherStudent::class, 'grades']);
        Route::post('students/{id}/grades', [TeacherStudent::class, 'saveGrades']);
    });
    Route::prefix('parent')->middleware(['role:parent'])->group(function () {
        Route::get('dashboard', [ParentDashboard::class, 'index']);
        Route::apiResource('children', ChildManagementController::class);
        Route::get('children/{id}/attendance', [ChildManagementController::class, 'attendance']);
        Route::post('children/{id}/medical', [ChildManagementController::class, 'saveMedical']);
    });

    Route::post('ai/chat', [AssistantController::class, 'chat']);

    // Feedback System
    Route::get('feedbacks', [\App\Http\Controllers\FeedbackController::class, 'index']);
    Route::post('feedbacks', [\App\Http\Controllers\FeedbackController::class, 'store']);
    Route::post('feedbacks/{id}/reply', [\App\Http\Controllers\FeedbackController::class, 'reply']);

    // --- Global Sync (Cross-Device Data Sync) ---
    Route::post('sync', function (\Illuminate\Http\Request $req) {
        DB::statement("CREATE TABLE IF NOT EXISTS sync_data (
            id INT PRIMARY KEY DEFAULT 1,
            payload LONGTEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        $action = $req->input('action', 'push');

        if ($action === 'pull') {
            $row = DB::table('sync_data')->where('id', 1)->first();
            $payload = $row ? json_decode($row->payload, true) : [];
            return response()->json(['payload' => $payload]);
        }

        // push
        $incoming = $req->input('payload', []);
        $existingRaw = DB::table('sync_data')->where('id', 1)->value('payload');
        $existing = $existingRaw ? json_decode($existingRaw, true) : [];
        
        // Merge: use the version with more records for array fields
        foreach ($incoming as $k => $v) {
            if (!isset($existing[$k])) {
                $existing[$k] = $v;
            } else {
                $localArr = json_decode($existing[$k] ?? '[]', true);
                $incomingArr = json_decode($v ?? '[]', true);
                if (is_array($incomingArr) && is_array($localArr)) {
                    $existing[$k] = count($incomingArr) >= count($localArr) ? $v : $existing[$k];
                } else {
                    $existing[$k] = $v;
                }
            }
        }
        
        DB::table('sync_data')->updateOrInsert(['id' => 1], ['payload' => json_encode($existing)]);
        return response()->json(['message' => 'Synced OK']);
    });

    // Teacher attendance & grades (extra routes)
    Route::get('teacher/students/{id}/attendance', [\App\Http\Controllers\Teacher\AttendanceController::class, 'studentAttendance'])->middleware('role:teacher');
    Route::post('teacher/attendance', [\App\Http\Controllers\Teacher\AttendanceController::class, 'store'])->middleware('role:teacher');
    Route::get('teacher/reports/attendance', [\App\Http\Controllers\Teacher\AttendanceController::class, 'report'])->middleware('role:teacher');
    Route::get('parent/children/{id}/grades', [\App\Http\Controllers\Parent\ChildManagementController::class, 'grades'])->middleware('role:parent');
    Route::get('notifications', function() { return response()->json(['data' => []]); });
    Route::post('notifications/{id}/read', function($id) { return response()->json(['message' => 'ok']); });
});
