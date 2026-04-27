<?php
/*
 * Changes:
 * 1. Added extends Controller and proper namespace import.
 * 6. Validation for section_id in store() is now nullable.
 * 14. Return explicit error message when a teacher has no assigned section in index().
 */

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\SchoolYear;
use App\Models\Section;

class EnrollmentController extends Controller
{
    public function index(Request $request)
    {
        $user = auth('api')->user();
        $activeYear = SchoolYear::where('is_active', true)->first();

        $query = Enrollment::with(['student', 'section', 'schoolYear'])
            ->when($activeYear, fn($q) => $q->where('school_year_id', $activeYear->id));

        // Teachers see:
        // 1. Enrollments in their assigned section
        // 2. Pending enrollments in their grade level (if they have a section)
        // 3. Enrollments where they are the preferred_adviser
        if ($user->role === 'teacher') {
            $section = Section::where('teacher_id', $user->id)->first();
            $query->where(function($q) use ($user, $section) {
                // By Name (Preferred Adviser)
                $q->whereHas('student', function($sq) use ($user) {
                    $sq->where('preferred_adviser', $user->name);
                });

                // By Section/Grade
                if ($section) {
                    $q->orWhere('section_id', $section->id)
                      ->orWhere(function($sq) use ($section) {
                          $sq->whereNull('section_id')
                             ->whereHas('student', function($ssq) use ($section) {
                                 $ssq->where('grade_level', $section->grade_level);
                             });
                      });
                }
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->orderBy('created_at', 'desc')->get(),
            'message' => 'Enrollments retrieved'
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id'     => 'required|exists:students,id',
            'section_id'     => 'nullable|exists:sections,id',
            'school_year_id' => 'required|exists:school_years,id',
        ]);
        $enrollment = Enrollment::create(array_merge($data, ['status' => 'pending']));
        return response()->json([
            'status' => 'success',
            'data' => $enrollment->load(['student', 'section']),
            'message' => 'Enrollment created'
        ], 201);
    }

    public function show($id)
    {
        $enrollment = Enrollment::with(['student.medicalInfo', 'student.grades.subject', 'section', 'schoolYear'])->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $enrollment,
            'message' => 'Enrollment details retrieved'
        ]);
    }

    public function approve(Request $request, $id)
    {
        $user = auth('api')->user();
        $enrollment = Enrollment::findOrFail($id);
        
        // Automatically find the teacher's section for assignment
        $section = Section::where('teacher_id', $user->id)->first();

        $data = $request->validate([
            'section_id' => 'sometimes|nullable|exists:sections,id',
        ]);

        $updateData = [
            'status' => 'enrolled',
            'section_id' => $data['section_id'] ?? ($section?->id ?? $enrollment->section_id),
            'enrolled_at' => now()
        ];

        $enrollment->update($updateData);

        // Update the student status to 'enrolled' to reflect in both parent and teacher views
        if ($enrollment->student) {
            $enrollment->student->update(['status' => 'enrolled']);
        }

        return response()->json([
            'status' => 'success',
            'data' => $enrollment->fresh(['student', 'section', 'schoolYear']),
            'message' => 'Enrollment approved'
        ]);
    }

    public function reject($id)
    {
        $enrollment = Enrollment::findOrFail($id);
        $enrollment->update(['status' => 'rejected']);
        return response()->json([
            'status' => 'success',
            'data' => $enrollment->fresh(['student', 'section', 'schoolYear']),
            'message' => 'Enrollment rejected'
        ]);
    }
}
