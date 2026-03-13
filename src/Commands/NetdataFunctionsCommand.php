<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Commands;

use DavitVardanyan\Netdata\Exceptions\NetdataException;
use DavitVardanyan\NetdataLaravel\NetdataManager;
use Illuminate\Console\Command;

final class NetdataFunctionsCommand extends Command
{
    protected $signature = 'netdata:functions
        {--connection= : The connection to use}
        {--execute= : Execute a specific function by name}
        {--json : Output as JSON}';

    protected $description = 'List or execute Netdata agent functions';

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

            /** @var string|null $execute */
            $execute = $this->option('execute');

            if ($execute !== null) {
                return $this->executeFunction($execute, $connection);
            }

            $functions = $client->functions()->list();

            if ($this->option('json')) {
                $this->line(json_encode($functions, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

                return self::SUCCESS;
            }

            if ($functions === []) {
                $this->components->info('No functions available.');

                return self::SUCCESS;
            }

            // Functions response format varies, display as table if possible
            $rows = [];
            foreach ($functions as $key => $value) {
                if (is_array($value) && isset($value['name'])) {
                    $rows[] = [
                        $value['name'],
                        $value['description'] ?? 'N/A',
                    ];
                } elseif (is_string($key)) {
                    $rows[] = [$key, is_string($value) ? $value : json_encode($value)];
                }
            }

            if ($rows !== []) {
                $this->table(['Function', 'Description'], $rows);
            } else {
                $this->line(json_encode($functions, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            }

            return self::SUCCESS;
        } catch (NetdataException $e) {
            $this->components->error("Failed to fetch functions: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * Execute a specific Netdata function by name.
     *
     * @param  string  $name  The function name to execute
     * @param  string|null  $connection  The connection to use
     */
    private function executeFunction(string $name, ?string $connection): int
    {
        try {
            $client = $this->manager->connection($connection);
            $result = $client->functions()->execute($name);

            if ($this->option('json')) {
                $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

                return self::SUCCESS;
            }

            $this->components->info("Function '{$name}' executed successfully:");
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (NetdataException $e) {
            $this->components->error("Failed to execute function '{$name}': {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
