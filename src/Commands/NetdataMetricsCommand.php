<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Commands;

use DavitVardanyan\Netdata\Exceptions\NetdataException;
use DavitVardanyan\Netdata\Requests\DataQueryRequest;
use DavitVardanyan\NetdataLaravel\NetdataManager;
use Illuminate\Console\Command;

final class NetdataMetricsCommand extends Command
{
    protected $signature = 'netdata:metrics
        {context : The metric context (e.g., system.cpu)}
        {--connection= : The connection to use}
        {--after= : Start time in seconds relative to now (e.g., -3600)}
        {--points= : Number of data points}
        {--node= : Specific node ID}
        {--dimensions= : Comma-separated dimensions to include}
        {--json : Output as JSON}';

    protected $description = 'Query metric data from Netdata';

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

            /** @var string $context */
            $context = $this->argument('context');

            $request = DataQueryRequest::make()->contexts($context);

            /** @var string|null $after */
            $after = $this->option('after');
            if ($after !== null) {
                $request = $request->after((int) $after);
            }

            /** @var string|null $points */
            $points = $this->option('points');
            if ($points !== null) {
                $request = $request->points((int) $points);
            }

            /** @var string|null $node */
            $node = $this->option('node');
            if ($node !== null) {
                $request = $request->nodes($node);
            }

            /** @var string|null $dimensions */
            $dimensions = $this->option('dimensions');
            if ($dimensions !== null) {
                $request = $request->dimensions(explode(',', $dimensions));
            }

            $data = $client->data()->query($request);

            if ($this->option('json')) {
                $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

                return self::SUCCESS;
            }

            if ($data->isEmpty()) {
                $this->components->info('No data returned.');

                return self::SUCCESS;
            }

            // Build table
            $headers = array_merge(['Timestamp'], $data->labels);
            $rows = [];

            foreach ($data->toTimeSeries() as $point) {
                $row = [date('Y-m-d H:i:s', (int) ($point['timestamp'] ?? 0))];
                foreach ($data->labels as $label) {
                    $value = $point[$label] ?? null;
                    $row[] = $value !== null ? (string) round((float) $value, 2) : 'N/A';
                }
                $rows[] = $row;
            }

            $this->table($headers, $rows);

            // Summary per dimension
            $this->newLine();
            $this->components->info('Summary:');
            foreach ($data->labels as $label) {
                $avg = round($data->average($label), 2);
                $min = round($data->min($label), 2);
                $max = round($data->max($label), 2);
                $latest = $data->latest($label);
                $latestStr = $latest !== null ? (string) round((float) $latest, 2) : 'N/A';

                $this->components->twoColumnDetail(
                    $label,
                    "avg: {$avg}, min: {$min}, max: {$max}, latest: {$latestStr}",
                );
            }

            return self::SUCCESS;
        } catch (NetdataException $e) {
            $this->components->error("Failed to fetch metrics: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
