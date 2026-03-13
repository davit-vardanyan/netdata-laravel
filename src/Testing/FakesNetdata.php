<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Testing;

use DavitVardanyan\Netdata\NetdataClient;
use DavitVardanyan\Netdata\Testing\NetdataFake;
use DavitVardanyan\NetdataLaravel\NetdataManager;
use Illuminate\Foundation\Application;

trait FakesNetdata
{
    /**
     * Replace the Netdata bindings with a fake for testing.
     *
     * Creates a NetdataFake instance and injects its client into the
     * NetdataManager via `setClient()`. Also rebinds the NetdataClient
     * in the container so that direct injection uses the fake as well.
     */
    protected function fakeNetdata(): NetdataFake
    {
        $fake = NetdataFake::create();
        $client = $fake->toClient();

        /** @var Application $app */
        $app = $this->app;

        /** @var NetdataManager $manager */
        $manager = $app->make(NetdataManager::class);
        $manager->setClient($manager->getDefaultConnection(), $client);

        $app->instance(NetdataClient::class, $client);

        return $fake;
    }
}
