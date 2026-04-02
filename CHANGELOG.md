# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-04-02

### Added

- Laravel 13.x support (`illuminate/*` `^13.0`, `orchestra/testbench` `^11.0`)

## [1.0.0] - 2026-03-13

### Added

- Multi-connection `NetdataManager` with lazy client resolution and request-lifetime caching
- `Netdata` facade proxying all 13 resource accessors (`data`, `weights`, `contexts`, `nodes`, `alerts`, `functions`, `info`, `search`, `badges`, `allMetrics`, `config`, `streamPath`, `claim`)
- `CachedNetdataClient` composition-based decorator with per-resource TTL and selective cache bypass
- Configurable base URL via `NETDATA_BASE_URL` (defaults to `https://registry.my-netdata.io`), supporting Netdata Cloud, public registries, and local agents
- 8 Artisan commands: `netdata:test`, `netdata:nodes`, `netdata:alerts`, `netdata:health`, `netdata:metrics`, `netdata:contexts`, `netdata:info`, `netdata:functions` — all with `--connection` and `--json` support
- 4 event classes: `AlertTriggered`, `NodeWentOffline`, `NodeCameOnline`, `MetricThresholdExceeded`
- `AlertPoller` with deduplication by name and chart, `NodeStatusPoller` with online/offline detection, `ThresholdMonitor` with 6 comparison operators and duration-based breach tracking
- `NetdataAlertNotification` and `NodeStatusNotification` mail notification classes
- `NetdataHealthCheck` with connectivity test and critical/warning alert detection
- `NetdataPerformanceMiddleware` for HTTP request duration logging
- `FakesNetdata` testing trait for injecting fake clients in consumer tests
- Full Pest v3 test suite with architectural tests
- PHPStan level 8 with Larastan — zero errors
- Laravel Pint code style (Laravel preset) — zero issues
- GitHub Actions CI workflows for tests and code style
- Publishable configuration with sensible defaults (`netdata-config` tag)
- Auto-discovery for service provider and facade
