<?php

declare(strict_types=1);

use DavitVardanyan\Netdata\Testing\NetdataFake;
use DavitVardanyan\NetdataLaravel\Cache\CachedNetdataClient;
use Illuminate\Support\Facades\Cache;

test('remember caches result for cached resource', function () {
    $fake = NetdataFake::create();
    $client = $fake->toClient();
    $cache = Cache::store('array');

    $cached = new CachedNetdataClient($client, $cache, 'netdata', 'default', ['nodes' => 300]);

    $callCount = 0;
    $result1 = $cached->remember('nodes', 'list', [], function () use (&$callCount) {
        $callCount++;

        return ['node-1', 'node-2'];
    });

    $result2 = $cached->remember('nodes', 'list', [], function () use (&$callCount) {
        $callCount++;

        return ['node-3'];
    });

    expect($result1)->toBe(['node-1', 'node-2']);
    expect($result2)->toBe(['node-1', 'node-2']); // cached
    expect($callCount)->toBe(1);
});

test('remember bypasses cache for non-cached resources', function () {
    $fake = NetdataFake::create();
    $client = $fake->toClient();
    $cache = Cache::store('array');

    $cached = new CachedNetdataClient($client, $cache, 'netdata', 'default', []);

    $callCount = 0;
    $cached->remember('weights', 'query', [], function () use (&$callCount) {
        $callCount++;

        return ['result'];
    });
    $cached->remember('weights', 'query', [], function () use (&$callCount) {
        $callCount++;

        return ['result2'];
    });

    expect($callCount)->toBe(2); // not cached
});

test('remember uses different keys for different params', function () {
    $fake = NetdataFake::create();
    $client = $fake->toClient();
    $cache = Cache::store('array');

    $cached = new CachedNetdataClient($client, $cache, 'netdata', 'default', ['nodes' => 300]);

    $result1 = $cached->remember('nodes', 'list', ['scope' => 'a'], fn () => 'result-a');
    $result2 = $cached->remember('nodes', 'list', ['scope' => 'b'], fn () => 'result-b');

    expect($result1)->toBe('result-a');
    expect($result2)->toBe('result-b');
});

test('flushCache clears all cached keys', function () {
    $fake = NetdataFake::create();
    $client = $fake->toClient();
    $cache = Cache::store('array');

    $cached = new CachedNetdataClient($client, $cache, 'netdata', 'default', ['nodes' => 300]);

    $cached->remember('nodes', 'list', [], fn () => 'original');
    $cached->flushCache();

    $callCount = 0;
    $result = $cached->remember('nodes', 'list', [], function () use (&$callCount) {
        $callCount++;

        return 'refreshed';
    });

    expect($result)->toBe('refreshed');
    expect($callCount)->toBe(1);
});

test('flushCache by resource only clears that resource', function () {
    $fake = NetdataFake::create();
    $client = $fake->toClient();
    $cache = Cache::store('array');

    $cached = new CachedNetdataClient($client, $cache, 'netdata', 'default', [
        'nodes' => 300,
        'info' => 3600,
    ]);

    $cached->remember('nodes', 'list', [], fn () => 'nodes-data');
    $cached->remember('info', 'get', [], fn () => 'info-data');

    $cached->flushCache('nodes');

    // nodes should be refreshed
    $nodesCallCount = 0;
    $cached->remember('nodes', 'list', [], function () use (&$nodesCallCount) {
        $nodesCallCount++;

        return 'new-nodes';
    });
    expect($nodesCallCount)->toBe(1);

    // info should still be cached
    $infoCallCount = 0;
    $result = $cached->remember('info', 'get', [], function () use (&$infoCallCount) {
        $infoCallCount++;

        return 'new-info';
    });
    expect($result)->toBe('info-data');
    expect($infoCallCount)->toBe(0);
});

test('isCachedResource returns true for cached resources', function () {
    $fake = NetdataFake::create();
    $client = $fake->toClient();
    $cache = Cache::store('array');

    $cached = new CachedNetdataClient($client, $cache, 'netdata', 'default', []);

    expect($cached->isCachedResource('nodes'))->toBeTrue();
    expect($cached->isCachedResource('contexts'))->toBeTrue();
    expect($cached->isCachedResource('info'))->toBeTrue();
    expect($cached->isCachedResource('functions'))->toBeTrue();
    expect($cached->isCachedResource('data'))->toBeTrue();
    expect($cached->isCachedResource('alerts'))->toBeTrue();
});

test('isCachedResource returns false for non-cached resources', function () {
    $fake = NetdataFake::create();
    $client = $fake->toClient();
    $cache = Cache::store('array');

    $cached = new CachedNetdataClient($client, $cache, 'netdata', 'default', []);

    expect($cached->isCachedResource('weights'))->toBeFalse();
    expect($cached->isCachedResource('search'))->toBeFalse();
    expect($cached->isCachedResource('badges'))->toBeFalse();
    expect($cached->isCachedResource('allMetrics'))->toBeFalse();
    expect($cached->isCachedResource('config'))->toBeFalse();
    expect($cached->isCachedResource('streamPath'))->toBeFalse();
    expect($cached->isCachedResource('claim'))->toBeFalse();
});

test('getClient returns underlying client', function () {
    $fake = NetdataFake::create();
    $client = $fake->toClient();
    $cache = Cache::store('array');

    $cached = new CachedNetdataClient($client, $cache, 'netdata', 'default', []);

    expect($cached->getClient())->toBe($client);
});
