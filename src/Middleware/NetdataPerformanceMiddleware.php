<?php

declare(strict_types=1);

namespace DavitVardanyan\NetdataLaravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class NetdataPerformanceMiddleware
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): SymfonyResponse  $next
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = (microtime(true) - $startTime) * 1000;

        $this->logger->info('Request performance', [
            'method' => $request->method(),
            'uri' => $request->getRequestUri(),
            'duration_ms' => round($duration, 2),
            'status' => $response->getStatusCode(),
        ]);

        return $response;
    }
}
