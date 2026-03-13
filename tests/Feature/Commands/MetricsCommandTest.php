<?php

declare(strict_types=1);

use DavitVardanyan\Netdata\Testing\NetdataFake;
use DavitVardanyan\NetdataLaravel\NetdataManager;

test('netdata:metrics displays metric data', function () {
    $fake = NetdataFake::create();
    $fake->fakeData([
        'result' => [
            'labels' => ['time', 'user', 'system'],
            'data' => [
                [1700000000, [25.5, 0, 0], [10.2, 0, 0]],
                [1700000300, [30.1, 0, 0], [12.4, 0, 0]],
            ],
            'points' => 2,
        ],
        'view' => ['after' => 1700000000, 'before' => 1700000600],
    ]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $this->artisan('netdata:metrics system.cpu')
        ->assertExitCode(0);
});

test('netdata:metrics shows empty state', function () {
    $fake = NetdataFake::create();
    $fake->fakeData([
        'result' => [
            'labels' => ['time'],
            'data' => [],
            'points' => 0,
        ],
        'view' => ['after' => 0, 'before' => 0],
    ]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $this->artisan('netdata:metrics system.cpu')
        ->assertExitCode(0);
});

test('netdata:metrics outputs valid json', function () {
    $fake = NetdataFake::create();
    $fake->fakeData([
        'result' => [
            'labels' => ['time', 'user'],
            'data' => [[1700000000, [25.5, 0, 0]]],
            'points' => 1,
        ],
        'view' => ['after' => 1700000000, 'before' => 1700000600],
    ]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $this->artisan('netdata:metrics system.cpu --json')
        ->assertExitCode(0);
});
