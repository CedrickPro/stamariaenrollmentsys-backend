<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with(['enrollments.section', 'enrollments.schoolYear'])->get();
        
        $data = $students->map(function($s) {
            $enrollment = $s->enrollments->where('status', 'enrolled')->first();
            return [
                'id' => $s->id,
                'lrn' => $s->lrn,
                'first_name' => $s->first_name,
                'middle_name' => $s->middle_name,
                'last_name' => $s->last_name,
                'gender' => $s->gender,
                'section' => $enrollment?->section?->name ?? 'Unassigned',
                'grade' => $enrollment?->section?->grade_level ?? 'N/A',
                'school_year' => $enrollment?->schoolYear?->year_label ?? 'N/A',
            ];
        });

        return response()->json($data);
    }
}
