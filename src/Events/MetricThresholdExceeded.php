<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Events;

final readonly class MetricThresholdExceeded
{
    public function __construct(
        public string $context,
        public string $dimension,
        public float $value,
        public float $threshold,
        public string $operator,
        public string $severity,
        public string $connection,
    ) {}
}
