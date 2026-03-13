<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Events;

use DavitVardanyan\Netdata\DTOs\Node;

final readonly class NodeCameOnline
{
    public function __construct(
        public Node $node,
        public string $connection,
        public \DateTimeImmutable $detectedAt,
    ) {}
}
