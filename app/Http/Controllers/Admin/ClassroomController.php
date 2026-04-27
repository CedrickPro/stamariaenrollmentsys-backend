<?php
/*
 * Changes:
 * 1. Added extends Controller and proper namespace import.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => Classroom::all(),
            'message' => 'Classrooms retrieved'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'room_name' => 'required_without:name|string',
            'room_type' => 'nullable|string',
            'name'      => 'required_without:room_name|string',
            'building'  => 'nullable|string',
            'capacity'  => 'nullable|integer',
            'status'    => 'nullable|in:available,unavailable',
        ]);

        $classroom = Classroom::create([
            'name'     => $request->room_name ?? $request->name,
            'building' => $request->room_type ?? $request->building,
            'status'   => $request->status ?? 'available',
            'capacity' => $request->capacity ?? 45,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $classroom,
            'message' => 'Classroom created'
        ], 201);
    }

    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => Classroom::findOrFail($id),
            'message' => 'Classroom details retrieved'
        ]);
    }

    public function update(Request $request, $id)
    {
        $classroom = Classroom::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|required|string',
            'building' => 'nullable|string',
            'capacity' => 'nullable|integer',
            'status' => 'nullable|in:available,unavailable',
        ]);

        $classroom->update($data);
        return response()->json([
            'status' => 'success',
            'data' => $classroom,
            'message' => 'Classroom updated'
        ]);
    }

    public function destroy($id)
    {
        Classroom::destroy($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Classroom deleted successfully'
        ]);
    }
}
