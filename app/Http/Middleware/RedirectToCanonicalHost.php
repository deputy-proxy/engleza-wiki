<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToCanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $canonicalHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! is_string($canonicalHost) || $canonicalHost === '' || strcasecmp($request->getHost(), $canonicalHost) === 0) {
            return $next($request);
        }

        $canonicalUrl = rtrim((string) config('app.url'), '/').$request->getRequestUri();

        return redirect()->to($canonicalUrl, 301);
    }
}
