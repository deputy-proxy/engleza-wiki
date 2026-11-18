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

        $locale = (string) config('app.locale', 'ro');
        App::setLocale($locale);
        Date::setLocale($locale);
        Number::useLocale($locale);

        return $next($request);
    }
}
