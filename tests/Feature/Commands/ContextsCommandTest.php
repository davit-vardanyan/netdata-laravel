<?php

declare(strict_types=1);

use DavitVardanyan\Netdata\Testing\NetdataFake;
use DavitVardanyan\NetdataLaravel\NetdataManager;

test('netdata:contexts lists contexts', function () {
    $fake = NetdataFake::create();
    $fake->fakeContexts(['contexts' => [
        'system.cpu' => ['id' => 'system.cpu', 'name' => 'system.cpu', 'family' => 'cpu', 'chart_type' => 'stacked', 'units' => '%', 'priority' => 100, 'first_entry' => 1699900000, 'last_entry' => 1700000600],
    ]]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $this->artisan('netdata:contexts')
        ->assertExitCode(0);
});

test('netdata:contexts filters by substring', function () {
    $fake = NetdataFake::create();
    $fake->fakeContexts(['contexts' => [
        'system.cpu' => ['id' => 'system.cpu', 'name' => 'system.cpu', 'family' => 'cpu', 'chart_type' => 'stacked', 'units' => '%', 'priority' => 100, 'first_entry' => 1699900000, 'last_entry' => 1700000600],
        'system.ram' => ['id' => 'system.ram', 'name' => 'system.ram', 'family' => 'mem', 'chart_type' => 'stacked', 'units' => 'MiB', 'priority' => 200, 'first_entry' => 1699900000, 'last_entry' => 1700000600],
    ]]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $this->artisan('netdata:contexts --filter=cpu')
        ->assertExitCode(0);
});

test('netdata:contexts outputs valid json', function () {
    $fake = NetdataFake::create();
    $fake->fakeContexts(['contexts' => []]);

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    $this->artisan('netdata:contexts --json')
        ->assertExitCode(0);
});
