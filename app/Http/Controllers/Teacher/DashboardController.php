<?php
/*
 * Changes:
 * 1. Added extends Controller and proper namespace import.
 */

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Section;
use App\Models\Enrollment;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 401);
            }

            $section = Section::where('teacher_id', $user->id)->first();
            $today = now()->toDateString();
            $activeYear = \App\Models\SchoolYear::where('is_active', true)->first();

            $advisoryCount = 0;
            if ($section) {
                $advisoryCount = Enrollment::where('section_id', $section->id)
                    ->where('status', 'enrolled')
                    ->count();
            }
            
            // Pending count: those in their section OR those who chose them as preferred adviser
            $pendingCount = Enrollment::where('status', 'pending')
                ->where(function($q) use ($user, $section) {
                    $q->whereHas('student', function($sq) use ($user) {
                        $sq->where('preferred_adviser', $user->name)
                          ->orWhere('preferred_adviser', 'teacher:' . $user->id);
                    });
                    if ($section) {
                        $q->orWhere('section_id', $section->id);
                    }
                })
                ->count();

            $attendanceStats = [
                'present' => 0,
                'absent' => 0,
                'late' => 0
            ];

            if ($section) {
                // Ensure Attendance model exists and table exists
                try {
                    $attendanceStats['present'] = \App\Models\Attendance::where('section_id', $section->id)
                        ->where('date', $today)
                        ->where('status', 'present')
                        ->count();
                    $attendanceStats['absent'] = \App\Models\Attendance::where('section_id', $section->id)
                        ->where('date', $today)
                        ->where('status', 'absent')
                        ->count();
                    $attendanceStats['late'] = \App\Models\Attendance::where('section_id', $section->id)
                        ->where('date', $today)
                        ->where('status', 'late')
                        ->count();
                } catch (\Exception $e) {
                    // Fail silently for attendance stats if table missing
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_students' => $advisoryCount,
                    'pending_enrollments' => $pendingCount,
                    'section_assigned' => $section?->name ?? 'No Section Assigned',
                    'today_present' => $attendanceStats['present'],
                    'today_absent' => $attendanceStats['absent'],
                    'today_late' => $attendanceStats['late'],
                    'active_sy' => $activeYear?->year_label ?? ($activeYear?->year ?? '2026-2027'),
                    'is_live' => true
                ],
                'message' => 'Teacher Dashboard data retrieved'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
