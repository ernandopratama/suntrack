<?php

namespace App\Contracts\Notification;

/**
 * Interface NotificationDriverInterface
 *
 * Defines the contract for all Notification Drivers in SunTrack (WhatsApp, Email, In-App, Webhook).
 * Per Refinement #7, each notification must support standardized metadata to allow seamless
 * transition from Log Mode (Sprint 7) to active Gateway Integrations (Sprint 8+) without data structure changes.
 */
interface NotificationDriverInterface
{
    /**
     * Send a notification to the specified recipient.
     *
     * @param  string  $recipient  Target address (WhatsApp phone number, Email address, User UUID, or Webhook URL).
     * @param  string  $message  Main message body or template content.
     * @param  array<string, mixed>  $metadata  Additional structured metadata required per Refinement #7:
     *                                          - 'channel': string ('whatsapp' | 'email' | 'in_app' | 'webhook')
     *                                          - 'subject': string (Subject line or title)
     *                                          - 'status': string ('pending' | 'sent' | 'failed')
     *                                          - 'sent_at': string (ISO 8601 timestamp)
     *                                          - 'related_entity': string (e.g., 'Promotion', 'Campaign', 'SecureLink')
     *                                          - 'related_entity_id': string|int
     * @return bool True if dispatch or log succeeded, false otherwise.
     */
    public function send(string $recipient, string $message, array $metadata = []): bool;

    /**
     * Get the identifier name of the driver (e.g., 'whatsapp', 'email', 'in_app', 'webhook').
     */
    public function getDriverName(): string;
}
