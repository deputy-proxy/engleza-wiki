<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class HealthController
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->getPdo();

            if (! Schema::hasTable('users')
                || ! Schema::hasTable('migrations')
                || ! Schema::hasTable('resources')
                || ! Schema::hasTable('speaking_resources')) {
                return response()->json(['status' => 'down'], 503);
            }
        } catch (Throwable) {
            return response()->json(['status' => 'down'], 503);
        }

        return response()->json(['status' => 'up']);
    }
}
