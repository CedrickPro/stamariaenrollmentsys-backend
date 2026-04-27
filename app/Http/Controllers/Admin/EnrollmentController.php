<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    /**
     * Get all enrollments with relations
     */
    public function index()
    {
        return response()->json(
            Enrollment::with(['student', 'section', 'schoolYear'])->get()
        );
    }

    /**
     * Approve or Reject an enrollment
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,enrolled,dropped,rejected',
            'section_id' => 'nullable|exists:sections,id'
        ]);

        $enrollment = Enrollment::findOrFail($id);
        
        $enrollment->update([
            'status' => $request->status,
            'section_id' => $request->section_id ?? $enrollment->section_id
        ]);

        // If enrolled, update the student status too
        if ($request->status === 'enrolled') {
            $enrollment->student->update(['enrollment_status' => 'enrolled']);
        }

        return response()->json([
            'message' => 'Enrollment status updated successfully',
            'enrollment' => $enrollment->load(['student', 'section'])
        ]);
    }
    
    /**
     * Delete an enrollment record
     */
    public function destroy($id)
    {
        $enrollment = Enrollment::findOrFail($id);
        $enrollment->delete();
        return response()->json(['message' => 'Record deleted']);
    }
}
