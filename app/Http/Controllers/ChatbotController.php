<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class ChatbotController extends Controller
{
    /**
     * Muestra la vista principal del chatbot
     */
    public function index()
    {
        return view('usuarios.chatbot');
    }

    /**
     * Envía el mensaje del usuario a OpenRouter (DeepSeek gratuito)
     */
    public function enviar(Request $request)
    {
        $mensaje = $request->input('mensaje');

        if (!$mensaje) {
            return response()->json(['error' => 'Mensaje vacío.'], 400);
        }

        try {
            // Cliente HTTP configurado para OpenRouter
            $client = new Client([
                'base_uri' => 'https://openrouter.ai/api/v1/',
                'timeout'  => 15.0,
            ]);

            // Llamada a la API OpenRouter con el modelo DeepSeek
            $response = $client->post('chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('DEEPSEEK_API_KEY'),
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => 'deepseek/deepseek-chat',
                    'messages' => [
                        ['role' => 'system', 'content' => 'Eres un asistente útil para PRO AUDIO.'],
                        ['role' => 'user', 'content' => $mensaje],
                    ],
                ],
            ]);

            // Decodificar respuesta JSON
            $data = json_decode($response->getBody(), true);
            $respuesta = $data['choices'][0]['message']['content'] ?? 'No se recibió respuesta.';

            return response()->json(['respuesta' => $respuesta]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al conectar con el chatbot: ' . $e->getMessage()
            ], 500);
        }
    }
}
