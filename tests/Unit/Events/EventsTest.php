<?php

declare(strict_types=1);

use DavitVardanyan\Netdata\DTOs\Alert;
use DavitVardanyan\Netdata\DTOs\Node;
use DavitVardanyan\Netdata\Enums\AlertStatus;
use DavitVardanyan\NetdataLaravel\Events\AlertTriggered;
use DavitVardanyan\NetdataLaravel\Events\MetricThresholdExceeded;
use DavitVardanyan\NetdataLaravel\Events\NodeCameOnline;
use DavitVardanyan\NetdataLaravel\Events\NodeWentOffline;

test('AlertTriggered holds alert and connection', function () {
    $alert = new Alert(
        name: 'cpu_high',
        chart: 'system.cpu',
        status: AlertStatus::Critical,
        value: 95.5,
        units: '%',
        info: 'CPU high',
        lastStatusChange: 1700000000,
    );

    $event = new AlertTriggered($alert, 'production');

    expect($event->alert)->toBe($alert);
    expect($event->connection)->toBe('production');
});

test('NodeWentOffline holds node, connection, and detectedAt', function () {
    $node = new Node(
        id: 'node-1', name: 'web-01', os: 'linux',
        osName: null, osVersion: null, kernelName: null, kernelVersion: null,
        architecture: null, cpus: null, memory: null, diskSpace: null,
        version: null, machineGuid: null, services: [],
    );

    $now = new DateTimeImmutable;
    $event = new NodeWentOffline($node, 'default', $now);

    expect($event->node)->toBe($node);
    expect($event->connection)->toBe('default');
    expect($event->detectedAt)->toBe($now);
});

test('NodeCameOnline holds node, connection, and detectedAt', function () {
    $node = new Node(
        id: 'node-1', name: 'web-01', os: 'linux',
        osName: null, osVersion: null, kernelName: null, kernelVersion: null,
        architecture: null, cpus: null, memory: null, diskSpace: null,
        version: '1.44.0', machineGuid: null, services: [],
    );

    $now = new DateTimeImmutable;
    $event = new NodeCameOnline($node, 'default', $now);

    expect($event->node)->toBe($node);
    expect($event->connection)->toBe('default');
    expect($event->detectedAt)->toBe($now);
});

test('MetricThresholdExceeded holds all properties', function () {
    $event = new MetricThresholdExceeded(
        context: 'system.cpu',
        dimension: 'user',
        value: 95.0,
        threshold: 90.0,
        operator: '>',
        severity: 'critical',
        connection: 'default',
    );

    expect($event->context)->toBe('system.cpu');
    expect($event->dimension)->toBe('user');
    expect($event->value)->toBe(95.0);
    expect($event->threshold)->toBe(90.0);
    expect($event->operator)->toBe('>');
    expect($event->severity)->toBe('critical');
    expect($event->connection)->toBe('default');
});
