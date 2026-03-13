<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Notifications;

use DavitVardanyan\NetdataLaravel\Events\AlertTriggered;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class NetdataAlertNotification extends Notification
{
    public function __construct(
        private readonly AlertTriggered $event,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        /** @var array<int, string> $channels */
        $channels = config('netdata.monitoring.alerts.notify.channels', ['mail']);

        return $channels;
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Netdata Alert: {$this->event->alert->name} [{$this->event->alert->status->value}]")
            ->line("Alert: {$this->event->alert->name}")
            ->line("Status: {$this->event->alert->status->value}")
            ->line("Chart: {$this->event->alert->chart}")
            ->line("Info: {$this->event->alert->info}")
            ->line("Connection: {$this->event->connection}");
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'alert_name' => $this->event->alert->name,
            'status' => $this->event->alert->status->value,
            'chart' => $this->event->alert->chart,
            'info' => $this->event->alert->info,
            'connection' => $this->event->connection,
        ];
    }
}
