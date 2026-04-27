<?php
/*
 * Changes:
 * 1. Added extends Controller and proper namespace import.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\SchoolYear;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $yearLabel = $request->query('school_year');
        $targetYear = $yearLabel 
            ? SchoolYear::where('year_label', $yearLabel)->first()
            : SchoolYear::where('is_active', true)->first();

        $enrolledCount = $targetYear
            ? Enrollment::where('school_year_id', $targetYear->id)->where('status', 'enrolled')->count()
            : 0;

        $sectionCount = $targetYear
            ? \App\Models\Section::where('school_year_id', $targetYear->id)->count()
            : \App\Models\Section::count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'students'   => $enrolledCount,
                'teachers'   => User::where('role', 'teacher')->count(),
                'parents'    => User::where('role', 'parent')->count(),
                'feedbacks'  => \App\Models\Feedback::count(),
                'classrooms' => $sectionCount,
                'active_sy'  => $targetYear?->year_label ?? 'N/A',
            ],
            'message' => 'Admin Dashboard data retrieved'
        ]);
    }
}
