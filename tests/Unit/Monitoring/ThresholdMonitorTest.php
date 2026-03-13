<?php

declare(strict_types=1);

use DavitVardanyan\Netdata\Testing\NetdataFake;
use DavitVardanyan\NetdataLaravel\Events\MetricThresholdExceeded;
use DavitVardanyan\NetdataLaravel\Monitoring\ThresholdMonitor;
use DavitVardanyan\NetdataLaravel\NetdataManager;
use Illuminate\Support\Facades\Event;

test('threshold monitor dispatches event when value exceeds threshold with > operator', function () {
    Event::fake();

    config()->set('netdata.monitoring.thresholds.rules', [
        ['context' => 'system.cpu', 'dimension' => 'user', 'operator' => '>', 'value' => 90, 'severity' => 'critical'],
    ]);

    $fake = NetdataFake::create();
    $fake->fakeData([
        'result' => [
            'labels' => ['time', 'user'],
            'data' => [[1700000000, [95.5, 0, 0]]],
            'points' => 1,
        ],
        'view' => ['after' => 1700000000, 'before' => 1700000060],
    ]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $monitor = app(ThresholdMonitor::class);
    $monitor->check();

    Event::assertDispatched(MetricThresholdExceeded::class, function (MetricThresholdExceeded $event) {
        return $event->context === 'system.cpu'
            && $event->dimension === 'user'
            && $event->value === 95.5
            && $event->threshold === 90.0
            && $event->operator === '>'
            && $event->severity === 'critical';
    });
});

test('threshold monitor does not dispatch when value is below threshold', function () {
    Event::fake();

    config()->set('netdata.monitoring.thresholds.rules', [
        ['context' => 'system.cpu', 'dimension' => 'user', 'operator' => '>', 'value' => 90, 'severity' => 'critical'],
    ]);

    $fake = NetdataFake::create();
    $fake->fakeData([
        'result' => [
            'labels' => ['time', 'user'],
            'data' => [[1700000000, [50.0, 0, 0]]],
            'points' => 1,
        ],
        'view' => ['after' => 1700000000, 'before' => 1700000060],
    ]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $monitor = app(ThresholdMonitor::class);
    $monitor->check();

    Event::assertNotDispatched(MetricThresholdExceeded::class);
});

test('threshold monitor supports all six operators', function () {
    Event::fake();

    $operators = [
        ['>', 90, 95.0, true],
        ['>=', 90, 90.0, true],
        ['<', 10, 5.0, true],
        ['<=', 10, 10.0, true],
        ['==', 50, 50.0, true],
        ['!=', 50, 51.0, true],
    ];

    foreach ($operators as [$operator, $threshold, $value, $expected]) {
        Event::fake(); // reset

        config()->set('netdata.monitoring.thresholds.rules', [
            ['context' => 'system.cpu', 'dimension' => 'user', 'operator' => $operator, 'value' => $threshold, 'severity' => 'warning'],
        ]);

        $fake = NetdataFake::create();
        $fake->fakeData([
            'result' => [
                'labels' => ['time', 'user'],
                'data' => [[1700000000, [$value, 0, 0]]],
                'points' => 1,
            ],
            'view' => ['after' => 1700000000, 'before' => 1700000060],
        ]);

        $manager = app(NetdataManager::class);
        $manager->setClient('default', $fake->toClient());

        $monitor = app(ThresholdMonitor::class);
        $monitor->check();

        if ($expected) {
            Event::assertDispatched(MetricThresholdExceeded::class);
        } else {
            Event::assertNotDispatched(MetricThresholdExceeded::class);
        }
    }
});
