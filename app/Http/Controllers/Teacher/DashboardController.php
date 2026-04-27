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
        $user = auth('api')->user();
        $section = Section::where('teacher_id', $user->id)->first();
        $today = now()->toDateString();
        $activeYear = \App\Models\SchoolYear::where('is_active', true)->first();

        $advisoryCount = $section ? Enrollment::where('section_id', $section->id)->where('status', 'enrolled')->count() : 0;
        
        // Pending count: those in their section OR those who chose them as preferred adviser
        $pendingCount = Enrollment::where('status', 'pending')
            ->where(function($q) use ($user, $section) {
                $q->whereHas('student', function($sq) use ($user) {
                    $sq->where('preferred_adviser', $user->name);
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
                'active_sy' => $activeYear?->year_label ?? '2026-2027',
                'is_live' => true
            ],
            'message' => 'Teacher Dashboard data retrieved'
        ]);
    }
}
