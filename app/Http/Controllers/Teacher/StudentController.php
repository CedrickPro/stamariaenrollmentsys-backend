<?php
/*
 * Changes:
 * 1. Added extends Controller and proper namespace import.
 */

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Section;

class StudentController extends Controller
{
    public function index() 
    { 
        $section = Section::where('teacher_id', auth('api')->id())->first();
        
        if (!$section) {
            return response()->json([]);
        }

        $students = Student::whereHas('enrollments', function($query) use ($section) {
            $query->where('section_id', $section->id);
        })->get();

        return response()->json($students); 
    }

    public function store(Request $request) 
    { 
        $validated = $request->validate([
            'lrn' => 'required|string|unique:students,lrn',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:255',
            'gender' => 'required|string',
            'birth_date' => 'required|date',
            'grade_level' => 'required|string',
        ]);

        return response()->json(Student::create($validated)); 
    }

    public function show($id) 
    { 
        return response()->json(Student::findOrFail($id)); 
    }

    public function update(Request $request, $id) 
    { 
        $student = Student::findOrFail($id); 
        
        $validated = $request->validate([
            'lrn' => 'required|string|unique:students,lrn,' . $id,
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:255',
            'gender' => 'required|string',
            'birth_date' => 'required|date',
            'grade_level' => 'required|string',
        ]);

        $student->update($validated); 
        return response()->json($student); 
    }

    public function destroy($id) 
    { 
        Student::destroy($id); 
        return response()->json(['message' => 'Deleted']); 
    }

    public function medical(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        $data = $request->validate([
            'weight' => 'required|numeric',
            'height' => 'required|numeric',
            'medical_remarks' => 'nullable|string',
        ]);

        $medical = \App\Models\MedicalInfo::updateOrCreate(
            ['student_id' => $student->id],
            $data
        );

        return response()->json($medical);
    }

    public function grades($id)
    {
        $student = Student::findOrFail($id);
        $grades = \App\Models\Grade::where('student_id', $student->id)
            ->with('subject')
            ->get();
        return response()->json($grades);
    }

    public function saveGrades(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        $request->validate([
            'grades' => 'required|array',
            'grades.*.subject_id' => 'required|exists:subjects,id',
            'grades.*.q1' => 'nullable|integer|min:0|max:100',
            'grades.*.q2' => 'nullable|integer|min:0|max:100',
            'grades.*.q3' => 'nullable|integer|min:0|max:100',
            'grades.*.q4' => 'nullable|integer|min:0|max:100',
            'grades.*.final_rating' => 'nullable|integer|min:0|max:100',
        ]);

        foreach ($request->grades as $item) {
            \App\Models\Grade::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'subject_id' => $item['subject_id'],
                    'school_year_id' => \App\Models\SchoolYear::where('is_active', true)->first()?->id,
                ],
                [
                    'q1' => $item['q1'] ?? null,
                    'q2' => $item['q2'] ?? null,
                    'q3' => $item['q3'] ?? null,
                    'q4' => $item['q4'] ?? null,
                    'final_rating' => $item['final_rating'] ?? null,
                ]
            );
        }

        return response()->json(['message' => 'Grades saved successfully']);
    }
}
