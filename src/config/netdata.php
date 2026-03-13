<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Connection
    |--------------------------------------------------------------------------
    |
    | The default Netdata Cloud connection to use. Useful if you manage
    | multiple Netdata Cloud accounts or spaces.
    |
    */
    'default' => env('NETDATA_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Connections
    |--------------------------------------------------------------------------
    |
    | Define one or more Netdata Cloud connections. Each connection has its
    | own API token and optional configuration overrides.
    |
    */
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

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Enable response caching to reduce API calls. Particularly useful for
    | data that doesn't change frequently like nodes and contexts.
    |
    */
    'cache' => [
        'enabled' => env('NETDATA_CACHE_ENABLED', true),
        'store' => env('NETDATA_CACHE_STORE'),
        'prefix' => 'netdata',
        'ttl' => [
            'nodes' => 300,
            'contexts' => 1800,
            'info' => 3600,
            'functions' => 1800,
            'data' => 60,
            'alerts' => 30,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure automated monitoring: alert polling, node status checking,
    | and metric threshold monitoring. All disabled by default.
    |
    */
    'monitoring' => [

        'alerts' => [
            'enabled' => env('NETDATA_MONITOR_ALERTS', false),
            'poll_interval' => 60,
            'dispatch_events' => true,
            'notify' => [
                'enabled' => false,
                'channels' => ['mail', 'slack'],
                'recipients' => [],
            ],
        ],

        'nodes' => [
            'enabled' => env('NETDATA_MONITOR_NODES', false),
            'poll_interval' => 120,
            'dispatch_events' => true,
            'notify' => [
                'enabled' => false,
                'channels' => ['mail', 'slack'],
                'recipients' => [],
            ],
        ],

        'thresholds' => [
            'enabled' => env('NETDATA_MONITOR_THRESHOLDS', false),
            'poll_interval' => 60,
            'rules' => [
                // [
                //     'context' => 'system.cpu',
                //     'dimension' => 'user',
                //     'operator' => '>',
                //     'value' => 90,
                //     'duration' => 300,
                //     'severity' => 'critical',
                // ],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Pass a PSR-3 logger to the core SDK for request/response debugging.
    |
    */
    'logging' => [
        'enabled' => env('NETDATA_LOG_ENABLED', false),
        'channel' => env('NETDATA_LOG_CHANNEL'),
    ],

];
