<?php

namespace App\Http\Controllers;

use App\Models\WhatsappQueueModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppQueueController extends Controller
{
    /**
     * Add a new entry to the WhatsApp queue
     */
    public function addToQueue(Request $request)
    {
        $validated = $request->validate([
            'jamiat_id' => 'required|integer',
            'group_id' => 'required|string|max:100',
            'callback_data' => 'nullable|string|max:256',
            'recipient_type' => 'required|string|max:256',
            'to' => 'required|string|max:20', // Ensure it's valid for phone numbers
            'template_name' => 'required|string|max:100', // Renamed for consistency
            'file_url' => 'nullable|url|max:1000',
            'content' => 'required|string',
        ]);

        $queueEntry = WhatsappQueueModel::create([
            'jamiat_id' => $validated['jamiat_id'],
            'group_id' => $validated['group_id'],
            'callback_data' => $validated['callback_data'] ?? '',
            'recipient_type' => $validated['recipient_type'],
            'to' => $validated['to'],
            'template_name' => $validated['template_name'],
            'file_url' => $validated['file_url'] ?? '',
            'content' => $validated['content'],
            'status' => 0, // Pending
            'log_user' => auth()->user()->name ?? 'system', // Log the user creating the entry
        ]);

        return response()->json([
            'message' => 'Queue entry added successfully.',
            'data' => $queueEntry,
        ], 201);
    }

    /**
     * Process pending WhatsApp messages in the queue
     */
    public function processQueue()
    {
        $apiUrl = "https://graph.facebook.com/v19.0/357370407455461/messages";
        $accessToken = "EAAEqC1znq1MBOwsToIozZCB2QslimXlqLJO6xdRZC2x5PMTqKfPdZA7TtjBH6YTTh6jRS5mRV5JKoEkiQccjdGAx8kItaxeiJVzUe8fckCRZBZANu2sjzFiiKFvUAYZAwwGQza3ploD5heDHm3IduT9ZAFioRsUUaQsu8m8Ah2XimStQRMqBwCusecFJqUbesufjZBZAyZBlE6oZCfKKVZCSaiqs";

        // Fetch pending messages in batches of 100
        $pendingMessages = WhatsappQueueModel::where('status', 0)->limit(100)->get();

        foreach ($pendingMessages as $message) {
            $content = json_decode($message->content, true);

            if (!isset($content['name']) || !isset($content['language']) || !isset($content['components'])) {
                $message->update([
                    'status' => -1, // Mark as invalid
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

            try {
                $response = Http::withToken($accessToken)->post($apiUrl, $data);

                $message->update([
                    'status' => $response->successful() ? 1 : -1,
                    'response' => $response->body(),
                    'msg_id' => $response->json()['messages'][0]['id'] ?? null,
                ]);
            } catch (\Exception $e) {
                $message->update([
                    'status' => -1, // Mark as failed
                    'response' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Queue processed successfully.',
        ]);
    }
}