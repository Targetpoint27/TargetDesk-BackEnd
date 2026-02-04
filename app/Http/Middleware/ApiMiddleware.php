<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Set headers for API responses
        $response = $next($request);

        // Don't override Content-Type for file endpoints (download/preview)
        if (!$this->isFileEndpoint($request)) {
            $response->headers->set('Content-Type', 'application/json');
        }

        $response->headers->set('X-API-Version', '1.0.0');

        return $response;
    }

    /**
     * Check if the request is for a file endpoint that should not have JSON content type
     */
    private function isFileEndpoint(Request $request): bool
    {
        $uri = $request->getRequestUri();

        return str_contains($uri, '/download') || str_contains($uri, '/preview');
    }
}
