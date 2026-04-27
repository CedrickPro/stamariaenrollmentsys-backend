<?php
namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth('api')->user();
        $children = \App\Models\Student::where('parent_id', $user->id)->get()->map(function($student) {
            $enrollment = \App\Models\Enrollment::where('student_id', $student->id)
                ->orderBy('created_at', 'desc')->first();
            
            return [
                'id' => $student->id,
                'name' => $student->name,
                'lrn' => $student->lrn,
                'grade' => $student->grade_level,
                'status' => $student->status, // or $enrollment?->status ?? 'pending'
                'section' => $enrollment?->section?->name ?? 'Not Assigned'
            ];
        });

        $pending = \App\Models\Enrollment::whereIn('student_id', $children->pluck('id'))
                   ->where('status', 'pending')->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'children' => $children,
                'children_count' => $children->count(),
                'pending_enrollments' => $pending,
            ],
            'message' => 'Parent Dashboard data retrieved'
        ]);
    }
}
