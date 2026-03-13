<?php

declare(strict_types=1);

use DavitVardanyan\Netdata\Testing\NetdataFake;
use DavitVardanyan\NetdataLaravel\Health\NetdataHealthCheck;
use DavitVardanyan\NetdataLaravel\Health\Status;
use DavitVardanyan\NetdataLaravel\NetdataManager;

test('health check returns ok when healthy', function () {
    $fake = NetdataFake::create();
    $fake->fakeInfo([
        'version' => '1.44.0', 'uid' => 'test', 'hostname' => 'host',
        'os' => 'linux', 'architecture' => 'x86_64', 'cpus' => 4, 'host_labels' => [],
    ]);
    $fake->fakeAlerts(['alerts' => []]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $check = app(NetdataHealthCheck::class);
    $result = $check->run();

    expect($result->status)->toBe(Status::Ok);
});

test('health check returns warning for warning alerts', function () {
    $fake = NetdataFake::create();
    $fake->fakeInfo([
        'version' => '1.44.0', 'uid' => 'test', 'hostname' => 'host',
        'os' => 'linux', 'architecture' => 'x86_64', 'cpus' => 4, 'host_labels' => [],
    ]);
    $fake->fakeAlerts(['alerts' => [
        ['name' => 'disk_low', 'chart' => 'disk_space._', 'status' => 'warning', 'value' => 85.0, 'units' => '%', 'info' => 'Disk low', 'last_status_change' => 1700000000],
    ]]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $check = app(NetdataHealthCheck::class);
    $result = $check->run();

    expect($result->status)->toBe(Status::Warning);
});

test('health check returns failed for critical alerts', function () {
    $fake = NetdataFake::create();
    $fake->fakeInfo([
        'version' => '1.44.0', 'uid' => 'test', 'hostname' => 'host',
        'os' => 'linux', 'architecture' => 'x86_64', 'cpus' => 4, 'host_labels' => [],
    ]);
    $fake->fakeAlerts(['alerts' => [
        ['name' => 'cpu_high', 'chart' => 'system.cpu', 'status' => 'critical', 'value' => 95.0, 'units' => '%', 'info' => 'CPU high', 'last_status_change' => 1700000000],
    ]]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $check = app(NetdataHealthCheck::class);
    $result = $check->run();

    expect($result->status)->toBe(Status::Failed);
});
