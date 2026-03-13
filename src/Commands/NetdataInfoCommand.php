<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Commands;

use DavitVardanyan\Netdata\Exceptions\NetdataException;
use DavitVardanyan\NetdataLaravel\NetdataManager;
use Illuminate\Console\Command;

final class NetdataInfoCommand extends Command
{
    protected $signature = 'netdata:info
        {--connection= : The connection to use}
        {--json : Output as JSON}';

    protected $description = 'Show Netdata agent information';

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

            $info = $client->info()->get();

            if ($this->option('json')) {
                $this->line(json_encode($info, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

                return self::SUCCESS;
            }

            $this->components->twoColumnDetail('Version', $info->version);
            $this->components->twoColumnDetail('UID', $info->uid);
            $this->components->twoColumnDetail('Hostname', $info->hostname);
            $this->components->twoColumnDetail('OS', $info->os);
            $this->components->twoColumnDetail('Architecture', $info->architecture);
            $this->components->twoColumnDetail('CPUs', (string) $info->cpus);

            if ($info->hostLabels !== []) {
                $this->newLine();
                $this->components->info('Host Labels:');
                foreach ($info->hostLabels as $key => $value) {
                    $this->components->twoColumnDetail($key, $value);
                }
            }

            return self::SUCCESS;
        } catch (NetdataException $e) {
            $this->components->error("Failed to fetch info: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
