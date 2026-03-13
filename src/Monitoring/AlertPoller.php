<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Monitoring;

use DavitVardanyan\Netdata\DTOs\Alert;
use DavitVardanyan\NetdataLaravel\Events\AlertTriggered;
use DavitVardanyan\NetdataLaravel\NetdataManager;
use DavitVardanyan\NetdataLaravel\Notifications\NetdataAlertNotification;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\AnonymousNotifiable;

final class AlertPoller
{
    private const string CACHE_KEY = 'netdata:alert_poller:last_seen';

    public function __construct(
        private readonly NetdataManager $manager,
        private readonly Repository $cache,
        private readonly Dispatcher $events,
    ) {}

    /**
     * Poll for new alerts and dispatch events.
     */
    public function poll(?string $connection = null): void
    {
        $connectionName = $connection ?? $this->manager->getDefaultConnection();
        $client = $this->manager->connection($connectionName);

        $alerts = $client->alerts()->list();

        /** @var array<string, string> $lastSeen */
        $lastSeen = $this->cache->get(self::CACHE_KEY.':'.$connectionName, []);

        /** @var array<string, string> $currentAlerts */
        $currentAlerts = [];

        foreach ($alerts as $alert) {
            /** @var Alert $alert */
            $key = $alert->name.':'.$alert->chart;
            $currentAlerts[$key] = $alert->status->value;

            // Skip if we've already seen this alert with the same status
            if (isset($lastSeen[$key]) && $lastSeen[$key] === $alert->status->value) {
                continue;
            }

            $event = new AlertTriggered($alert, $connectionName);

            /** @var bool $dispatchEvents */
            $dispatchEvents = config('netdata.monitoring.alerts.dispatch_events', true);
            if ($dispatchEvents) {
                $this->events->dispatch($event);
            }

            /** @var bool $notifyEnabled */
            $notifyEnabled = config('netdata.monitoring.alerts.notify.enabled', false);
            if ($notifyEnabled) {
                $this->sendNotification($event);
            }
        }

        $this->cache->put(self::CACHE_KEY.':'.$connectionName, $currentAlerts, 3600);
    }

    private function sendNotification(AlertTriggered $event): void
    {
        /** @var array<int, string> $recipients */
        $recipients = config('netdata.monitoring.alerts.notify.recipients', []);

        foreach ($recipients as $recipient) {
            (new AnonymousNotifiable)
                ->route('mail', $recipient)
                ->notify(new NetdataAlertNotification($event));
        }
    }
}
