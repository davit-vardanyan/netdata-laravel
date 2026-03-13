# Netdata Laravel

[![Tests](https://github.com/davit-vardanyan/netdata-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/davit-vardanyan/netdata-laravel/actions/workflows/tests.yml)
[![Code Style](https://github.com/davit-vardanyan/netdata-laravel/actions/workflows/code-style.yml/badge.svg)](https://github.com/davit-vardanyan/netdata-laravel/actions/workflows/code-style.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/davit-vardanyan/netdata-laravel.svg)](https://packagist.org/packages/davit-vardanyan/netdata-laravel)
[![PHP Version](https://img.shields.io/packagist/php-v/davit-vardanyan/netdata-laravel.svg)](https://packagist.org/packages/davit-vardanyan/netdata-laravel)
[![License](https://img.shields.io/packagist/l/davit-vardanyan/netdata-laravel.svg)](https://packagist.org/packages/davit-vardanyan/netdata-laravel)

A production-ready Laravel wrapper for the [Netdata API v3 PHP SDK](https://github.com/davit-vardanyan/netdata-php). Provides facade, multi-connection support, caching, artisan commands, monitoring, health checks, and more.

## Requirements

- PHP 8.2+
- Laravel 11.x or 12.x
- [davit-vardanyan/netdata-php](https://github.com/davit-vardanyan/netdata-php) ^1.0

## Installation

```bash
composer require davit-vardanyan/netdata-laravel
```

The package auto-discovers its service provider and facade.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=netdata-config
```

Set your environment variables in `.env`:

```dotenv
NETDATA_TOKEN=your-bearer-token
NETDATA_BASE_URL=https://registry.my-netdata.io
```

The base URL defaults to `https://registry.my-netdata.io`. Point it at any Netdata agent or Cloud instance — for example, a local agent at `http://localhost:19999`.

### Multi-Connection Setup

```php
// config/netdata.php
'connections' => [
    'default' => [
        'token' => env('NETDATA_TOKEN'),
        'base_url' => env('NETDATA_BASE_URL', 'https://registry.my-netdata.io'),
        'timeout' => (int) env('NETDATA_TIMEOUT', 30),
        'read_timeout' => (int) env('NETDATA_READ_TIMEOUT', 60),
        'retry' => [
            'max_attempts' => (int) env('NETDATA_RETRY_MAX', 3),
            'base_delay_ms' => (int) env('NETDATA_RETRY_DELAY', 1000),
        ],
    ],
    'local' => [
        'token' => '',
        'base_url' => 'http://localhost:19999',
    ],
],
```

## Quick Start

```php
use DavitVardanyan\NetdataLaravel\Facades\Netdata;

$nodes = Netdata::nodes()->list();
$cpuData = Netdata::data()->cpu();
$alerts = Netdata::alerts()->list();
```

## Usage

### All 13 Resource Accessors

```php
use DavitVardanyan\NetdataLaravel\Facades\Netdata;

Netdata::data()->cpu();              // CPU metrics (last 10 min)
Netdata::data()->memory();           // Memory metrics
Netdata::data()->disk();             // Disk metrics
Netdata::data()->network();          // Network metrics
Netdata::data()->query($request);    // Custom data query via DataQueryRequest
Netdata::weights()->query($request); // Metric weight/correlation analysis
Netdata::contexts()->list();         // Browse metric contexts
Netdata::nodes()->list();            // List monitored nodes
Netdata::alerts()->list();           // Active alert summaries
Netdata::functions()->list();        // Agent functions
Netdata::info()->get();              // Agent info (version, hostname, OS)
Netdata::search()->query('cpu');     // Search across metrics
Netdata::badges()->svg('system.cpu');// Generate badge SVGs
Netdata::allMetrics()->get();        // Export all metrics (Prometheus format)
Netdata::config()->tree();           // Agent configuration tree
Netdata::streamPath()->get();        // Streaming path info
Netdata::claim()->info();            // Agent claim status
```

### Dependency Injection

```php
use DavitVardanyan\NetdataLaravel\NetdataManager;

public function __construct(private NetdataManager $netdata) {}

$this->netdata->nodes()->list();
$this->netdata->connection('local')->nodes()->list();
```

### Multiple Connections

```php
Netdata::connection('local')->nodes()->list();
Netdata::connection('production')->alerts()->list();
```

### Caching

Caching is enabled by default. Configure per-resource TTLs in `config/netdata.php`:

```php
'cache' => [
    'enabled' => true,
    'store' => null, // uses default cache store
    'prefix' => 'netdata',
    'ttl' => [
        'nodes' => 300,      // 5 minutes
        'contexts' => 1800,  // 30 minutes
        'info' => 3600,      // 1 hour
        'functions' => 1800, // 30 minutes
        'data' => 60,        // 1 minute
        'alerts' => 30,      // 30 seconds
    ],
],
```

Resources like `weights`, `search`, `badges`, `allMetrics`, `config`, `streamPath`, and `claim` bypass the cache automatically.

Flush cache programmatically:

```php
Netdata::flushCache();              // Flush all
Netdata::flushCache('nodes');       // Flush nodes only
```

## Artisan Commands

```bash
php artisan netdata:test                          # Test connectivity
php artisan netdata:nodes                         # List monitored nodes
php artisan netdata:alerts                        # List active alerts
php artisan netdata:alerts --status=critical      # Filter by status
php artisan netdata:health                        # System health overview (CPU, RAM, disk, network)
php artisan netdata:metrics system.cpu            # Query metric data
php artisan netdata:metrics system.cpu --after=-3600 --points=60
php artisan netdata:contexts                      # List metric contexts
php artisan netdata:contexts --filter=system      # Filter contexts
php artisan netdata:info                          # Agent information
php artisan netdata:functions                     # List agent functions
php artisan netdata:functions --execute=processes # Execute a function
```

All commands support `--connection=NAME` and `--json` flags.

## Monitoring

### Alert Polling

Polls for new alerts and dispatches `AlertTriggered` events. Alerts are deduplicated by name and chart.

```php
// config/netdata.php
'monitoring' => [
    'alerts' => [
        'enabled' => true,
        'poll_interval' => 60,
        'dispatch_events' => true,
        'notify' => [
            'enabled' => true,
            'channels' => ['mail', 'slack'],
            'recipients' => ['admin@example.com'],
        ],
    ],
],
```

### Node Status Monitoring

Detects nodes going online/offline and dispatches `NodeWentOffline` / `NodeCameOnline` events.

```php
'monitoring' => [
    'nodes' => [
        'enabled' => true,
        'poll_interval' => 120,
        'dispatch_events' => true,
    ],
],
```

### Threshold Rules

Monitor specific metrics against thresholds. Supports `>`, `>=`, `<`, `<=`, `==`, `!=` operators with optional duration-based breach tracking.

```php
'monitoring' => [
    'thresholds' => [
        'enabled' => true,
        'rules' => [
            [
                'context' => 'system.cpu',
                'dimension' => 'user',
                'operator' => '>',
                'value' => 90,
                'duration' => 300, // seconds the threshold must be breached
                'severity' => 'critical',
            ],
        ],
    ],
],
```

## Events

Listen to monitoring events in your `EventServiceProvider` or anywhere Laravel events are registered:

```php
use DavitVardanyan\NetdataLaravel\Events\AlertTriggered;
use DavitVardanyan\NetdataLaravel\Events\NodeWentOffline;
use DavitVardanyan\NetdataLaravel\Events\NodeCameOnline;
use DavitVardanyan\NetdataLaravel\Events\MetricThresholdExceeded;

Event::listen(AlertTriggered::class, function (AlertTriggered $event) {
    Log::warning("Alert: {$event->alert->name} [{$event->alert->status->value}]");
});

Event::listen(NodeWentOffline::class, function (NodeWentOffline $event) {
    Log::error("Node offline: {$event->node->name}");
});

Event::listen(MetricThresholdExceeded::class, function (MetricThresholdExceeded $event) {
    Log::critical("{$event->context}.{$event->dimension} {$event->operator} {$event->threshold} (value: {$event->value})");
});
```

## Health Check

```php
use DavitVardanyan\NetdataLaravel\Health\NetdataHealthCheck;

$check = app(NetdataHealthCheck::class);
$result = $check->run();
// $result->status: Status::Ok, Status::Warning, or Status::Failed
// $result->message: Human-readable description

// Check a specific connection
$result = $check->connection('local')->run();
```

## Middleware

Log request performance with the built-in middleware:

```php
// In a route group or kernel
use DavitVardanyan\NetdataLaravel\Middleware\NetdataPerformanceMiddleware;

Route::middleware(NetdataPerformanceMiddleware::class)->group(function () {
    // ...
});
```

## Testing

Use the `FakesNetdata` trait in your Pest or PHPUnit tests:

```php
use DavitVardanyan\NetdataLaravel\Testing\FakesNetdata;

uses(FakesNetdata::class);

test('dashboard shows node list', function () {
    $fake = $this->fakeNetdata();
    $fake->fakeNodes([
        ['nd' => 'abc', 'nm' => 'web-1', 'v' => 'v2.0.0'],
    ]);

    $this->get('/dashboard')->assertOk();

    $fake->assertCalled('/api/v3/nodes');
});
```

### Running the Package Tests

```bash
vendor/bin/pest              # Run tests (63 tests, 136 assertions)
vendor/bin/phpstan analyse   # Static analysis (level 8)
vendor/bin/pint --test       # Code style check (Laravel preset)
```

## License

MIT License. See [LICENSE](LICENSE) for details.
