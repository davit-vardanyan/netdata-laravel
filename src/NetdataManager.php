<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel;

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
use DavitVardanyan\Netdata\Support\Config;
use DavitVardanyan\NetdataLaravel\Cache\CachedNetdataClient;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Log\LogManager;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Multi-connection manager for the Netdata SDK.
 *
 * Follows Laravel's DatabaseManager pattern: resolves named connections lazily,
 * caches them for the request lifetime, and proxies all 13 resource accessor
 * methods to the default connection via `__call`.
 *
 * @method DataResource data()
 * @method WeightsResource weights()
 * @method ContextResource contexts()
 * @method NodeResource nodes()
 * @method AlertResource alerts()
 * @method FunctionResource functions()
 * @method InfoResource info()
 * @method SearchResource search()
 * @method BadgeResource badges()
 * @method AllMetricsResource allMetrics()
 * @method ConfigResource config()
 * @method StreamPathResource streamPath()
 * @method ClaimResource claim()
 */
final class NetdataManager
{
    /**
     * Resolved NetdataClient instances keyed by connection name.
     *
     * @var array<string, NetdataClient>
     */
    private array $connections = [];

    /**
     * Resolved CachedNetdataClient instances keyed by connection name.
     *
     * @var array<string, CachedNetdataClient>
     */
    private array $cachedClients = [];

    /**
     * @param  Application  $app  The Laravel application instance.
     */
    public function __construct(
        private readonly Application $app,
    ) {}

    /**
     * Resolve a named Netdata connection.
     *
     * If no name is provided, the default connection is used. Connections are
     * lazily created and cached for the lifetime of the request.
     *
     * @param  string|null  $name  The connection name, or null for the default.
     *
     * @throws InvalidArgumentException If the connection config does not exist.
     * @throws InvalidArgumentException If the API token is empty or null.
     */
    public function connection(?string $name = null): NetdataClient
    {
        $name ??= $this->getDefaultConnection();

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        return $this->connections[$name] = $this->resolve($name);
    }

    /**
     * Override the client for a given connection.
     *
     * This is primarily intended for testing, allowing a fake client to be
     * injected without modifying the connection configuration.
     *
     * @param  string  $name  The connection name.
     * @param  NetdataClient  $client  The client instance to use.
     */
    public function setClient(string $name, NetdataClient $client): void
    {
        $this->connections[$name] = $client;
    }

    /**
     * Get the default connection name.
     */
    public function getDefaultConnection(): string
    {
        /** @var string $default */
        $default = $this->app['config']->get('netdata.default', 'default');

        return $default;
    }

    /**
     * Get the CachedNetdataClient for a given connection, if caching is enabled.
     *
     * Returns null if caching is not enabled or the connection has not been resolved.
     *
     * @param  string|null  $name  The connection name, or null for the default.
     */
    public function getCachedClient(?string $name = null): ?CachedNetdataClient
    {
        $name ??= $this->getDefaultConnection();

        return $this->cachedClients[$name] ?? null;
    }

    /**
     * Flush cached responses.
     *
     * Delegates to the CachedNetdataClient for the default connection. If
     * caching is not enabled, this method is a no-op.
     *
     * @param  string|null  $resource  Optionally limit flushing to a specific resource (e.g., "nodes").
     * @param  string|null  $context  Optionally limit flushing to a specific context.
     */
    public function flushCache(?string $resource = null, ?string $context = null): void
    {
        foreach ($this->cachedClients as $cachedClient) {
            $cachedClient->flushCache($resource, $context);
        }
    }

    /**
     * Proxy all 13 resource accessor methods to the default connection's client.
     *
     * This allows calling `$manager->nodes()` instead of `$manager->connection()->nodes()`.
     *
     * @param  string  $method  The method name (e.g., "nodes", "data").
     * @param  array<mixed>  $arguments  The method arguments.
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->connection()->{$method}(...$arguments);
    }

    /**
     * Resolve and build a NetdataClient for the given connection name.
     *
     * @param  string  $name  The connection name.
     *
     * @throws InvalidArgumentException If the connection config does not exist.
     * @throws InvalidArgumentException If the API token is empty or null.
     */
    private function resolve(string $name): NetdataClient
    {
        /** @var array<string, mixed>|null $connectionConfig */
        $connectionConfig = $this->app['config']->get("netdata.connections.{$name}");

        if ($connectionConfig === null) {
            throw new InvalidArgumentException(
                "Netdata connection [{$name}] is not configured. "
                .'Please check your config/netdata.php connections array.'
            );
        }

        $token = $connectionConfig['token'] ?? null;

        if ($token === null || $token === '') {
            throw new InvalidArgumentException(
                "Netdata connection [{$name}] requires an API token. "
                .'Set the NETDATA_TOKEN environment variable or update config/netdata.php.'
            );
        }

        /** @var array{max_attempts?: int, base_delay_ms?: int} $retryConfig */
        $retryConfig = $connectionConfig['retry'] ?? [];

        $config = new Config(
            token: (string) $token,
            baseUrl: (string) ($connectionConfig['base_url'] ?? 'https://registry.my-netdata.io'),
            connectTimeout: (int) ($connectionConfig['timeout'] ?? 30),
            readTimeout: (int) ($connectionConfig['read_timeout'] ?? 60),
            maxRetries: (int) ($retryConfig['max_attempts'] ?? 3),
            retryBaseDelayMs: (int) ($retryConfig['base_delay_ms'] ?? 1000),
        );

        $logger = $this->createLogger();

        $client = new NetdataClient(
            config: $config,
            logger: $logger,
        );

        if ($this->isCacheEnabled()) {
            $this->cachedClients[$name] = $this->createCachedClient($client, $name);
        }

        return $client;
    }

    /**
     * Create a PSR-3 logger if logging is enabled in the config.
     */
    private function createLogger(): ?LoggerInterface
    {
        /** @var bool $enabled */
        $enabled = $this->app['config']->get('netdata.logging.enabled', false);

        if (! $enabled) {
            return null;
        }

        /** @var string|null $channel */
        $channel = $this->app['config']->get('netdata.logging.channel');

        /** @var LogManager $logManager */
        $logManager = $this->app['log'];

        return $logManager->channel($channel);
    }

    /**
     * Check whether response caching is enabled.
     */
    private function isCacheEnabled(): bool
    {
        /** @var bool $enabled */
        $enabled = $this->app['config']->get('netdata.cache.enabled', false);

        return $enabled;
    }

    /**
     * Create a CachedNetdataClient wrapping the given client.
     *
     * @param  NetdataClient  $client  The client to wrap.
     * @param  string  $name  The connection name.
     */
    private function createCachedClient(NetdataClient $client, string $name): CachedNetdataClient
    {
        /** @var string|null $storeName */
        $storeName = $this->app['config']->get('netdata.cache.store');

        /** @var CacheManager $cacheManager */
        $cacheManager = $this->app['cache'];

        /** @var Repository $store */
        $store = $cacheManager->store($storeName);

        /** @var string $prefix */
        $prefix = $this->app['config']->get('netdata.cache.prefix', 'netdata');

        /** @var array<string, int> $ttl */
        $ttl = $this->app['config']->get('netdata.cache.ttl', []);

        return new CachedNetdataClient(
            client: $client,
            cache: $store,
            prefix: $prefix,
            connection: $name,
            ttl: $ttl,
        );
    }
}
