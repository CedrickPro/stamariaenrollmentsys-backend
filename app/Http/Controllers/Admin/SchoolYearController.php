<?php
/*
 * Changes:
 * 1. Added extends Controller and proper namespace import.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class SchoolYearController extends Controller
{
    public function index()
    {
        return response()->json(SchoolYear::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'year_label' => 'required|string',
            'year'       => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
            'is_active'  => 'nullable|boolean',
            'status'     => 'nullable|in:active,upcoming,completed',
        ]);

        // Defaults if missing
        $data['year'] = $data['year'] ?? $data['year_label'];
        $data['start_date'] = $data['start_date'] ?? date('Y-06-01'); // Default June
        $data['end_date'] = $data['end_date'] ?? date('Y-03-31', strtotime('+1 year')); // Default March next year
        $data['status'] = $data['status'] ?? 'upcoming';

        return response()->json(SchoolYear::create($data), 201);
    }

    public function activate($id)
    {
        $sy = SchoolYear::findOrFail($id);
        $sy->activate();
        return response()->json($sy->fresh());
    }

    public function destroy($id)
    {
        SchoolYear::destroy($id);
        return response()->json(['message' => 'School year deleted']);
    }
}
