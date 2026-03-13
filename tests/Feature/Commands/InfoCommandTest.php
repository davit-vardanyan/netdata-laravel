<?php

declare(strict_types=1);

use DavitVardanyan\Netdata\Testing\NetdataFake;
use DavitVardanyan\NetdataLaravel\NetdataManager;

test('netdata:info displays agent info', function () {
    $fake = NetdataFake::create();
    $fake->fakeInfo([
        'version' => '1.44.0', 'uid' => 'agent-uid', 'hostname' => 'test-host',
        'os' => 'linux', 'architecture' => 'x86_64', 'cpus' => 8, 'host_labels' => ['_os_name' => 'Ubuntu'],
    ]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $this->artisan('netdata:info')
        ->assertExitCode(0);
});

test('netdata:info outputs valid json', function () {
    $fake = NetdataFake::create();
    $fake->fakeInfo([
        'version' => '1.44.0', 'uid' => 'test', 'hostname' => 'host',
        'os' => 'linux', 'architecture' => 'x86_64', 'cpus' => 4, 'host_labels' => [],
    ]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $this->artisan('netdata:info --json')
        ->assertExitCode(0);
});
