<?php

declare(strict_types=1);

use DavitVardanyan\Netdata\Testing\NetdataFake;
use DavitVardanyan\NetdataLaravel\Events\AlertTriggered;
use DavitVardanyan\NetdataLaravel\Monitoring\AlertPoller;
use DavitVardanyan\NetdataLaravel\NetdataManager;
use Illuminate\Support\Facades\Event;

test('alert poller dispatches event for new alerts', function () {
    Event::fake();

    $fake = NetdataFake::create();
    $fake->fakeAlerts(['alerts' => [
        ['name' => 'cpu_high', 'chart' => 'system.cpu', 'status' => 'critical', 'value' => 95.5, 'units' => '%', 'info' => 'High CPU', 'last_status_change' => 1700000000],
    ]]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    config()->set('netdata.monitoring.alerts.dispatch_events', true);
    config()->set('netdata.monitoring.alerts.notify.enabled', false);

    $poller = app(AlertPoller::class);
    $poller->poll();

    Event::assertDispatched(AlertTriggered::class);
});

test('alert poller does not dispatch duplicate alerts', function () {
    Event::fake();

    $fake = NetdataFake::create();
    $fake->fakeAlerts(['alerts' => [
        ['name' => 'cpu_high', 'chart' => 'system.cpu', 'status' => 'critical', 'value' => 95.5, 'units' => '%', 'info' => 'High CPU', 'last_status_change' => 1700000000],
    ]]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    config()->set('netdata.monitoring.alerts.dispatch_events', true);

    $poller = app(AlertPoller::class);
    $poller->poll();
    $poller->poll();

    Event::assertDispatchedTimes(AlertTriggered::class, 1);
});

test('alert poller dispatches when status changes', function () {
    Event::fake();

    $manager = app(NetdataManager::class);
    config()->set('netdata.monitoring.alerts.dispatch_events', true);

    // First poll - warning
    $fake1 = NetdataFake::create();
    $fake1->fakeAlerts(['alerts' => [
        ['name' => 'cpu_high', 'chart' => 'system.cpu', 'status' => 'warning', 'value' => 80.0, 'units' => '%', 'info' => 'High CPU', 'last_status_change' => 1700000000],
    ]]);
    $manager->setClient('default', $fake1->toClient());

    $poller = app(AlertPoller::class);
    $poller->poll();

    // Second poll - critical (status changed)
    $fake2 = NetdataFake::create();
    $fake2->fakeAlerts(['alerts' => [
        ['name' => 'cpu_high', 'chart' => 'system.cpu', 'status' => 'critical', 'value' => 95.0, 'units' => '%', 'info' => 'High CPU', 'last_status_change' => 1700000100],
    ]]);
    $manager->setClient('default', $fake2->toClient());

    $poller->poll();

    Event::assertDispatchedTimes(AlertTriggered::class, 2);
});
