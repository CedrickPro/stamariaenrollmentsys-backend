<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Feedback::with('user')->orderBy('created_at', 'desc');

        if ($user->role === 'teacher') {
            $query->where('recipient', 'teacher')
                  ->where(function($q) use ($user) {
                      $q->where('recipient_id', $user->id)
                        ->orWhereNull('recipient_id'); // General teacher feedback
                  });
        } elseif ($user->role === 'parent') {
            $query->where('user_id', $user->id);
        }

        $feedbacks = $query->get();

        // Calculate average rating for admin
        $avgRating = 0;
        if ($user->role === 'admin' && $feedbacks->count() > 0) {
            $avgRating = $feedbacks->whereNotNull('rating')->avg('rating') ?: 0;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'feedbacks' => $feedbacks,
                'average_rating' => round($avgRating, 1)
            ],
            'message' => 'Feedbacks retrieved'
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject'      => 'required|string',
            'message'      => 'required|string',
            'rating'       => 'nullable|integer|min:1|max:5',
            'recipient'    => 'nullable|string|in:admin,teacher',
            'recipient_id' => 'nullable|exists:users,id',
        ]);

        $translation = "Auto-translated: " . $data['message'];

        $feedback = Feedback::create([
            'user_id'      => $request->user()->id,
            'role'         => $request->user()->role,
            'recipient'    => $data['recipient'] ?? 'admin',
            'recipient_id' => $data['recipient_id'],
            'subject'      => $data['subject'],
            'message'      => $data['message'],
            'rating'       => $data['rating'] ?? 0,
            'translation'  => $translation,
            'status'       => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $feedback,
            'message' => 'Feedback submitted successfully'
        ], 201);
    }

    public function reply(Request $request, $id)
    {
        $request->validate(['reply' => 'required|string']);
        $feedback = Feedback::findOrFail($id);
        
        $feedback->update([
            'reply' => $request->reply,
            'replied_at' => now(),
            'status' => 'resolved',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $feedback,
            'message' => 'Reply sent'
        ]);
    }
}
