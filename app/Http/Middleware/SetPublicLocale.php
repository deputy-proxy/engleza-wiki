<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Number;
use Symfony\Component\HttpFoundation\Response;

class SetPublicLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin*')) {
            App::setLocale('en');
            Date::setLocale('en');
            Number::useLocale('en');

            return $next($request);
        }

        App::setLocale('ro');
        Date::setLocale('ro');
        Number::useLocale('ro');

        return $next($request);
    }
}
