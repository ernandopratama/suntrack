<?php

namespace App\Services\Notification;

use App\Contracts\Notification\NotificationDriverInterface;
use App\Models\NotificationLog;
use App\Services\Notification\Drivers\EmailDriver;
use App\Services\Notification\Drivers\InAppDriver;
use App\Services\Notification\Drivers\WebhookDriver;
use App\Services\Notification\Drivers\WhatsAppDriver;
use InvalidArgumentException;

class NotificationService
{
    /** @var array<string, NotificationDriverInterface> */
    protected array $drivers = [];

    public function __construct()
    {
        // Register default stub drivers (Refinement #7)
        $this->registerDriver(new WhatsAppDriver);
        $this->registerDriver(new EmailDriver);
        $this->registerDriver(new InAppDriver);
        $this->registerDriver(new WebhookDriver);
    }

    /**
     * Register a new notification channel driver.
     */
    public function registerDriver(NotificationDriverInterface $driver): void
    {
        $this->drivers[strtolower($driver->getDriverName())] = $driver;
    }

    /**
     * Send a notification through a specific channel.
     *
     * @param  string  $channel  ('whatsapp', 'email', 'in_app', 'webhook')
     * @param  string  $recipient  Phone number, email address, user ID, or URL
     * @param  string  $message  Main message body
     * @param  array<string, mixed>  $metadata  Full metadata (subject, related_entity, related_entity_id, etc.)
     */
    public function send(string $channel, string $recipient, string $message, array $metadata = []): bool
    {
        $channel = strtolower(trim($channel));
        if (! isset($this->drivers[$channel])) {
            throw new InvalidArgumentException("Notification driver [{$channel}] is not registered.");
        }

        $log = NotificationLog::create([
            'type' => $channel,
            'recipient' => $recipient,
            'subject' => $metadata['subject'] ?? null,
            'body' => $message,
            'status' => 'processing',
            'attempts' => 1,
            'processing_at' => now(),
            'metadata' => $metadata,
            'notifiable_type' => isset($metadata['related_entity'], $metadata['related_entity_id'])
                && class_exists((string) $metadata['related_entity']) ? $metadata['related_entity'] : null,
            'notifiable_id' => isset($metadata['related_entity'], $metadata['related_entity_id'])
                && class_exists((string) $metadata['related_entity']) ? $metadata['related_entity_id'] : null,
        ]);

        try {
            $sent = $this->drivers[$channel]->send($recipient, $message, $metadata);
            if ($sent) {
                $log->markSent();
            } else {
                $log->markFailed('Notification driver returned false.');
            }

            return $sent;
        } catch (\Throwable $exception) {
            $log->markFailed($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * Dispatch the same message across multiple notification channels simultaneously.
     *
     * @param  array<int, string>  $channels
     * @param  array<string, mixed>  $metadata
     * @return array<string, bool> Results per channel
     */
    public function sendToMany(array $channels, string $recipient, string $message, array $metadata = []): array
    {
        $results = [];
        foreach ($channels as $channel) {
            try {
                $results[$channel] = $this->send($channel, $recipient, $message, $metadata);
            } catch (\Exception $e) {
                $results[$channel] = false;
            }
        }

        return $results;
    }

    /**
     * Helper to dispatch automated reminder alerts (Backlog item #2 & Refinement #7 foundation).
     */
    public function sendReminder(string $channel, string $recipient, string $title, string $message, string $entityType, string $entityId): bool
    {
        return $this->send($channel, $recipient, $message, [
            'subject' => $title,
            'related_entity' => $entityType,
            'related_entity_id' => $entityId,
            'reminder_type' => 'automated_deadline_alert',
        ]);
    }
}
