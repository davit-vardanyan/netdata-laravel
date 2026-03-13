<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Monitoring;

use DavitVardanyan\Netdata\DTOs\Node;
use DavitVardanyan\NetdataLaravel\Events\NodeCameOnline;
use DavitVardanyan\NetdataLaravel\Events\NodeWentOffline;
use DavitVardanyan\NetdataLaravel\NetdataManager;
use DavitVardanyan\NetdataLaravel\Notifications\NodeStatusNotification;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\AnonymousNotifiable;

final class NodeStatusPoller
{
    private const string CACHE_KEY = 'netdata:node_poller:last_status';

    public function __construct(
        private readonly NetdataManager $manager,
        private readonly Repository $cache,
        private readonly Dispatcher $events,
    ) {}

    /**
     * Poll for node status changes and dispatch events.
     */
    public function poll(?string $connection = null): void
    {
        $connectionName = $connection ?? $this->manager->getDefaultConnection();
        $client = $this->manager->connection($connectionName);

        $nodes = $client->nodes()->list();

        /** @var array<string, bool> $lastStatus */
        $lastStatus = $this->cache->get(self::CACHE_KEY.':'.$connectionName, []);

        /** @var array<string, bool> $currentStatus */
        $currentStatus = [];

        $now = new \DateTimeImmutable;

        foreach ($nodes as $node) {
            /** @var Node $node */
            $isOnline = ! empty($node->version);
            $currentStatus[$node->id] = $isOnline;

            // First poll — no previous state to compare
            if (! isset($lastStatus[$node->id])) {
                continue;
            }

            $wasOnline = $lastStatus[$node->id];

            if ($wasOnline && ! $isOnline) {
                $event = new NodeWentOffline($node, $connectionName, $now);

                /** @var bool $dispatchEvents */
                $dispatchEvents = config('netdata.monitoring.nodes.dispatch_events', true);
                if ($dispatchEvents) {
                    $this->events->dispatch($event);
                }

                /** @var bool $notifyEnabled */
                $notifyEnabled = config('netdata.monitoring.nodes.notify.enabled', false);
                if ($notifyEnabled) {
                    $this->sendNotification($event);
                }
            } elseif (! $wasOnline && $isOnline) {
                $event = new NodeCameOnline($node, $connectionName, $now);

                /** @var bool $dispatchEvents */
                $dispatchEvents = config('netdata.monitoring.nodes.dispatch_events', true);
                if ($dispatchEvents) {
                    $this->events->dispatch($event);
                }

                /** @var bool $notifyEnabled */
                $notifyEnabled = config('netdata.monitoring.nodes.notify.enabled', false);
                if ($notifyEnabled) {
                    $this->sendNotification($event);
                }
            }
        }

        $this->cache->put(self::CACHE_KEY.':'.$connectionName, $currentStatus, 3600);
    }

    private function sendNotification(NodeWentOffline|NodeCameOnline $event): void
    {
        /** @var array<int, string> $recipients */
        $recipients = config('netdata.monitoring.nodes.notify.recipients', []);

        foreach ($recipients as $recipient) {
            (new AnonymousNotifiable)
                ->route('mail', $recipient)
                ->notify(new NodeStatusNotification($event));
        }
    }
}
