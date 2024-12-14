<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Prompt;

/**
 * @OA\Info(
 *     title="Prompt API",
 *     version="1.0.0"
 * )
 */

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


    /**
     * @OA\Post(
     *     path="/api/prompt/{identifier}",
     *     summary="Voer een prompt uit met gegeven parameters",
     *     tags={"Prompts"},
     *     @OA\Parameter(
     *         name="identifier",
     *         in="path",
     *         required=true,
     *         description="Identifier van de prompt die uitgevoerd moet worden",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"parameters"},
     *             @OA\Property(
     *                 property="parameters",
     *                 type="object",
     *                 description="Parameters die in zowel de prompt template als system template worden ingevuld",
     *                 example={
     *                     "tone": "enthousiaste",
     *                     "industry": "elektronica",
     *                     "product": "Smart TV"
     *                 }
     *             ),
     *             @OA\Property(
     *                 property="temperature",
     *                 type="number",
     *                 format="float",
     *                 description="ChatGPT temperature (0.0 - 1.0)",
     *                 example=0.7
     *             ),
     *             @OA\Property(
     *                 property="max_tokens",
     *                 type="integer",
     *                 description="Maximum aantal tokens in de response",
     *                 example=1000
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Succesvolle response",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="response",
     *                 type="string",
     *                 description="De gegenereerde tekst van ChatGPT",
     *                 example="Dit is een voorbeeld response van ChatGPT."
     *             ),
     *             @OA\Property(
     *                 property="prompt_id",
     *                 type="integer",
     *                 description="ID van de gebruikte prompt",
     *                 example=1
     *             ),
     *             @OA\Property(
     *                 property="system_message",
     *                 type="string",
     *                 description="De gebruikte system message (indien aanwezig)",
     *                 example="Je bent een enthousiaste copywriter die gespecialiseerd is in elektronica producten."
     *             ),
     *             @OA\Property(
     *                 property="usage",
     *                 type="object",
     *                 description="Token usage statistieken",
     *                 @OA\Property(property="prompt_tokens", type="integer", example=10),
     *                 @OA\Property(property="completion_tokens", type="integer", example=20),
     *                 @OA\Property(property="total_tokens", type="integer", example=30)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ongeldige aanvraag",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="error",
     *                         type="string",
     *                         example="Niet alle prompt parameters zijn ingevuld"
     *                     ),
     *                     @OA\Property(
     *                         property="prompt",
     *                         type="string",
     *                         example="Dit is een {missing_parameter}"
     *                     )
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="error",
     *                         type="string",
     *                         example="Niet alle system parameters zijn ingevuld"
     *                     ),
     *                     @OA\Property(
     *                         property="system_template",
     *                         type="string",
     *                         example="Je bent een {missing_parameter} copywriter"
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Prompt niet gevonden",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Prompt niet gevonden"
     *             ),
     *             @OA\Property(
     *                 property="identifier",
     *                 type="string",
     *                 example="niet-bestaande-prompt"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=503,
     *         description="OpenAI service niet beschikbaar",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="OpenAI API fout"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Service is currently unavailable"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Er is een onverwachte fout opgetreden"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Internal server error message"
     *             )
     *         )
     *     )
     * )
     */

    public function execute(Request $request, string $identifier)
    {
    try {
        // Request validatie met specifieke error messages
        $request->validate([
            'parameters' => 'required|array',
        ], [
            'parameters.required' => 'Parameters zijn verplicht',
            'parameters.array' => 'Parameters moeten als object worden aangeleverd'
        ]);

        // Probeer de prompt te vinden
        try {
            $prompt = Prompt::where('identifier', $identifier)->firstOrFail();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Prompt niet gevonden',
                'identifier' => $identifier
            ], 404);
        }

        // Vul de parameters in en check of alle placeholders zijn vervangen
        try {
            $filledPrompt = $this->fillPromptTemplate(
                $prompt->template, 
                $request->input('parameters')
            );

            $systemMessage = null;
            if ($prompt->system_template) {
                $systemMessage = $this->fillPromptTemplate(
                    $prompt->system_template,
                    $request->input('parameters')
                );
                
                // Check system template placeholders
                if (preg_match('/{[^}]+}/', $systemMessage)) {
                    return response()->json([
                        'error' => 'Niet alle system parameters zijn ingevuld',
                        'system_template' => $systemMessage
                    ], 400);
                }
            }

            // Check prompt template placeholders
            if (preg_match('/{[^}]+}/', $filledPrompt)) {
                return response()->json([
                    'error' => 'Niet alle prompt parameters zijn ingevuld',
                    'prompt' => $filledPrompt
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Fout bij het invullen van de parameters',
                'message' => $e->getMessage()
            ], 400);
        }

        // OpenAI request
        try {
            $messages = [];
            
            // Voeg system message toe als die bestaat
            if ($systemMessage) {
                $messages[] = ['role' => 'system', 'content' => $systemMessage];
            }
            
            // Voeg user message toe
            $messages[] = ['role' => 'user', 'content' => $filledPrompt];

            $result = OpenAI::chat()->create([
                'model' => 'gpt-4',
                'messages' => $messages,
                'max_tokens' => $request->input('max_tokens', 1000),
                'temperature' => $request->input('temperature', 0.7)
            ]);

            return response()->json([
                'response' => $result->choices[0]->message->content,
                'prompt_id' => $prompt->id,
                'usage' => $result->usage->toArray(), // Tokens gebruikt
                'system_message' => $systemMessage // Optioneel: voor debugging
            ], 200);

        } catch (\Exception $e) {
            // OpenAI API errors
            return response()->json([
                'error' => 'OpenAI API fout',
                'message' => $e->getMessage()
            ], 503); // Service Unavailable
        }
    } catch (\Exception $e) {
        // Onverwachte errors
        return response()->json([
            'error' => 'Er is een onverwachte fout opgetreden',
            'message' => $e->getMessage()
        ], 500);
    }
    }

    private function fillPromptTemplate(string $template, array $parameters): string
    {
        // Vervang alle placeholders met de gegeven parameters
        foreach ($parameters as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }
        
        return $template;
    }
}


