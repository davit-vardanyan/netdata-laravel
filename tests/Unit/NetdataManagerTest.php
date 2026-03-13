<?php

declare(strict_types=1);

use DavitVardanyan\Netdata\NetdataClient;
use DavitVardanyan\Netdata\Resources\AlertResource;
use DavitVardanyan\Netdata\Resources\AllMetricsResource;
use DavitVardanyan\Netdata\Resources\BadgeResource;
use DavitVardanyan\Netdata\Resources\ClaimResource;
use DavitVardanyan\Netdata\Resources\ConfigResource;
use DavitVardanyan\Netdata\Resources\ContextResource;
use DavitVardanyan\Netdata\Resources\DataResource;
use DavitVardanyan\Netdata\Resources\FunctionResource;
use DavitVardanyan\Netdata\Resources\InfoResource;
use DavitVardanyan\Netdata\Resources\NodeResource;
use DavitVardanyan\Netdata\Resources\SearchResource;
use DavitVardanyan\Netdata\Resources\StreamPathResource;
use DavitVardanyan\Netdata\Resources\WeightsResource;
use DavitVardanyan\Netdata\Testing\NetdataFake;
use DavitVardanyan\NetdataLaravel\NetdataManager;

test('manager resolves default connection', function () {
    $manager = app(NetdataManager::class);
    $client = $manager->connection();
    expect($client)->toBeInstanceOf(NetdataClient::class);
});

test('manager caches connections for request lifetime', function () {
    $manager = app(NetdataManager::class);
    $client1 = $manager->connection();
    $client2 = $manager->connection();
    expect($client1)->toBe($client2);
});

test('manager resolves named connection', function () {
    config()->set('netdata.connections.staging', [
        'token' => 'staging-token',
        'base_url' => 'https://staging.netdata.cloud',
    ]);

    $manager = app(NetdataManager::class);
    $client = $manager->connection('staging');
    expect($client)->toBeInstanceOf(NetdataClient::class);
});

test('manager throws on missing connection', function () {
    $manager = app(NetdataManager::class);
    $manager->connection('nonexistent');
})->throws(InvalidArgumentException::class, 'is not configured');

test('manager throws on empty token', function () {
    config()->set('netdata.connections.empty', [
        'token' => '',
    ]);

    $manager = app(NetdataManager::class);
    $manager->connection('empty');
})->throws(InvalidArgumentException::class, 'requires an API token');

test('manager throws on null token', function () {
    config()->set('netdata.connections.nulltoken', [
        'token' => null,
    ]);

    $manager = app(NetdataManager::class);
    $manager->connection('nulltoken');
})->throws(InvalidArgumentException::class, 'requires an API token');

test('manager returns default connection name from config', function () {
    config()->set('netdata.default', 'production');

    $manager = app(NetdataManager::class);
    expect($manager->getDefaultConnection())->toBe('production');
});

test('manager proxies all 13 resource methods via __call', function () {
    $fake = NetdataFake::create();
    $fake->fakeInfo([
        'version' => '1.44.0', 'uid' => 'test', 'hostname' => 'host',
        'os' => 'linux', 'architecture' => 'x86_64', 'cpus' => 4, 'host_labels' => [],
    ]);
    $client = $fake->toClient();

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $client);

    // Test that each accessor returns the correct resource type
    expect($manager->data())->toBeInstanceOf(DataResource::class);
    expect($manager->weights())->toBeInstanceOf(WeightsResource::class);
    expect($manager->contexts())->toBeInstanceOf(ContextResource::class);
    expect($manager->nodes())->toBeInstanceOf(NodeResource::class);
    expect($manager->alerts())->toBeInstanceOf(AlertResource::class);
    expect($manager->functions())->toBeInstanceOf(FunctionResource::class);
    expect($manager->info())->toBeInstanceOf(InfoResource::class);
    expect($manager->search())->toBeInstanceOf(SearchResource::class);
    expect($manager->badges())->toBeInstanceOf(BadgeResource::class);
    expect($manager->allMetrics())->toBeInstanceOf(AllMetricsResource::class);
    expect($manager->config())->toBeInstanceOf(ConfigResource::class);
    expect($manager->streamPath())->toBeInstanceOf(StreamPathResource::class);
    expect($manager->claim())->toBeInstanceOf(ClaimResource::class);
});

test('manager setClient overrides connection', function () {
    $fake = NetdataFake::create();
    $fakeClient = $fake->toClient();

    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fakeClient);

    expect($manager->connection())->toBe($fakeClient);
});

test('manager flushCache is no-op when no cached clients', function () {
    $manager = app(NetdataManager::class);
    // Should not throw
    $manager->flushCache();
    expect(true)->toBeTrue();
});
