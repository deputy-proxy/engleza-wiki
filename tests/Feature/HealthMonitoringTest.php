<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PDOException;
use Tests\TestCase;

class HealthMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_endpoint_confirms_database_connectivity_and_core_schema(): void
    {
        $this->getJson('/health/ready')
            ->assertOk()
            ->assertExactJson(['status' => 'up']);
    }

    public function test_readiness_endpoint_returns_generic_unavailable_response_when_database_is_unreachable(): void
    {
        DB::shouldReceive('connection')
            ->once()
            ->andThrow(new PDOException('sensitive database connection detail'));

        $this->getJson('/health/ready')
            ->assertServiceUnavailable()
            ->assertExactJson(['status' => 'down'])
            ->assertDontSee('sensitive database connection detail');
    }
}
