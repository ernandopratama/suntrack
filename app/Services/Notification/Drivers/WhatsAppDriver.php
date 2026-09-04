<?php

namespace App\Services\Notification\Drivers;

use App\Contracts\Notification\NotificationDriverInterface;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppDriver implements NotificationDriverInterface
{
    /**
     * Send a notification via live WhatsApp API Gateway with graceful fallback to Log Mode.
     */
    public function send(string $recipient, string $message, array $metadata = []): bool
    {
        $apiUrl = config('services.whatsapp.url');
        $apiKey = config('services.whatsapp.key');
        $status = 'sent_log_mode';
        $isLive = false;

        // Attempt live gateway dispatch if configured
        if (! empty($apiUrl) && ! empty($apiKey)) {
            try {
                $response = Http::withToken($apiKey)->timeout(10)->post($apiUrl, [
                    'phone' => $recipient,
                    'message' => $message,
                    'subject' => $metadata['subject'] ?? 'SunTrack Alert',
                ]);

                if ($response->successful()) {
                    $status = 'sent_gateway_live';
                    $isLive = true;
                } else {
                    $status = 'sent_log_mode_fallback';
                    Log::warning(' [Notification:WhatsApp] Live gateway returned HTTP '.$response->status().'. Falling back to Log Mode.');
                }
            } catch (\Exception $e) {
                $status = 'sent_log_mode_fallback';
                Log::warning(" [Notification:WhatsApp] Gateway connection error: {$e->getMessage()}. Falling back to Log Mode.");
            }
        }

        $payload = array_merge([
            'channel' => 'whatsapp',
            'recipient' => $recipient,
            'subject' => $metadata['subject'] ?? 'WhatsApp Notification',
            'message' => $message,
            'status' => $status,
            'is_live_gateway' => $isLive,
            'sent_at' => now()->toIso8601String(),
            'related_entity' => $metadata['related_entity'] ?? null,
            'related_entity_id' => $metadata['related_entity_id'] ?? null,
        ], $metadata);

        // Record in system log and ActivityLog
        Log::info(" [Notification:WhatsApp:{$status}] To: {$recipient} | Subject: {$payload['subject']} | Msg: {$message}", $payload);

        ActivityLogger::log(
            action: 'Notification:WhatsApp',
            description: "WhatsApp notification sent to {$recipient} (Subject: {$payload['subject']}) [".strtoupper($status).']',
            actorType: 'System',
            actorName: 'NotificationService',
            properties: $payload
        );

        return true;
    }

    public function getDriverName(): string
    {
        return 'whatsapp';
    }
}
