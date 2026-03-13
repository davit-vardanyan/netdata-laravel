<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Monitoring;

use DavitVardanyan\Netdata\Requests\DataQueryRequest;
use DavitVardanyan\NetdataLaravel\Events\MetricThresholdExceeded;
use DavitVardanyan\NetdataLaravel\NetdataManager;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;

final class ThresholdMonitor
{
    private const string CACHE_KEY = 'netdata:threshold_monitor:breach_start';

    public function __construct(
        private readonly NetdataManager $manager,
        private readonly Repository $cache,
        private readonly Dispatcher $events,
    ) {}

    /**
     * Check all configured threshold rules.
     */
    public function check(?string $connection = null): void
    {
        $connectionName = $connection ?? $this->manager->getDefaultConnection();
        $client = $this->manager->connection($connectionName);

        /** @var array<int, array<string, mixed>> $rules */
        $rules = config('netdata.monitoring.thresholds.rules', []);

        foreach ($rules as $index => $rule) {
            /** @var string $context */
            $context = $rule['context'] ?? '';
            /** @var string $dimension */
            $dimension = $rule['dimension'] ?? '';
            /** @var string $operator */
            $operator = $rule['operator'] ?? '>';
            /** @var float|int $threshold */
            $threshold = $rule['value'] ?? 0;
            /** @var int|null $duration */
            $duration = $rule['duration'] ?? null;
            /** @var string $severity */
            $severity = $rule['severity'] ?? 'warning';

            if ($context === '' || $dimension === '') {
                continue;
            }

            $request = DataQueryRequest::make()
                ->contexts($context)
                ->after(-60)
                ->points(1);

            $metricData = $client->data()->query($request);
            $latestValue = $metricData->latest($dimension);

            if ($latestValue === null) {
                $this->clearBreachStart($connectionName, $index);

                continue;
            }

            $breached = $this->evaluateOperator((float) $latestValue, $operator, (float) $threshold);

            if (! $breached) {
                $this->clearBreachStart($connectionName, $index);

                continue;
            }

            if ($duration !== null && $duration > 0) {
                $breachStart = $this->getBreachStart($connectionName, $index);

                if ($breachStart === null) {
                    $this->setBreachStart($connectionName, $index, time());

                    continue;
                }

                if ((time() - $breachStart) < $duration) {
                    continue;
                }
            }

            $this->events->dispatch(new MetricThresholdExceeded(
                context: $context,
                dimension: $dimension,
                value: (float) $latestValue,
                threshold: (float) $threshold,
                operator: $operator,
                severity: $severity,
                connection: $connectionName,
            ));
        }
    }

    /**
     * Evaluate a threshold operator.
     */
    private function evaluateOperator(float $value, string $operator, float $threshold): bool
    {
        return match ($operator) {
            '>' => $value > $threshold,
            '>=' => $value >= $threshold,
            '<' => $value < $threshold,
            '<=' => $value <= $threshold,
            '==' => $value === $threshold,
            '!=' => $value !== $threshold,
            default => false,
        };
    }

    private function getBreachStart(string $connection, int $ruleIndex): ?int
    {
        /** @var int|null $value */
        $value = $this->cache->get(self::CACHE_KEY.':'.$connection.':'.$ruleIndex);

        return $value;
    }

    private function setBreachStart(string $connection, int $ruleIndex, int $timestamp): void
    {
        $this->cache->put(self::CACHE_KEY.':'.$connection.':'.$ruleIndex, $timestamp, 86400);
    }

    private function clearBreachStart(string $connection, int $ruleIndex): void
    {
        $this->cache->forget(self::CACHE_KEY.':'.$connection.':'.$ruleIndex);
    }
}
