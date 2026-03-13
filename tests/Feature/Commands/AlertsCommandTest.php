<?php

declare(strict_types=1);

use DavitVardanyan\Netdata\Testing\NetdataFake;
use DavitVardanyan\NetdataLaravel\NetdataManager;

test('netdata:alerts shows empty state', function () {
    $fake = NetdataFake::create();
    $fake->fakeAlerts(['alerts' => []]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $this->artisan('netdata:alerts')
        ->assertExitCode(0);
});

test('netdata:alerts displays alert table', function () {
    $fake = NetdataFake::create();
    $fake->fakeAlerts(['alerts' => [
        ['name' => 'cpu_high', 'chart' => 'system.cpu', 'status' => 'critical', 'value' => 95.5, 'units' => '%', 'info' => 'CPU high', 'last_status_change' => 1700000000],
        ['name' => 'disk_low', 'chart' => 'disk_space._', 'status' => 'warning', 'value' => 85.0, 'units' => '%', 'info' => 'Disk low', 'last_status_change' => 1700000100],
    ]]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $this->artisan('netdata:alerts')
        ->assertExitCode(0);
});

test('netdata:alerts outputs valid json', function () {
    $fake = NetdataFake::create();
    $fake->fakeAlerts(['alerts' => [
        ['name' => 'cpu_high', 'chart' => 'system.cpu', 'status' => 'critical', 'value' => 95.5, 'units' => '%', 'info' => 'CPU high', 'last_status_change' => 1700000000],
    ]]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $this->artisan('netdata:alerts --json')
        ->assertExitCode(0);
});
