<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Commands;

use DavitVardanyan\Netdata\DTOs\Alert;
use DavitVardanyan\Netdata\Enums\AlertStatus;
use DavitVardanyan\Netdata\Exceptions\NetdataException;
use DavitVardanyan\NetdataLaravel\NetdataManager;
use Illuminate\Console\Command;

final class NetdataAlertsCommand extends Command
{
    protected $signature = 'netdata:alerts
        {--connection= : The connection to use}
        {--status= : Filter by status (critical, warning, clear)}
        {--json : Output as JSON}';

    protected $description = 'List active Netdata alerts';

    public function __construct(
        private readonly NetdataManager $manager,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            /** @var string|null $connection */
            $connection = $this->option('connection');
            /** @var string|null $status */
            $status = $this->option('status');

            $client = $this->manager->connection($connection);
            $alerts = $client->alerts()->list(status: $status);

            if ($this->option('json')) {
                $this->line(json_encode($alerts, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

                return self::SUCCESS;
            }

            if ($alerts->isEmpty()) {
                $this->components->info('All clear — no active alerts.');

                return self::SUCCESS;
            }

            $critical = 0;
            $warning = 0;
            $rows = [];

            foreach ($alerts as $alert) {
                /** @var Alert $alert */
                $statusBadge = match ($alert->status) {
                    AlertStatus::Critical => '<fg=red>CRITICAL</>',
                    AlertStatus::Warning => '<fg=yellow>WARNING</>',
                    AlertStatus::Clear => '<fg=green>CLEAR</>',
                    default => $alert->status->value,
                };

                if ($alert->status === AlertStatus::Critical) {
                    $critical++;
                } elseif ($alert->status === AlertStatus::Warning) {
                    $warning++;
                }

                $rows[] = [
                    $statusBadge,
                    $alert->name,
                    $alert->chart,
                    $alert->info,
                ];
            }

            $this->table(
                ['Status', 'Alert Name', 'Chart', 'Info'],
                $rows,
            );

            $this->newLine();
            $this->components->info("{$alerts->count()} active alerts: {$critical} critical, {$warning} warning.");

            return self::SUCCESS;
        } catch (NetdataException $e) {
            $this->components->error("Failed to fetch alerts: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
