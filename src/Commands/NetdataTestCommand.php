<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Commands;

use DavitVardanyan\Netdata\Exceptions\NetdataException;
use DavitVardanyan\NetdataLaravel\NetdataManager;
use Illuminate\Console\Command;

final class NetdataTestCommand extends Command
{
    protected $signature = 'netdata:test {--connection= : The connection to test}';

    protected $description = 'Test connectivity to the Netdata API';

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
        /** @var string|null $connection */
        $connection = $this->option('connection');

        $connectionName = $connection ?? $this->manager->getDefaultConnection();
        $this->components->info("Testing connection: {$connectionName}");

        try {
            $startTime = microtime(true);
            $client = $this->manager->connection($connection);
            $info = $client->info()->get();
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            /** @var array<string, mixed> $connectionConfig */
            $connectionConfig = config("netdata.connections.{$connectionName}", []);
            /** @var string $baseUrl */
            $baseUrl = $connectionConfig['base_url'] ?? 'https://registry.my-netdata.io';

            $this->components->twoColumnDetail('Connection', $connectionName);
            $this->components->twoColumnDetail('Base URL', $baseUrl);
            $this->components->twoColumnDetail('Agent Version', $info->version);
            $this->components->twoColumnDetail('Hostname', $info->hostname);
            $this->components->twoColumnDetail('Response Time', "{$duration}ms");

            $this->newLine();
            $this->components->info('Connection successful!');

            return self::SUCCESS;
        } catch (NetdataException $e) {
            $this->components->error("Connection failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
