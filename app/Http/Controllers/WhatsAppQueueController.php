<?php

namespace App\Http\Controllers;


use App\Models\WhatsappQueueModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppQueueController extends Controller
{
    // Add a new entry to the queue
    public function addToQueue(Request $request)
    {
        $validated = $request->validate([
            'jamiat_id' => 'required|int|max:11',
            'group_id' => 'required|string|max:100',
            'callback_data' => 'nullable|string|max:256',
            'recipient_type' => 'required|string|max:256',
            'to' => 'required|string|max:100',
            'type' => 'required|string|max:100',
            'file_url' => 'nullable|url|max:1000',
            'content' => 'required|string',
        ]);

        $queueEntry = WhatsappQueueModel::create([
            'group_id' => $validated['group_id'],
            'callback_data' => $validated['callback_data'] ?? '',
            'recipient_type' => $validated['recipient_type'],
            'to' => $validated['to'],
            'type' => $validated['type'],
            'file_url' => $validated['file_url'] ?? '',
            'content' => $validated['content'],
            'status' => 0, // Pending
        ]);

        return response()->json([
            'message' => 'Queue entry added successfully.',
            'data' => $queueEntry,
        ], 201);
    }

    // Process pending messages in the queue
    public function processQueue()
    {
        $apiUrl = "https://graph.facebook.com/v19.0/357370407455461/messages";
        $accessToken = "EAAEqC1znq1MBOwsToIozZCB2QslimXlqLJO6xdRZC2x5PMTqKfPdZA7TtjBH6YTTh6jRS5mRV5JKoEkiQccjdGAx8kItaxeiJVzUe8fckCRZBZANu2sjzFiiKFvUAYZAwwGQza3ploD5heDHm3IduT9ZAFioRsUUaQsu8m8Ah2XimStQRMqBwCusecFJqUbesufjZBZAyZBlE6oZCfKKVZCSaiqs";

        $pendingMessages = WhatsappQueueModel::where('status', 0)->limit(100)->get();

        foreach ($pendingMessages as $message) {
            $content = json_decode($message->content, true);

            if (!isset($content['name']) || !isset($content['language']) || !isset($content['components'])) {
                $message->update([
                    'status' => -1, // Invalid
                    'response' => 'Invalid template structure',
                ]);
                continue;
            }

            $data = [
                "messaging_product" => "whatsapp",
                "to" => $message->to,
                "type" => "template",
                "template" => [
                    "name" => $content['name'],
                    "language" => ["code" => $content['language']['code']],
                    "components" => $content['components'],
                ]
            ];

            $response = Http::withToken($accessToken)->post($apiUrl, $data);

            $message->update([
                'status' => $response->successful() ? 1 : -1,
                'response' => $response->body(),
                'msg_id' => $response->json()['messages'][0]['id'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Queue processed successfully.',
        ]);
    }
}
