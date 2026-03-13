<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Commands;

use DavitVardanyan\Netdata\DTOs\Context;
use DavitVardanyan\Netdata\Exceptions\NetdataException;
use DavitVardanyan\NetdataLaravel\NetdataManager;
use Illuminate\Console\Command;

final class NetdataContextsCommand extends Command
{
    protected $signature = 'netdata:contexts
        {--connection= : The connection to use}
        {--filter= : Filter contexts by substring match on ID}
        {--json : Output as JSON}';

    protected $description = 'List available Netdata contexts';

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

            $contexts = $client->contexts()->list();

            /** @var string|null $filter */
            $filter = $this->option('filter');
            if ($filter !== null) {
                $contexts = $contexts->filter(
                    fn (Context $ctx): bool => str_contains($ctx->id, $filter),
                );
            }

            if ($this->option('json')) {
                $this->line(json_encode($contexts, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

                return self::SUCCESS;
            }

            if ($contexts->isEmpty()) {
                $this->components->info('No contexts found.');

                return self::SUCCESS;
            }

            $rows = [];
            foreach ($contexts as $ctx) {
                /** @var Context $ctx */
                $rows[] = [
                    $ctx->id,
                    $ctx->family,
                    $ctx->chartType,
                    $ctx->units,
                    (string) $ctx->priority,
                ];
            }

            $this->table(
                ['ID', 'Family', 'Chart Type', 'Units', 'Priority'],
                $rows,
            );

            $this->newLine();
            $this->components->info("{$contexts->count()} contexts found.");

            return self::SUCCESS;
        } catch (NetdataException $e) {
            $this->components->error("Failed to fetch contexts: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
