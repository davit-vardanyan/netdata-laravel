<?php

declare(strict_types=1);

use DavitVardanyan\Netdata\NetdataClient;
use DavitVardanyan\NetdataLaravel\NetdataManager;

test('service provider registers NetdataManager as singleton', function () {
    $manager1 = app(NetdataManager::class);
    $manager2 = app(NetdataManager::class);

    expect($manager1)->toBe($manager2);
});

test('service provider binds NetdataClient to default connection', function () {
    $client = app(NetdataClient::class);

    expect($client)->toBeInstanceOf(NetdataClient::class);
});

test('config is merged with defaults', function () {
    expect(config('netdata.default'))->toBe('default');
    expect(config('netdata.cache.enabled'))->toBeTrue();
    expect(config('netdata.cache.prefix'))->toBe('netdata');
});

test('commands are registered', function () {
    $commands = Artisan::all();

    expect($commands)->toHaveKey('netdata:test');
    expect($commands)->toHaveKey('netdata:nodes');
    expect($commands)->toHaveKey('netdata:alerts');
    expect($commands)->toHaveKey('netdata:health');
    expect($commands)->toHaveKey('netdata:metrics');
    expect($commands)->toHaveKey('netdata:contexts');
    expect($commands)->toHaveKey('netdata:info');
    expect($commands)->toHaveKey('netdata:functions');
});
