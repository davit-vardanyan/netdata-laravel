<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Health;

use DavitVardanyan\Netdata\Enums\AlertStatus;
use DavitVardanyan\Netdata\Exceptions\NetdataException;
use DavitVardanyan\NetdataLaravel\NetdataManager;

final class NetdataHealthCheck
{
    private string $connectionName = 'default';

    public function __construct(
        private readonly NetdataManager $manager,
    ) {}

    /**
     * Set the connection name to check.
     */
    public function connection(string $name): static
    {
        $this->connectionName = $name;

        return $this;
    }

    /**
     * Run the health check.
     *
     * Tests connectivity by fetching agent info, then checks for critical
     * alerts. Returns a Result indicating ok, warning, or failed status.
     */
    public function run(): Result
    {
        $client = $this->manager->connection($this->connectionName);

        try {
            $client->info()->get();
        } catch (NetdataException $e) {
            return Result::failed("Netdata connection [{$this->connectionName}] is unreachable: {$e->getMessage()}");
        } catch (\Throwable $e) {
            return Result::failed("Netdata connection [{$this->connectionName}] failed: {$e->getMessage()}");
        }

        try {
            $alerts = $client->alerts()->list();

            $hasCritical = false;
            $hasWarning = false;

            foreach ($alerts as $alert) {
                if ($alert->status === AlertStatus::Critical) {
                    $hasCritical = true;

                    break;
                }

                if ($alert->status === AlertStatus::Warning) {
                    $hasWarning = true;
                }
            }

            if ($hasCritical) {
                return Result::failed("Netdata connection [{$this->connectionName}] has critical alerts.");
            }

            if ($hasWarning) {
                return Result::warning("Netdata connection [{$this->connectionName}] has warning alerts.");
            }

            return Result::ok("Netdata connection [{$this->connectionName}] is healthy.");
        } catch (NetdataException $e) {
            return Result::warning("Netdata connection [{$this->connectionName}] is reachable but alerts could not be checked: {$e->getMessage()}");
        } catch (\Throwable $e) {
            return Result::warning("Netdata connection [{$this->connectionName}] is reachable but alerts check failed: {$e->getMessage()}");
        }
    }
}
