<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('get')) {
            $key = 'response_' . md5($request->fullUrl());

            if (Cache::has($key)) {
                return Cache::get($key);
            }

            $response = $next($request);

            // Cache for 24 hours (since data is static)
            Cache::put($key, $response, now()->addDay());

            return $response;
        }

        return $next($request);
    }
}