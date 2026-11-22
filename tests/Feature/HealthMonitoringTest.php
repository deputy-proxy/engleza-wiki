<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

    public function test_readiness_endpoint_reports_down_when_a_required_application_table_is_missing(): void
    {
        Schema::shouldReceive('hasTable')->with('users')->once()->andReturn(true);
        Schema::shouldReceive('hasTable')->with('migrations')->once()->andReturn(true);
        Schema::shouldReceive('hasTable')->with('resources')->once()->andReturn(true);
        Schema::shouldReceive('hasTable')->with('speaking_resources')->once()->andReturn(false);

        $this->getJson('/health/ready')
            ->assertServiceUnavailable()
            ->assertExactJson(['status' => 'down']);
    }

    public function test_readiness_endpoint_returns_generic_unavailable_response_when_database_is_unreachable(): void
    {
        config(['session.driver' => 'array']);

        DB::partialMock()->shouldReceive('connection')
            ->once()
            ->andThrow(new PDOException('sensitive database connection detail'));

        $this->getJson('/health/ready')
            ->assertServiceUnavailable()
            ->assertExactJson(['status' => 'down'])
            ->assertDontSee('sensitive database connection detail');
    }
}
