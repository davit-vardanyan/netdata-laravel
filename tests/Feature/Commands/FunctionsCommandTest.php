<?php

declare(strict_types=1);

use DavitVardanyan\Netdata\Testing\NetdataFake;
use DavitVardanyan\NetdataLaravel\NetdataManager;

test('netdata:functions lists available functions', function () {
    $fake = NetdataFake::create();
    $fake->fakeFunctions(['functions' => [
        ['name' => 'processes', 'description' => 'Show running processes'],
        ['name' => 'network-interfaces', 'description' => 'Show network stats'],
    ]]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $this->artisan('netdata:functions')
        ->assertExitCode(0);
});

test('netdata:functions shows empty state', function () {
    $fake = NetdataFake::create();
    $fake->fakeFunctions([]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $this->artisan('netdata:functions')
        ->assertExitCode(0);
});

test('netdata:functions outputs valid json', function () {
    $fake = NetdataFake::create();
    $fake->fakeFunctions(['functions' => [
        ['name' => 'processes', 'description' => 'Show running processes'],
    ]]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $this->artisan('netdata:functions --json')
        ->assertExitCode(0);
});
