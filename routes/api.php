<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Api\WhatsAppController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/webhook/whatsapp', [WhatsAppController::class, 'verifyWebhook']);
Route::post('/webhook/whatsapp', [WhatsAppController::class, 'handleWebhook']);

Route::get('/send-test', function () {
    $token = env('WHATSAPP_ACCESS_TOKEN');
    $phoneId = env('WHATSAPP_PHONE_ID');

    $url = "https://graph.facebook.com/v18.0/{$phoneId}/messages";

    $response = Http::withoutVerifying()
        ->withToken($token)
        ->post($url, [
            'messaging_product' => 'whatsapp',
            'to' => '905348212802',
            'type' => 'text',
            'text' => [
                'body' => 'تجربة إرسال ناجحة من السيرفر بدون Ngrok!'
            ]
        ]);

    return response()->json([
        'status' => $response->status(),
        'success' => $response->successful(),
        'response' => $response->json(),
    ]);
});