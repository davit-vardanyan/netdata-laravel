<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Commands;

use DavitVardanyan\Netdata\Exceptions\NetdataException;
use DavitVardanyan\NetdataLaravel\NetdataManager;
use Illuminate\Console\Command;

final class NetdataHealthCommand extends Command
{
    protected $signature = 'netdata:health
        {--connection= : The connection to use}
        {--node= : Specific node ID to check}';

    protected $description = 'Show health overview of Netdata nodes';

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
            $client = $this->manager->connection($connection);

            $cpu = $client->data()->cpu();
            $memory = $client->data()->memory();
            $disk = $client->data()->disk();
            $network = $client->data()->network();

            $this->components->info('System Health Overview');
            $this->newLine();

            if (! $cpu->isEmpty() && $cpu->labels !== []) {
                $cpuLabel = $cpu->labels[0] ?? 'cpu';
                $cpuAvg = round($cpu->average($cpuLabel), 1);
                $this->components->twoColumnDetail('CPU Usage', $this->colorizePercent($cpuAvg));
            }

            if (! $memory->isEmpty() && $memory->labels !== []) {
                $memLabel = $memory->labels[0] ?? 'used';
                $memAvg = round($memory->average($memLabel), 1);
                $this->components->twoColumnDetail('Memory Usage', "{$memAvg}");
            }

            if (! $disk->isEmpty() && $disk->labels !== []) {
                $diskLabel = $disk->labels[0] ?? 'reads';
                $diskAvg = round($disk->average($diskLabel), 1);
                $this->components->twoColumnDetail('Disk I/O', "{$diskAvg}");
            }

            if (! $network->isEmpty() && $network->labels !== []) {
                $netLabel = $network->labels[0] ?? 'received';
                $netAvg = round($network->average($netLabel), 1);
                $this->components->twoColumnDetail('Network', "{$netAvg}");
            }

            return self::SUCCESS;
        } catch (NetdataException $e) {
            $this->components->error("Failed to fetch health data: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * Colorize a percentage value based on severity thresholds.
     */
    private function colorizePercent(float $value): string
    {
        return match (true) {
            $value > 90 => "<fg=red>{$value}%</>",
            $value > 70 => "<fg=yellow>{$value}%</>",
            default => "<fg=green>{$value}%</>",
        };
    }
}
