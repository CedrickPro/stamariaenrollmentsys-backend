<?php
/*
 * Changes:
 * 1. Added extends Controller and proper namespace import.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        return response()->json(Subject::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject_name' => 'required|string',
            'subject_code' => 'required|string|unique:subjects,code',
            'grade_level'  => 'required|string',
            'units'        => 'nullable|integer',
            'category'     => 'nullable|in:Core,MATATAG,Elective',
        ]);

        return response()->json(Subject::create([
            'name'        => $data['subject_name'],
            'code'        => $data['subject_code'],
            'grade_level' => $data['grade_level'],
            'units'       => $data['units'] ?? 3,
            'category'    => $data['category'] ?? 'Core',
        ]), 201);
    }

    public function show($id)
    {
        return response()->json(Subject::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);
        $data = $request->validate([
            'subject_name' => 'sometimes|required|string',
            'subject_code' => 'sometimes|required|string|unique:subjects,code,' . $id,
            'grade_level'  => 'sometimes|required|string',
            'units'        => 'nullable|integer',
            'category'     => 'nullable|in:Core,MATATAG,Elective',
        ]);

        $updateData = [];
        if (isset($data['subject_name'])) $updateData['name'] = $data['subject_name'];
        if (isset($data['subject_code'])) $updateData['code'] = $data['subject_code'];
        if (isset($data['grade_level'])) $updateData['grade_level'] = $data['grade_level'];
        if (isset($data['units'])) $updateData['units'] = $data['units'];
        if (isset($data['category'])) $updateData['category'] = $data['category'];

        $subject->update($updateData);
        return response()->json($subject);
    }

    public function destroy($id)
    {
        Subject::destroy($id);
        return response()->json(['message' => 'Subject deleted successfully']);
    }
}
