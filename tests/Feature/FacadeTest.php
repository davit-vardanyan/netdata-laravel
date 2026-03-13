<?php

declare(strict_types=1);

use DavitVardanyan\Netdata\NetdataClient;
use DavitVardanyan\Netdata\Resources\AlertResource;
use DavitVardanyan\Netdata\Resources\AllMetricsResource;
use DavitVardanyan\Netdata\Resources\BadgeResource;
use DavitVardanyan\Netdata\Resources\ClaimResource;
use DavitVardanyan\Netdata\Resources\ConfigResource;
use DavitVardanyan\Netdata\Resources\ContextResource;
use DavitVardanyan\Netdata\Resources\DataResource;
use DavitVardanyan\Netdata\Resources\FunctionResource;
use DavitVardanyan\Netdata\Resources\InfoResource;
use DavitVardanyan\Netdata\Resources\NodeResource;
use DavitVardanyan\Netdata\Resources\SearchResource;
use DavitVardanyan\Netdata\Resources\StreamPathResource;
use DavitVardanyan\Netdata\Resources\WeightsResource;
use DavitVardanyan\Netdata\Testing\NetdataFake;
use DavitVardanyan\NetdataLaravel\Facades\Netdata;
use DavitVardanyan\NetdataLaravel\NetdataManager;

test('facade resolves NetdataManager', function () {
    expect(Netdata::getFacadeRoot())->toBeInstanceOf(NetdataManager::class);
});

test('facade proxies all 13 resource methods', function () {
    $fake = NetdataFake::create();
    $manager = app(NetdataManager::class);
    $manager->setClient('default', $fake->toClient());

    expect(Netdata::data())->toBeInstanceOf(DataResource::class);
    expect(Netdata::weights())->toBeInstanceOf(WeightsResource::class);
    expect(Netdata::contexts())->toBeInstanceOf(ContextResource::class);
    expect(Netdata::nodes())->toBeInstanceOf(NodeResource::class);
    expect(Netdata::alerts())->toBeInstanceOf(AlertResource::class);
    expect(Netdata::functions())->toBeInstanceOf(FunctionResource::class);
    expect(Netdata::info())->toBeInstanceOf(InfoResource::class);
    expect(Netdata::search())->toBeInstanceOf(SearchResource::class);
    expect(Netdata::badges())->toBeInstanceOf(BadgeResource::class);
    expect(Netdata::allMetrics())->toBeInstanceOf(AllMetricsResource::class);
    expect(Netdata::config())->toBeInstanceOf(ConfigResource::class);
    expect(Netdata::streamPath())->toBeInstanceOf(StreamPathResource::class);
    expect(Netdata::claim())->toBeInstanceOf(ClaimResource::class);
});

test('facade connection method returns NetdataClient', function () {
    $client = Netdata::connection();
    expect($client)->toBeInstanceOf(NetdataClient::class);
});
