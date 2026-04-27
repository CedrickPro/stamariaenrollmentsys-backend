<?php
namespace App\Http\Controllers\AI;

use Illuminate\Http\Request;

class AssistantController 
{
    public function chat(Request $request) {
        $message = $request->input('message');
        
        // Setup Anthropic API connection if needed
        // $apiKey = env('ANTHROPIC_API_KEY');
        
        return response()->json([
            'reply' => "I received your message: \"$message\". This is a response from the backend AI controller."
        ]);
    }
}
