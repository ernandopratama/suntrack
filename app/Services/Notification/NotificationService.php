<?php

namespace App\Services\Notification;

use App\Contracts\Notification\NotificationDriverInterface;
use App\Services\Notification\Drivers\WhatsAppDriver;
use App\Services\Notification\Drivers\EmailDriver;
use App\Services\Notification\Drivers\InAppDriver;
use App\Services\Notification\Drivers\WebhookDriver;
use InvalidArgumentException;

class NotificationService
{
    /** @var array<string, NotificationDriverInterface> */
    protected array $drivers = [];

    public function __construct()
    {
        // Register default stub drivers (Refinement #7)
        $this->registerDriver(new WhatsAppDriver());
        $this->registerDriver(new EmailDriver());
        $this->registerDriver(new InAppDriver());
        $this->registerDriver(new WebhookDriver());
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
     * @param string $channel ('whatsapp', 'email', 'in_app', 'webhook')
     * @param string $recipient Phone number, email address, user ID, or URL
     * @param string $message Main message body
     * @param array<string, mixed> $metadata Full metadata (subject, related_entity, related_entity_id, etc.)
     * @return bool
     */
    public function send(string $channel, string $recipient, string $message, array $metadata = []): bool
    {
        $channel = strtolower(trim($channel));
        if (!isset($this->drivers[$channel])) {
            throw new InvalidArgumentException("Notification driver [{$channel}] is not registered.");
        }

        return $this->drivers[$channel]->send($recipient, $message, $metadata);
    }

    /**
     * Dispatch the same message across multiple notification channels simultaneously.
     *
     * @param array<int, string> $channels
     * @param string $recipient
     * @param string $message
     * @param array<string, mixed> $metadata
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
            'subject'           => $title,
            'related_entity'    => $entityType,
            'related_entity_id' => $entityId,
            'reminder_type'     => 'automated_deadline_alert',
        ]);
    }
}
