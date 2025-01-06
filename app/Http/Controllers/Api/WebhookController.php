<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    private $telegramBotToken = '7624696613:AAE-7wGIuie4_Z7bMu5XAd91E7IhshvJuTg';
    private $telegramApiUrl = 'https://api.telegram.org/bot';
    private $chatId = '-1002444739996';

    public function test(Request $request)
    {
        try {
            // Get the raw XML content
            $xmlContent = $request->getContent();
            
            // Log raw XML
            Log::info('Received webhook data', ['xml' => $xmlContent]);
            
            // Parse XML
            $xml = simplexml_load_string($xmlContent);
            
            if ($xml === false) {
                Log::error('Failed to parse XML in webhook');
                return response()->json(['error' => 'Invalid XML format'], 400);
            }

            // Convert XML to array to capture all data
            $xmlArray = json_decode(json_encode($xml), true);
            
            // Format full message for Telegram
            $message = "📦 New Courier Update\n\n";
            
            // Add all attributes from root order element
            foreach ($xml->attributes() as $key => $value) {
                $message .= "• " . ucfirst($key) . ": " . $value . "\n";
            }
            
            // Add barcode if exists
            if (isset($xmlArray['barcode'])) {
                $message .= "• Barcode: " . $xmlArray['barcode'] . "\n";
            }
            
            // Add sender information if exists
            if (isset($xmlArray['sender'])) {
                $message .= "\n📫 Sender Info:\n";
                foreach ($xmlArray['sender'] as $key => $value) {
                    if (!empty($value)) {
                        $message .= "• " . ucfirst($key) . ": " . $value . "\n";
                    }
                }
            }
            
            // Add raw XML data at the end for reference
            $message .= "\n📄 Raw XML:\n<code>" . htmlspecialchars($xmlContent) . "</code>";

            // Send to Telegram
            $response = Http::get($this->telegramApiUrl . $this->telegramBotToken . '/sendMessage', [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);

            if (!$response->successful()) {
                Log::error('Failed to send message to Telegram', [
                    'response' => $response->json()
                ]);
                return response()->json(['error' => 'Failed to send to Telegram'], 500);
            }

            // Log successful webhook
            Log::info('Webhook processed successfully', [
                'data' => $xmlArray
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
                'data' => $xmlArray
            ]);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'raw_content' => $request->getContent()
            ]);
            
            return response()->json([
                'error' => 'Failed to process webhook',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
