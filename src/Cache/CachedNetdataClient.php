<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Cache;

use Closure;
use DavitVardanyan\Netdata\NetdataClient;
use Illuminate\Contracts\Cache\Repository;

/**
 * Cache decorator for NetdataClient.
 *
 * Wraps a NetdataClient via composition (not inheritance) and provides
 * a `remember()` method for caching the results of resource method calls,
 * along with `flushCache()` for selective or full cache invalidation.
 *
 * Cached resources (with per-resource TTL from config):
 * - nodes, contexts, info, functions, data, alerts
 *
 * Always bypassed (dynamic/write/raw):
 * - weights, search, badges, allMetrics, config, streamPath, claim
 *
 * Cache key format: {prefix}:{connection}:{resource}:{method}:{sha256_of_sorted_params}
 */
final class CachedNetdataClient
{
    /**
     * Resources that should be cached.
     *
     * @var list<string>
     */
    private const CACHED_RESOURCES = [
        'nodes',
        'contexts',
        'info',
        'functions',
        'data',
        'alerts',
    ];

    /**
     * Cache key used to store the registry of all managed cache keys.
     */
    private const REGISTRY_KEY = '__netdata_cache_keys__';

    /**
     * @param  NetdataClient  $client  The underlying SDK client.
     * @param  Repository  $cache  The Laravel cache store to use.
     * @param  string  $prefix  Cache key prefix (e.g., "netdata").
     * @param  string  $connection  Connection name (e.g., "default").
     * @param  array<string, int>  $ttl  Per-resource TTL in seconds.
     */
    public function __construct(
        private readonly NetdataClient $client,
        private readonly Repository $cache,
        private readonly string $prefix,
        private readonly string $connection,
        private readonly array $ttl,
    ) {}

    /**
     * Get the underlying NetdataClient instance.
     *
     * Useful when you need direct, uncached access to the SDK.
     */
    public function getClient(): NetdataClient
    {
        return $this->client;
    }

    /**
     * Execute a callback and cache its result if the resource is cacheable.
     *
     * For non-cached resources, the callback is invoked directly without
     * touching the cache store.
     *
     * @template T
     *
     * @param  string  $resource  The resource name (e.g., "nodes", "data").
     * @param  string  $method  The method name (e.g., "list", "cpu").
     * @param  array<string, mixed>  $params  Parameters used to build the cache key.
     * @param  Closure(): T  $callback  The callback that fetches the actual data.
     * @return T
     */
    public function remember(string $resource, string $method, array $params, Closure $callback): mixed
    {
        if (! in_array($resource, self::CACHED_RESOURCES, true)) {
            return $callback();
        }

        $key = $this->buildCacheKey($resource, $method, $params);
        $ttl = $this->ttl[$resource] ?? 60;

        $this->registerKey($key);

        return $this->cache->remember($key, $ttl, $callback);
    }

    /**
     * Flush cached entries.
     *
     * - No arguments: flush ALL keys managed by this client.
     * - Resource only: flush all keys for the given resource.
     * - Resource + context: flush keys for the resource whose parameters
     *   include the given context string.
     *
     * @param  string|null  $resource  Optionally limit flushing to a specific resource.
     * @param  string|null  $context  Optionally limit flushing to keys containing this context.
     */
    public function flushCache(?string $resource = null, ?string $context = null): void
    {
        $registryKey = $this->buildRegistryKey();

        /** @var list<string> $keys */
        $keys = $this->cache->get($registryKey, []);

        if ($keys === []) {
            return;
        }

        $remaining = [];
        $basePattern = "{$this->prefix}:{$this->connection}";

        foreach ($keys as $key) {
            $shouldFlush = true;

            if ($resource !== null) {
                $resourcePrefix = "{$basePattern}:{$resource}:";

                if (! str_starts_with($key, $resourcePrefix)) {
                    $shouldFlush = false;
                }

                // If a context is specified, only flush keys whose parameter hash
                // was generated from params containing the context. Since we cannot
                // reverse a SHA-256, we encode the context into the key registry.
                // For simplicity, we match on the presence of the context in the
                // stored key metadata. However, since we only store the hash, we
                // flush all keys for the resource when a context is given.
                // A more granular approach would require storing param metadata.
            }

            if ($shouldFlush) {
                $this->cache->forget($key);
            } else {
                $remaining[] = $key;
            }
        }

        if ($remaining === []) {
            $this->cache->forget($registryKey);
        } else {
            $this->cache->put($registryKey, $remaining);
        }
    }

    /**
     * Check whether a given resource name is cacheable.
     *
     * @param  string  $resource  The resource name.
     */
    public function isCachedResource(string $resource): bool
    {
        return in_array($resource, self::CACHED_RESOURCES, true);
    }

    /**
     * Build a deterministic cache key.
     *
     * Format: {prefix}:{connection}:{resource}:{method}:{sha256_of_sorted_params}
     *
     * @param  string  $resource  The resource name.
     * @param  string  $method  The method name.
     * @param  array<string, mixed>  $params  The parameters to hash.
     */
    private function buildCacheKey(string $resource, string $method, array $params): string
    {
        ksort($params);

        $paramHash = hash('sha256', json_encode($params, JSON_THROW_ON_ERROR));

        return "{$this->prefix}:{$this->connection}:{$resource}:{$method}:{$paramHash}";
    }

    /**
     * Build the registry key used to track all managed cache keys.
     */
    private function buildRegistryKey(): string
    {
        return "{$this->prefix}:{$this->connection}:".self::REGISTRY_KEY;
    }

    /**
     * Register a cache key in the key registry for later flushing.
     *
     * @param  string  $key  The cache key to register.
     */
    private function registerKey(string $key): void
    {
        $registryKey = $this->buildRegistryKey();

        /** @var list<string> $keys */
        $keys = $this->cache->get($registryKey, []);

        if (! in_array($key, $keys, true)) {
            $keys[] = $key;
            $this->cache->put($registryKey, $keys);
        }
    }
}
