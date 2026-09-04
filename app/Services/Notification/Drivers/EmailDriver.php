<?php

namespace App\Services\Notification\Drivers;

use App\Contracts\Notification\NotificationDriverInterface;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailDriver implements NotificationDriverInterface
{
    /**
     * Send a notification via live SMTP / Mailgun with graceful fallback to Log Mode.
     */
    public function send(string $recipient, string $message, array $metadata = []): bool
    {
        $mailer = config('mail.default', 'log');
        $status = 'sent_log_mode';
        $isLive = false;

        // If a live mailer (smtp, mailgun, ses, postmark) is explicitly configured
        if ($mailer !== 'log' && $mailer !== 'array' && ! empty(config('mail.mailers.'.$mailer.'.host'))) {
            try {
                $subject = $metadata['subject'] ?? 'SunTrack Notification Email';
                Mail::raw($message, function ($mail) use ($recipient, $subject) {
                    $mail->to($recipient)->subject($subject);
                });
                $status = 'sent_gateway_live';
                $isLive = true;
            } catch (\Exception $e) {
                $status = 'sent_log_mode_fallback';
                Log::warning(" [Notification:Email] Live SMTP error: {$e->getMessage()}. Falling back to Log Mode.");
            }
        }

        $payload = array_merge([
            'channel' => 'email',
            'recipient' => $recipient,
            'subject' => $metadata['subject'] ?? 'SunTrack Notification Email',
            'message' => $message,
            'status' => $status,
            'is_live_gateway' => $isLive,
            'sent_at' => now()->toIso8601String(),
            'related_entity' => $metadata['related_entity'] ?? null,
            'related_entity_id' => $metadata['related_entity_id'] ?? null,
        ], $metadata);

        Log::info(" [Notification:Email:{$status}] To: {$recipient} | Subject: {$payload['subject']}", $payload);

        ActivityLogger::log(
            action: 'Notification:Email',
            description: "Email notification sent to {$recipient} (Subject: {$payload['subject']}) [".strtoupper($status).']',
            actorType: 'System',
            actorName: 'NotificationService',
            properties: $payload
        );

        return true;
    }

    public function getDriverName(): string
    {
        return 'email';
    }
}
