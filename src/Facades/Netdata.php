<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Facades;

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
use DavitVardanyan\NetdataLaravel\NetdataManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static DataResource data()
 * @method static WeightsResource weights()
 * @method static ContextResource contexts()
 * @method static NodeResource nodes()
 * @method static AlertResource alerts()
 * @method static FunctionResource functions()
 * @method static InfoResource info()
 * @method static SearchResource search()
 * @method static BadgeResource badges()
 * @method static AllMetricsResource allMetrics()
 * @method static ConfigResource config()
 * @method static StreamPathResource streamPath()
 * @method static ClaimResource claim()
 * @method static \DavitVardanyan\Netdata\NetdataClient connection(?string $name = null)
 * @method static void flushCache(?string $resource = null, ?string $context = null)
 *
 * @see NetdataManager
 */
final class Netdata extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return NetdataManager::class;
    }
}
