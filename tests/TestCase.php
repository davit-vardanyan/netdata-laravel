<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Tests;

use DavitVardanyan\NetdataLaravel\Facades\Netdata;
use DavitVardanyan\NetdataLaravel\NetdataServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            NetdataServiceProvider::class,
        ];
    }

    /**
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'Netdata' => Netdata::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('netdata.connections.default.token', 'test-token');
    }
}
