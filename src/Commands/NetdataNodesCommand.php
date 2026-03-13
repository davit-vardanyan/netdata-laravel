<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Commands;

use DavitVardanyan\Netdata\DTOs\Node;
use DavitVardanyan\Netdata\Exceptions\NetdataException;
use DavitVardanyan\NetdataLaravel\NetdataManager;
use Illuminate\Console\Command;

final class NetdataNodesCommand extends Command
{
    protected $signature = 'netdata:nodes
        {--connection= : The connection to use}
        {--json : Output as JSON}';

    protected $description = 'List all Netdata nodes';

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
            $nodes = $client->nodes()->list();

            if ($this->option('json')) {
                $this->line(json_encode($nodes, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

                return self::SUCCESS;
            }

            if ($nodes->isEmpty()) {
                $this->components->info('No nodes found.');

                return self::SUCCESS;
            }

            $rows = [];
            foreach ($nodes as $node) {
                /** @var Node $node */
                $rows[] = [
                    $node->name,
                    $node->os,
                    $node->version ?? 'N/A',
                    $node->architecture ?? 'N/A',
                    $node->cpus !== null ? (string) $node->cpus : 'N/A',
                    $node->memory ?? 'N/A',
                ];
            }

            $this->table(
                ['Name', 'OS', 'Version', 'Architecture', 'CPUs', 'Memory'],
                $rows,
            );

            $this->newLine();
            $this->components->info("{$nodes->count()} nodes found.");

            return self::SUCCESS;
        } catch (NetdataException $e) {
            $this->components->error("Failed to fetch nodes: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
