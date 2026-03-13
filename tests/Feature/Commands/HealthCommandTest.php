<?php

declare(strict_types=1);

use DavitVardanyan\Netdata\Testing\NetdataFake;
use DavitVardanyan\NetdataLaravel\NetdataManager;

test('netdata:health shows health overview', function () {
    $fake = NetdataFake::create();
    $fake->fakeData([
        'result' => [
            'labels' => ['time', 'user', 'system'],
            'data' => [[1700000000, [25.5, 0, 0], [10.2, 0, 0]]],
            'points' => 1,
        ],
        'view' => ['after' => 1700000000, 'before' => 1700000600],
    ]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $this->artisan('netdata:health')
        ->assertExitCode(0);
});
