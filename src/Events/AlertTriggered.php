<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Events;

use DavitVardanyan\Netdata\DTOs\Alert;

final readonly class AlertTriggered
{
    public function __construct(
        public Alert $alert,
        public string $connection,
    ) {}
}
