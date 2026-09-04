<?php

namespace App\Services\Notification\Drivers;

use App\Contracts\Notification\NotificationDriverInterface;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Log;

class InAppDriver implements NotificationDriverInterface
{
    public function send(string $recipient, string $message, array $metadata = []): bool
    {
        $payload = array_merge([
            'channel' => 'in_app',
            'recipient' => $recipient,
            'subject' => $metadata['subject'] ?? 'System Notification',
            'message' => $message,
            'status' => 'sent_log_mode',
            'sent_at' => now()->toIso8601String(),
            'related_entity' => $metadata['related_entity'] ?? null,
            'related_entity_id' => $metadata['related_entity_id'] ?? null,
        ], $metadata);

        Log::info(" [Notification:InApp] To User: {$recipient} | Subject: {$payload['subject']}", $payload);

        ActivityLogger::log(
            action: 'Notification:InApp',
            description: "In-App notification sent to User {$recipient} (Subject: {$payload['subject']}) [Log Mode]",
            actorType: 'System',
            actorName: 'NotificationService',
            properties: $payload
        );

        return true;
    }

    public function getDriverName(): string
    {
        return 'in_app';
    }
}
