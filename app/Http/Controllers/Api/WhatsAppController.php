<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    // دالة التحقق من Webhook
    public function verifyWebhook(Request $request)
    {
        $verifyToken = env('WHATSAPP_VERIFY_TOKEN', 'my_secure_token_123');
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode && $token && $mode === 'subscribe' && $token === $verifyToken) {
            return response($challenge, 200);
        }
        return response('Forbidden', 403);
    }

    // دالة استقبال الرسائل والرد الآلي
    public function handleWebhook(Request $request)
    {
        $message = $request->input('entry.0.changes.0.value.messages.0');

        if ($message && $message['type'] === 'text') {
            $from = $message['from']; // رقم المريض
            $text = $message['text']['body']; // النص الذي أرسله
            $name = $request->input('entry.0.changes.0.value.contacts.0.profile.name') ?? 'ضيفنا الكريم';

            Log::info("تم استلام رسالة من {$name}: {$text}");

            // نص الرد الآلي
            $replyMessage = "أهلاً بك {$name} في نظام العيادة 🏥\nلقد استلمنا رسالتك: {$text}\n\nللبدء بحجز موعد، يرجى الرد بكلمة *حجز*.";

            $token = env('WHATSAPP_ACCESS_TOKEN');
            $phoneId = env('WHATSAPP_PHONE_ID');
            $url = "https://graph.facebook.com/v18.0/{$phoneId}/messages";
            
            // إرسال الرد وتخزين استجابة Meta في متغير
            $response = Http::withoutVerifying()->withToken($token)->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => $from,
                'type' => 'text',
                'text' => [
                    'body' => $replyMessage
                ]
            ]);

            // تسجيل رد Meta في ملف اللوج لمعرفة سبب عدم الإرسال
            Log::info("Meta Reply Status: " . $response->body());
        }

        // إرسال 200 لـ Meta لتأكيد الاستلام
        return response('EVENT_RECEIVED', 200);
    }
}