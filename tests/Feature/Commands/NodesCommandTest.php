<?php

declare(strict_types=1);

use DavitVardanyan\Netdata\Testing\NetdataFake;
use DavitVardanyan\NetdataLaravel\NetdataManager;

test('netdata:nodes displays node table', function () {
    $fake = NetdataFake::create();
    $fake->fakeNodes([
        ['nd' => 'node-1', 'nm' => 'web-server-01', 'os' => 'linux', 'v' => '1.44.0', 'architecture' => 'x86_64', 'cpus' => 8, 'memory' => '16 GiB', 'services' => []],
    ]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $this->artisan('netdata:nodes')
        ->assertExitCode(0);
});

test('netdata:nodes shows empty state', function () {
    $fake = NetdataFake::create();
    $fake->fakeNodes([]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $this->artisan('netdata:nodes')
        ->assertExitCode(0);
});

test('netdata:nodes outputs valid json', function () {
    $fake = NetdataFake::create();
    $fake->fakeNodes([
        ['nd' => 'node-1', 'nm' => 'web-01', 'os' => 'linux', 'v' => '1.44.0', 'services' => []],
    ]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $this->artisan('netdata:nodes --json')
        ->assertExitCode(0);
});
