<?php

declare(strict_types=1);

use DavitVardanyan\Netdata\Testing\NetdataFake;
use DavitVardanyan\NetdataLaravel\Events\AlertTriggered;
use DavitVardanyan\NetdataLaravel\Monitoring\AlertPoller;
use DavitVardanyan\NetdataLaravel\NetdataManager;
use Illuminate\Support\Facades\Event;

test('full alert poller flow: poll, cache, re-poll, detect new', function () {
    Event::fake();

    config()->set('netdata.monitoring.alerts.dispatch_events', true);
    config()->set('netdata.monitoring.alerts.notify.enabled', false);

    $manager = app(NetdataManager::class);

    // First poll - one alert
    $fake1 = NetdataFake::create();
    $fake1->fakeAlerts(['alerts' => [
        ['name' => 'cpu_high', 'chart' => 'system.cpu', 'status' => 'critical', 'value' => 95.0, 'units' => '%', 'info' => 'CPU high', 'last_status_change' => 1700000000],
    ]]);
    $manager->setClient('default', $fake1->toClient());

    $poller = app(AlertPoller::class);
    $poller->poll();

    Event::assertDispatchedTimes(AlertTriggered::class, 1);

    // Second poll - same alert (should not dispatch again)
    $fake2 = NetdataFake::create();
    $fake2->fakeAlerts(['alerts' => [
        ['name' => 'cpu_high', 'chart' => 'system.cpu', 'status' => 'critical', 'value' => 95.0, 'units' => '%', 'info' => 'CPU high', 'last_status_change' => 1700000000],
    ]]);
    $manager->setClient('default', $fake2->toClient());

    $poller->poll();

    Event::assertDispatchedTimes(AlertTriggered::class, 1); // Still 1

    // Third poll - new alert added
    $fake3 = NetdataFake::create();
    $fake3->fakeAlerts(['alerts' => [
        ['name' => 'cpu_high', 'chart' => 'system.cpu', 'status' => 'critical', 'value' => 95.0, 'units' => '%', 'info' => 'CPU high', 'last_status_change' => 1700000000],
        ['name' => 'disk_low', 'chart' => 'disk._', 'status' => 'warning', 'value' => 85.0, 'units' => '%', 'info' => 'Disk low', 'last_status_change' => 1700000100],
    ]]);
    $manager->setClient('default', $fake3->toClient());

    $poller->poll();

    Event::assertDispatchedTimes(AlertTriggered::class, 2); // 1 original + 1 new
});
