<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Notifications;

use DavitVardanyan\NetdataLaravel\Events\NodeCameOnline;
use DavitVardanyan\NetdataLaravel\Events\NodeWentOffline;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class NodeStatusNotification extends Notification
{
    public function __construct(
        private readonly NodeWentOffline|NodeCameOnline $event,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        /** @var array<int, string> $channels */
        $channels = config('netdata.monitoring.nodes.notify.channels', ['mail']);

        return $channels;
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $status = $this->event instanceof NodeWentOffline ? 'OFFLINE' : 'ONLINE';

        return (new MailMessage)
            ->subject("Netdata Node {$status}: {$this->event->node->name}")
            ->line("Node: {$this->event->node->name}")
            ->line("Status: {$status}")
            ->line("Detected at: {$this->event->detectedAt->format('Y-m-d H:i:s')}")
            ->line("Connection: {$this->event->connection}");
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        $status = $this->event instanceof NodeWentOffline ? 'offline' : 'online';

        return [
            'node_name' => $this->event->node->name,
            'status' => $status,
            'detected_at' => $this->event->detectedAt->format('Y-m-d H:i:s'),
            'connection' => $this->event->connection,
        ];
    }
}
