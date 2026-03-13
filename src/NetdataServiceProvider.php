<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel;

use DavitVardanyan\Netdata\NetdataClient;
use DavitVardanyan\NetdataLaravel\Commands\NetdataAlertsCommand;
use DavitVardanyan\NetdataLaravel\Commands\NetdataContextsCommand;
use DavitVardanyan\NetdataLaravel\Commands\NetdataFunctionsCommand;
use DavitVardanyan\NetdataLaravel\Commands\NetdataHealthCommand;
use DavitVardanyan\NetdataLaravel\Commands\NetdataInfoCommand;
use DavitVardanyan\NetdataLaravel\Commands\NetdataMetricsCommand;
use DavitVardanyan\NetdataLaravel\Commands\NetdataNodesCommand;
use DavitVardanyan\NetdataLaravel\Commands\NetdataTestCommand;
use DavitVardanyan\NetdataLaravel\Monitoring\AlertPoller;
use DavitVardanyan\NetdataLaravel\Monitoring\NodeStatusPoller;
use DavitVardanyan\NetdataLaravel\Monitoring\ThresholdMonitor;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

/**
 * Laravel service provider for the Netdata SDK wrapper.
 *
 * Registers the NetdataManager singleton, binds the NetdataClient to the
 * default connection, publishes configuration, registers artisan commands,
 * and schedules monitoring tasks when enabled.
 */
final class NetdataServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/netdata.php',
            'netdata',
        );

        $this->app->singleton(NetdataManager::class, function ($app) {
            return new NetdataManager($app);
        });

        $this->app->bind(NetdataClient::class, function ($app) {
            return $app->make(NetdataManager::class)->connection();
        });
    }

    /**
     * Bootstrap package services.
     *
     * Publishes config, registers commands, and schedules monitoring tasks.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/netdata.php' => $this->app->configPath('netdata.php'),
            ], 'netdata-config');

            $this->commands([
                NetdataTestCommand::class,
                NetdataNodesCommand::class,
                NetdataAlertsCommand::class,
                NetdataHealthCommand::class,
                NetdataMetricsCommand::class,
                NetdataContextsCommand::class,
                NetdataInfoCommand::class,
                NetdataFunctionsCommand::class,
            ]);
        }

        $this->scheduleMonitoring();
    }

    /**
     * Register scheduled monitoring tasks if enabled in config.
     *
     * Uses `callAfterResolving` so that the schedule is only configured
     * when the Schedule class is actually resolved by the framework.
     */
    private function scheduleMonitoring(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $this->scheduleAlertPolling($schedule);
            $this->scheduleNodePolling($schedule);
            $this->scheduleThresholdMonitoring($schedule);
        });
    }

    /**
     * Schedule alert polling if enabled.
     *
     * @param  Schedule  $schedule  The Laravel scheduler instance.
     */
    private function scheduleAlertPolling(Schedule $schedule): void
    {
        /** @var bool $enabled */
        $enabled = config('netdata.monitoring.alerts.enabled', false);

        if (! $enabled) {
            return;
        }

        /** @var int $interval */
        $interval = config('netdata.monitoring.alerts.poll_interval', 60);

        $event = $schedule->call(fn () => $this->app->make(AlertPoller::class)->poll());
        $this->applyInterval($event, $interval);
        $event->name('netdata:poll-alerts')
            ->withoutOverlapping();
    }

    /**
     * Schedule node status polling if enabled.
     *
     * @param  Schedule  $schedule  The Laravel scheduler instance.
     */
    private function scheduleNodePolling(Schedule $schedule): void
    {
        /** @var bool $enabled */
        $enabled = config('netdata.monitoring.nodes.enabled', false);

        if (! $enabled) {
            return;
        }

        /** @var int $interval */
        $interval = config('netdata.monitoring.nodes.poll_interval', 120);

        $event = $schedule->call(fn () => $this->app->make(NodeStatusPoller::class)->poll());
        $this->applyInterval($event, $interval);
        $event->name('netdata:poll-nodes')
            ->withoutOverlapping();
    }

    /**
     * Schedule threshold monitoring if enabled.
     *
     * @param  Schedule  $schedule  The Laravel scheduler instance.
     */
    private function scheduleThresholdMonitoring(Schedule $schedule): void
    {
        /** @var bool $enabled */
        $enabled = config('netdata.monitoring.thresholds.enabled', false);

        if (! $enabled) {
            return;
        }

        /** @var int $interval */
        $interval = config('netdata.monitoring.thresholds.poll_interval', 60);

        $event = $schedule->call(fn () => $this->app->make(ThresholdMonitor::class)->check());
        $this->applyInterval($event, $interval);
        $event->name('netdata:check-thresholds')
            ->withoutOverlapping();
    }

    /**
     * Apply the configured interval (in seconds) to a scheduled event.
     *
     * Converts the interval to the most appropriate Laravel scheduling frequency.
     * For intervals under 60 seconds, sub-minute scheduling is used. For intervals
     * of 60 seconds or more, a cron expression with the appropriate minute interval
     * is generated.
     *
     * @param  CallbackEvent  $event  The scheduled event to configure.
     * @param  int  $interval  The interval in seconds.
     */
    private function applyInterval(CallbackEvent $event, int $interval): void
    {
        if ($interval < 60) {
            // Sub-minute scheduling: map to the closest available Laravel method.
            match (true) {
                $interval <= 1 => $event->everySecond(),
                $interval <= 2 => $event->everyTwoSeconds(),
                $interval <= 5 => $event->everyFiveSeconds(),
                $interval <= 10 => $event->everyTenSeconds(),
                $interval <= 15 => $event->everyFifteenSeconds(),
                $interval <= 20 => $event->everyTwentySeconds(),
                default => $event->everyThirtySeconds(),
            };

            return;
        }

        $minutes = max(1, (int) round($interval / 60));

        if ($minutes === 1) {
            $event->everyMinute();
        } else {
            $event->cron("*/{$minutes} * * * *");
        }
    }
}
