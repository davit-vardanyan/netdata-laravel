<?php

declare(strict_types=1);

use DavitVardanyan\Netdata\Testing\NetdataFake;
use DavitVardanyan\NetdataLaravel\Events\NodeCameOnline;
use DavitVardanyan\NetdataLaravel\Events\NodeWentOffline;
use DavitVardanyan\NetdataLaravel\Monitoring\NodeStatusPoller;
use DavitVardanyan\NetdataLaravel\NetdataManager;
use Illuminate\Support\Facades\Event;

test('node status poller detects node going offline', function () {
    Event::fake();

    $manager = app(NetdataManager::class);
    config()->set('netdata.monitoring.nodes.dispatch_events', true);

    // First poll - online
    $fake1 = NetdataFake::create();
    $fake1->fakeNodes([
        ['nd' => 'node-1', 'nm' => 'web-01', 'os' => 'linux', 'v' => '1.44.0', 'services' => []],
    ]);
    $manager->setClient('default', $fake1->toClient());

    $poller = app(NodeStatusPoller::class);
    $poller->poll();

    Event::assertNotDispatched(NodeWentOffline::class);

    // Second poll - offline (empty version)
    $fake2 = NetdataFake::create();
    $fake2->fakeNodes([
        ['nd' => 'node-1', 'nm' => 'web-01', 'os' => 'linux', 'v' => '', 'services' => []],
    ]);
    $manager->setClient('default', $fake2->toClient());

    $poller->poll();

    Event::assertDispatched(NodeWentOffline::class);
});

test('node status poller detects node coming online', function () {
    Event::fake();

    $manager = app(NetdataManager::class);
    config()->set('netdata.monitoring.nodes.dispatch_events', true);

    // First poll - offline
    $fake1 = NetdataFake::create();
    $fake1->fakeNodes([
        ['nd' => 'node-1', 'nm' => 'web-01', 'os' => 'linux', 'v' => '', 'services' => []],
    ]);
    $manager->setClient('default', $fake1->toClient());

    $poller = app(NodeStatusPoller::class);
    $poller->poll();

    // Second poll - online
    $fake2 = NetdataFake::create();
    $fake2->fakeNodes([
        ['nd' => 'node-1', 'nm' => 'web-01', 'os' => 'linux', 'v' => '1.44.0', 'services' => []],
    ]);
    $manager->setClient('default', $fake2->toClient());

    $poller->poll();

    Event::assertDispatched(NodeCameOnline::class);
});

test('node status poller does not dispatch when status unchanged', function () {
    Event::fake();

    $manager = app(NetdataManager::class);
    config()->set('netdata.monitoring.nodes.dispatch_events', true);

    $fake = NetdataFake::create();
    $fake->fakeNodes([
        ['nd' => 'node-1', 'nm' => 'web-01', 'os' => 'linux', 'v' => '1.44.0', 'services' => []],
    ]);
    $manager->setClient('default', $fake->toClient());

    $poller = app(NodeStatusPoller::class);
    $poller->poll();
    $poller->poll();

    Event::assertNotDispatched(NodeWentOffline::class);
    Event::assertNotDispatched(NodeCameOnline::class);
});
