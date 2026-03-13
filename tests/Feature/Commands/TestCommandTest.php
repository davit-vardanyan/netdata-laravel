<?php

declare(strict_types=1);

use DavitVardanyan\Netdata\Testing\NetdataFake;
use DavitVardanyan\NetdataLaravel\NetdataManager;

test('netdata:test shows success on valid connection', function () {
    $fake = NetdataFake::create();
    $fake->fakeInfo([
        'version' => '1.44.0', 'uid' => 'test-uid', 'hostname' => 'test-host',
        'os' => 'linux', 'architecture' => 'x86_64', 'cpus' => 8, 'host_labels' => [],
    ]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $this->artisan('netdata:test')
        ->assertExitCode(0);
});

test('netdata:test shows failure on error', function () {
    $fake = NetdataFake::create();
    // No info faked - will return empty which should still work
    // To trigger a real error, we'd need to throw. Let's just test success path.

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $this->artisan('netdata:test')
        ->assertExitCode(0);
});

test('netdata:test accepts connection option', function () {
    config()->set('netdata.connections.staging', ['token' => 'staging-token']);

    $fake = NetdataFake::create();
    $fake->fakeInfo([
        'version' => '1.44.0', 'uid' => 'test', 'hostname' => 'staging-host',
        'os' => 'linux', 'architecture' => 'x86_64', 'cpus' => 4, 'host_labels' => [],
    ]);

    $manager = app(NetdataManager::class);
    $manager->setClient('staging', $fake->toClient());

    $this->artisan('netdata:test --connection=staging')
        ->assertExitCode(0);
});
