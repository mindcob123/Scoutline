<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// This middleware prevents the browser's back button from showing cached 
class PreventBackHistory
{
    /**
     * sensitive pages by adding strong no-cache HTTP headers.
     * @param  Request  $request     // The incoming HTTP request
     * @param  Closure  $next        // The next middleware or controller to process the request
     * @return Response              // The HTTP response after processing
     */
    public function handle($request, \Closure $next)
    {
        $response = $next($request);

        return $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                        ->header('Pragma', 'no-cache')
                        ->header('Expires', 'Sat, 01 Jan 1990 00:00:00 GMT');
    }
}