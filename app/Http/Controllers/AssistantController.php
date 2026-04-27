<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AssistantController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $apiKey = env('ANTHROPIC_API_KEY');
        
        // Fallback for demo if no key or placeholder key
        if (!$apiKey || $apiKey === 'your_anthropic_api_key_here') {
            return response()->json([
                'reply' => "Hello! I am currently running in demo mode. (To enable full AI, please configure a valid ANTHROPIC_API_KEY). \n\nRegarding your question: I am designed to help with enrollment and school forms. How can I assist you further?"
            ]);
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(10)->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-3-haiku-20240307',
                'max_tokens' => 1024,
                'messages' => [
                    ['role' => 'user', 'content' => $request->message]
                ],
                'system' => "You are an AI assistant for the School Forms Digitized Report System (Sta. Maria Web System). You help Admins, Teachers, and Parents navigate the system correctly.
ADMIN: Setup school year, classrooms, sections, subjects, create accounts, assign teachers, generate SF1 and SF4.
TEACHER: Approve enrollments, take attendance, encode grades, encode BMI, generate SF2, SF5, SF8, SF9, SF10.
PARENT: Register account, create learner profile with 12-digit LRN, submit enrollment, monitor attendance.
IMPORTANT RULES:
- LRN must be exactly 12 digits
- Birth Date must match PSA Birth Certificate
- Never leave Address or Contact fields blank
- Always save before leaving any page
- PROMOTION: 75 and above = PROMOTED, below 75 = RETAINED
- BEHAVIOR RATING: AO, SO, RO, NO (DepEd Core Values: Maka-Diyos, Makatao, Makakalikasan, Makabansa)
Be friendly, patient, and helpful. Only answer questions about this school system.",
            ]);

            if ($response->failed()) {
                // Return a graceful fallback if the API fails (e.g. invalid key or quota)
                return response()->json([
                    'reply' => "I apologize, but I'm having trouble connecting to my brain right now. I can still help you with navigation! \n\nRemember: Admins handle setups, Teachers handle forms (SF2, SF5), and Parents handle enrollment."
                ]);
            }

            return response()->json([
                'reply' => $response->json()['content'][0]['text'] ?? 'No response from AI.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'reply' => "I encountered a minor technical glitch. Please try asking your question again in a moment!"
            ]);
        }
    }
}
