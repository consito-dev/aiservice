<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAIController extends Controller
{
    public function generateText(Request $request)
    {
        $system = $request->input('system'); // system instruction: bv. jij bent een behulpzame assistent
        $prompt = $request->input('prompt');

        $result = OpenAI::chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => $system ? $system : ''],
                ['role' => 'user', 'content' => $prompt ? $prompt : 'geef 10 voorbeelden van vette autos'],
            ],
            'max_tokens' => 1000,
            'temperature' => $request->input('temperature') ? $request->input('temperature') : 0.7
        ]);

        return response()->json([
            'generated_text' => $result['choices'][0]['message']['content']
        ]);
    }
}


