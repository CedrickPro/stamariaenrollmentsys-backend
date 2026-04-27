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

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('login', [LoginController::class, 'login']);
    Route::post('register', [RegisterController::class, 'register']);
});

// Protected routes
Route::middleware(['auth:api'])->group(function () {

    Route::post('auth/logout', [LogoutController::class, 'logout']);

    // --- Current user ---
    Route::get('me', function () {
        return response()->json(auth('api')->user());
    });
    Route::put('me', function (\Illuminate\Http\Request $request) {
        $user = \App\Models\User::find(auth('api')->id());
        if (!$user) return response()->json(['error' => 'User not found'], 404);

        $data = $request->validate([
            'name' => 'sometimes|required|string',
            'email' => 'sometimes|nullable|email|unique:users,email,' . $user->id,
            'profile_pic' => 'sometimes|nullable|string', // base64
        ]);
        
        $user->update($data);
        return response()->json([
            'message' => 'Profile updated successfully', 
            'user' => $user->fresh()
        ]);
    });
    Route::put('me/password', function (\Illuminate\Http\Request $request) {
        $user = auth('api')->user();
        $request->validate(['password' => 'required|string|min:6|confirmed']);
        $user->update(['password' => bcrypt($request->password)]);
        return response()->json(['message' => 'Password updated successfully']);
    });

    // --- Shared / Global Routes ---
    Route::get('notifications', function (\Illuminate\Http\Request $request) {
        return response()->json($request->user()->notifications()->orderBy('created_at', 'desc')->get());
    });
    Route::post('notifications/{id}/read', function ($id) {
        \App\Models\Notification::where('id', $id)->where('user_id', auth()->id())->update(['is_read' => true]);
        return response()->json(['message' => 'Marked as read']);
    });

    Route::get('feedbacks', [\App\Http\Controllers\FeedbackController::class, 'index']);
    Route::post('feedbacks', [\App\Http\Controllers\FeedbackController::class, 'store']);
    Route::post('feedbacks/{id}/reply', [\App\Http\Controllers\FeedbackController::class, 'reply']);

    // --- Shared Metadata (For dropdowns, etc) ---
    Route::get('shared/teachers', function() {
        return response()->json([
            'status' => 'success',
            'data' => \App\Models\User::where('role', 'teacher')->select('id', 'name')->get()
        ]);
    });
    Route::get('shared/sections', [\App\Http\Controllers\Admin\SectionController::class, 'index']);
    Route::get('shared/school-years', [\App\Http\Controllers\Admin\SchoolYearController::class, 'index']);
    Route::get('shared/subjects', [\App\Http\Controllers\Admin\SubjectController::class, 'index']);

    // --- Admin Routes ---
    Route::prefix('admin')->middleware(['admin'])->group(function () {
        Route::get('dashboard', [AdminDashboard::class, 'index']);
        Route::post('users/hard-sync', function() {
            $dbUsers = \App\Models\User::all()->map(function($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'username' => $u->username,
                    'email' => $u->email,
                    'role' => $u->role,
                    'status' => 'OFFLINE',
                    'is_locked' => false,
                    'created_at' => $u->created_at
                ];
            });
            
            $existingRaw = \Illuminate\Support\Facades\DB::table('sync_data')->where('id', 1)->value('payload');
            $payload = $existingRaw ? json_decode($existingRaw, true) : [];
            $payload['smcs_users'] = json_encode($dbUsers);
            
            \Illuminate\Support\Facades\DB::table('sync_data')->updateOrInsert(['id' => 1], ['payload' => json_encode($payload)]);
            
            return response()->json(['message' => 'Sync list rebuilt from live database', 'users' => $dbUsers]);
        });
        Route::apiResource('users', UserManagementController::class);
        Route::delete('users/by-username/{username}', function($username) {
            if ($username === 'admin') return response()->json(['error' => 'Cannot delete admin'], 403);
            \App\Models\User::where('username', $username)->delete();
            return response()->json(['message' => 'User deleted from live database']);
        });
        Route::post('users/{id}/toggle-lock', [UserManagementController::class, 'toggleLock']);
        Route::apiResource('classrooms', ClassroomController::class);
        Route::apiResource('sections', SectionController::class);
        Route::apiResource('subjects', SubjectController::class);
        Route::get('students', [AdminStudent::class, 'index']);

        // School Years
        Route::get('school-years', [SchoolYearController::class, 'index']);
        Route::post('school-years', [SchoolYearController::class, 'store']);
        Route::post('school-years/{id}/activate', [SchoolYearController::class, 'activate']);
        Route::delete('school-years/{id}', [SchoolYearController::class, 'destroy']);
    });

    // --- Teacher Routes ---
    Route::prefix('teacher')->middleware(['role:teacher'])->group(function () {
        Route::get('dashboard', [TeacherDashboard::class, 'index']);
        Route::get('enrollments', [EnrollmentController::class, 'index']);
        Route::get('enrollments/{id}', [EnrollmentController::class, 'show']);
        Route::post('enrollments', [EnrollmentController::class, 'store']);
        Route::put('enrollments/{id}/approve', [EnrollmentController::class, 'approve']);
        Route::put('enrollments/{id}/reject', [EnrollmentController::class, 'reject']);
        Route::apiResource('students', TeacherStudent::class);
        Route::get('students/{id}/medical', [TeacherStudent::class, 'medical']);
        Route::get('students/{id}/grades', [TeacherStudent::class, 'grades']);
        Route::post('students/{id}/grades', [TeacherStudent::class, 'saveGrades']);
        Route::get('attendance', [AttendanceController::class, 'index']);
        Route::post('attendance', [AttendanceController::class, 'store']);
        Route::get('attendance/{date}', [AttendanceController::class, 'show']);
    });

    // --- Parent Routes ---
    Route::prefix('parent')->middleware(['role:parent'])->group(function () {
        Route::get('dashboard', [ParentDashboard::class, 'index']);
        Route::apiResource('children', ChildManagementController::class);
        Route::get('children/{id}/attendance', [ChildManagementController::class, 'attendance']);
        Route::put('children/{id}/medical', [ChildManagementController::class, 'saveMedical']);
    });

    Route::post('ai/chat', [AssistantController::class, 'chat']);
});

// --- Global State Sync (Auto-Sync Magic) - Outside Auth for Universal Access ---
Route::get('sync', function () {
    \Illuminate\Support\Facades\DB::statement("CREATE TABLE IF NOT EXISTS sync_data (id INT PRIMARY KEY, payload LONGTEXT)");
    $data = \Illuminate\Support\Facades\DB::table('sync_data')->where('id', 1)->value('payload');
    return response()->json(['data' => $data ? json_decode($data) : null]);
});

Route::post('sync', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\DB::statement("CREATE TABLE IF NOT EXISTS sync_data (id INT PRIMARY KEY, payload LONGTEXT)");
    $existingRaw = \Illuminate\Support\Facades\DB::table('sync_data')->where('id', 1)->value('payload');
    $existing = $existingRaw ? json_decode($existingRaw, true) : [];
    if (!is_array($existing)) $existing = [];
    
    $newData = $request->all();
    // Merge so we don't accidentally wipe out data if a new device sends partial payload
    $mergedData = array_merge($existing, $newData);
    
    \Illuminate\Support\Facades\DB::table('sync_data')->updateOrInsert(['id' => 1], ['payload' => json_encode($mergedData)]);
    return response()->json(['message' => 'Synced']);
});

// --- TEMPORARY: Public cleanup route - deletes ghost users by username ---
Route::get('cleanup-user/{username}', function ($username) {
    if ($username === 'admin') return response()->json(['error' => 'Cannot delete admin'], 403);
    $deleted = \App\Models\User::where('username', $username)->delete();
    
    // Also clean up sync data
    $existingRaw = \Illuminate\Support\Facades\DB::table('sync_data')->where('id', 1)->value('payload');
    if ($existingRaw) {
        $payload = json_decode($existingRaw, true);
        if (isset($payload['smcs_users'])) {
            $users = json_decode($payload['smcs_users'], true);
            if (is_array($users)) {
                $users = array_values(array_filter($users, fn($u) => $u['username'] !== $username));
                $payload['smcs_users'] = json_encode($users);
                \Illuminate\Support\Facades\DB::table('sync_data')->updateOrInsert(['id' => 1], ['payload' => json_encode($payload)]);
            }
        }
    }
    return response()->json(['deleted' => $deleted, 'username' => $username, 'message' => 'Ghost user removed']);
});
