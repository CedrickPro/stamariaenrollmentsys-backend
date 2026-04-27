<?php
/*
 * Changes:
 * 1. Added extends Controller and proper namespace import.
 * 4. Implemented store() to save multiple attendance records and show($date) to retrieve them.
 */

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Section;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $teacher = auth('api')->user();
        $section = Section::where('teacher_id', $teacher->id)->first();

        if (!$section) {
            return response()->json([]);
        }

        $query = Attendance::where('section_id', $section->id);

        if ($request->has('month')) {
            $monthNum = date('m', strtotime($request->month));
            $query->whereMonth('date', $monthNum);
        }

        if ($request->has('year')) {
            $query->whereYear('date', $request->year);
        }

        $records = $query->with('student:id,lrn,first_name,last_name')->get();
        return response()->json($records);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*.student_id' => 'required|exists:students,id',
            'attendance.*.status' => 'required|in:present,absent,late',
        ]);

        $teacher = auth('api')->user();
        $section = Section::where('teacher_id', $teacher->id)->first();

        if (!$section) {
            return response()->json(['error' => 'You are not assigned to any section.'], 403);
        }

        foreach ($request->attendance as $item) {
            Attendance::updateOrCreate(
                [
                    'student_id' => $item['student_id'],
                    'date' => $request->date,
                ],
                [
                    'section_id' => $section->id,
                    'status' => $item['status'],
                ]
            );
        }

        return response()->json(['message' => 'Attendance records saved successfully.']);
    }

    public function show($date)
    {
        $teacher = auth('api')->user();
        $section = Section::where('teacher_id', $teacher->id)->first();

        if (!$section) {
            return response()->json(['error' => 'You are not assigned to any section.'], 403);
        }

        $records = Attendance::where('section_id', $section->id)
            ->whereDate('date', $date)
            ->with('student:id,first_name,last_name')
            ->get();

        return response()->json($records);
    }
}
