<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\SchoolYear;

class ChildManagementController extends Controller
{
    public function index()
    {
        $user = auth('api')->user();
        $children = Student::with(['enrollments.section', 'enrollments.schoolYear'])
            ->where('parent_id', $user->id)
            ->get();
        return response()->json([
            'status' => 'success',
            'data' => $children,
            'message' => 'Children list retrieved'
        ]);
    }

    public function store(Request $request)
    {
        $user = auth('api')->user();

        $rules = [
            'lrn'                => 'required|string|size:12|unique:students,lrn',
            'middle_name'        => 'nullable|string',
            'suffix'             => 'nullable|string',
            'birth_date'         => 'sometimes|nullable|date',
            'birth_place'        => 'sometimes|nullable|string',
            'gender'             => 'sometimes|nullable|in:Male,Female',
            'religion'           => 'sometimes|nullable|string',
            'mother_tongue'      => 'sometimes|nullable|string',
            'barangay'           => 'sometimes|nullable|string',
            'city'               => 'sometimes|nullable|string',
            'province'           => 'sometimes|nullable|string',
            'grade_level'        => 'required|string',
            'preferred_adviser'  => 'nullable|string',
            'previous_average'   => 'nullable|numeric',
        ];

        if ($request->has('name')) {
            $rules['name'] = 'required|string';
        } else {
            $rules['first_name'] = 'required|string';
            $rules['last_name'] = 'required|string';
        }

        $data = $request->validate($rules);

        if ($request->has('name')) {
            $parts = explode(' ', $data['name'], 2);
            $data['first_name'] = $parts[0];
            $data['last_name'] = $parts[1] ?? '';
            unset($data['name']);
        }

        $data['parent_id'] = $user->id;
        $data['status'] = 'pending';

        try {
            $student = Student::create($data);

            // Handle "Connection" to Teacher/Section
            $sectionId = null;
            $adviser = $request->preferred_adviser;
            
            if ($adviser && str_contains($adviser, ':')) {
                [$type, $id] = explode(':', $adviser);
                if ($type === 'section') {
                    $sectionId = $id;
                } elseif ($type === 'teacher') {
                    // Find a section for this teacher and grade level if exists
                    $section = \App\Models\Section::where('teacher_id', $id)
                        ->where('grade_level', $request->grade_level)
                        ->first();
                    $sectionId = $section?->id;
                }
            }

            // Auto-create a pending enrollment for the active SY
            $activeYear = SchoolYear::where('is_active', true)->first();
            if ($activeYear) {
                Enrollment::create([
                    'student_id'     => $student->id,
                    'school_year_id' => $activeYear->id,
                    'section_id'     => $sectionId,
                    'status'         => 'pending',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'data' => $student->load('enrollments'),
                'message' => 'Child registered and connected successfully'
            ], 201);

        } catch (\Exception $e) {
            \Log::error("Child Registration Failed: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to register child: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = auth('api')->user();
        $student = Student::where('parent_id', $user->id)->findOrFail($id);

        $rules = [
            'lrn'                => 'sometimes|required|string|size:12|unique:students,lrn,' . $id,
            'middle_name'        => 'nullable|string',
            'suffix'             => 'nullable|string',
            'birth_date'         => 'sometimes|required|date',
            'birth_place'        => 'sometimes|required|string',
            'gender'             => 'sometimes|required|in:Male,Female',
            'religion'           => 'sometimes|required|string',
            'mother_tongue'      => 'sometimes|required|string',
            'barangay'           => 'sometimes|required|string',
            'city'               => 'sometimes|required|string',
            'province'           => 'sometimes|required|string',
            'grade_level'        => 'sometimes|required|string',
            'preferred_adviser'  => 'nullable|string',
            'previous_average'   => 'nullable|numeric',
        ];

        if ($request->has('name')) {
            $rules['name'] = 'sometimes|required|string';
        } else {
            $rules['first_name'] = 'sometimes|required|string';
            $rules['last_name'] = 'sometimes|required|string';
        }

        $data = $request->validate($rules);

        if ($request->has('name')) {
            $parts = explode(' ', $data['name'], 2);
            $data['first_name'] = $parts[0];
            $data['last_name'] = $parts[1] ?? '';
            unset($data['name']);
        }

        $student->update($data);
        return response()->json([
            'status' => 'success',
            'data' => $student,
            'message' => 'Child information updated'
        ]);
    }

    public function attendance($id)
    {
        $user = auth('api')->user();
        $student = Student::where('parent_id', $user->id)->findOrFail($id);
        
        $attendance = \App\Models\Attendance::where('student_id', $student->id)
            ->orderBy('date', 'desc')
            ->get();
            
        return response()->json([
            'status' => 'success',
            'data' => $attendance,
            'message' => 'Attendance records retrieved'
        ]);
    }

    public function saveMedical(Request $request, $id)
    {
        $user = auth('api')->user();
        $student = Student::where('parent_id', $user->id)->findOrFail($id);
        
        $data = $request->validate([
            'weight' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'pwd_id' => 'nullable|boolean',
            'pwd_details' => 'nullable|string',
            'medical_remarks' => 'nullable|string',
        ]);

        $medical = \App\Models\MedicalInfo::updateOrCreate(
            ['student_id' => $student->id],
            $data
        );

        return response()->json([
            'status' => 'success',
            'data' => $medical,
            'message' => 'Medical information saved'
        ]);
    }

    public function show($id)
    {
        $user = auth('api')->user();
        $student = Student::where('parent_id', $user->id)->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $student->load(['enrollments.section', 'enrollments.schoolYear', 'medicalInfo', 'grades.subject']),
            'message' => 'Child details retrieved'
        ]);
    }

    public function grades($id)
    {
        $user = auth('api')->user();
        $student = Student::where('parent_id', $user->id)->findOrFail($id);
        $grades = \App\Models\Grade::where('student_id', $student->id)
            ->with('subject:id,name,units')
            ->get();
        return response()->json([
            'status' => 'success',
            'data'   => $grades,
            'message' => 'Grades retrieved'
        ]);
    }

    public function destroy($id)
    {
        $user = auth('api')->user();
        $student = Student::where('parent_id', $user->id)->findOrFail($id);
        $student->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Child record deleted'
        ]);
    }
}
