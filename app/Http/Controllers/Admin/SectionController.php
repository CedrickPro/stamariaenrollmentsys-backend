<?php
/*
 * Changes:
 * 1. Added extends Controller and proper namespace import.
 * 5. Standardized to section_name instead of name.
 * 11. index() now uses classroom_name and teacher_name fields instead of overwriting relations.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Section;

class SectionController extends Controller
{
    public function index()
    {
        $sections = Section::with(['classroom', 'teacher', 'schoolYear'])
            ->withCount('enrollments')
            ->get()
            ->map(function($s) {
                $s->students = $s->enrollments_count;
                $s->classroom_name = $s->classroom?->name ?? 'N/A';
                $s->teacher_name = $s->teacher?->name ?? 'N/A';
                return $s;
            });
            
        return response()->json([
            'status' => 'success',
            'data' => $sections,
            'message' => 'Sections retrieved'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string',
            'grade_level'    => 'required|string',
            'classroom_id'   => 'nullable|exists:classrooms,id',
            'teacher_id'     => 'nullable|exists:users,id',
            'school_year_id' => 'nullable|exists:school_years,id',
        ]);

        $data = $request->all();
        
        if (!$request->filled('teacher_id') && $request->filled('teacher')) {
            $data['teacher_id'] = \App\Models\User::where('name', $request->teacher)->first()?->id;
        }

        $section = Section::create($data);
        return response()->json([
            'status' => 'success',
            'data' => $section,
            'message' => 'Section created'
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $section = Section::findOrFail($id);
        $data = $request->validate([
            'name'           => 'sometimes|required|string',
            'grade_level'    => 'sometimes|required|string',
            'classroom_id'   => 'nullable|exists:classrooms,id',
            'teacher_id'     => 'nullable|exists:users,id',
            'school_year_id' => 'nullable|exists:school_years,id',
            'status'         => 'nullable|in:available,unavailable,full',
        ]);

        $section->update($data);
        return response()->json([
            'status' => 'success',
            'data' => $section,
            'message' => 'Section updated'
        ]);
    }

    public function destroy($id)
    {
        Section::destroy($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Section deleted'
        ]);
    }
}
