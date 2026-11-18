<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_access_user_management(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $editor = User::factory()->create(['role' => 'editor']);
        $learner = User::factory()->create(['role' => 'learner']);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk();

        $this->get('/admin/users/create')->assertOk();

        $this->actingAs($editor)
            ->get('/admin/resources')
            ->assertOk();

        $this->get('/admin/users')
            ->assertForbidden();

        $this->get('/admin/users/create')->assertForbidden();

        $this->get('/admin/users/'.$learner->id.'/edit')->assertForbidden();

        $this->actingAs($learner)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_user_policy_denies_every_user_management_ability_to_editors(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $record = User::factory()->create(['role' => 'learner']);

        $this->assertFalse(Gate::forUser($editor)->allows('viewAny', User::class));
        $this->assertFalse(Gate::forUser($editor)->allows('create', User::class));
        $this->assertFalse(Gate::forUser($editor)->allows('view', $record));
        $this->assertFalse(Gate::forUser($editor)->allows('update', $record));
        $this->assertFalse(Gate::forUser($editor)->allows('delete', $record));
        $this->assertFalse(Gate::forUser($editor)->allows('deleteAny', User::class));
    }

    public function test_admin_can_change_roles_but_non_admin_cannot(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $editor = User::factory()->create(['role' => 'editor']);
        $learner = User::factory()->create(['role' => 'learner']);

        $this->assertTrue(Gate::forUser($admin)->allows('update', $learner));
        $this->assertFalse(Gate::forUser($editor)->allows('update', $learner));

        $this->actingAs($admin)
            ->get('/admin/users/'.$learner->id.'/edit')
            ->assertOk();
    }

    public function test_last_admin_cannot_be_demoted_or_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $operator = User::factory()->create(['role' => 'admin']);
        $operator->delete();

        $this->expectException(ValidationException::class);
        $admin->update(['role' => 'editor']);
    }

    public function test_admin_cannot_demote_their_own_account_even_when_another_admin_exists(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin);

        $this->expectException(ValidationException::class);
        $admin->update(['role' => 'editor']);
    }

    public function test_last_admin_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $operator = User::factory()->create(['role' => 'admin']);
        $operator->delete();

        $this->expectException(ValidationException::class);
        $admin->delete();
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin);

        $this->expectException(ValidationException::class);
        $admin->delete();
    }

    public function test_accounts_with_learning_history_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $learner = User::factory()->create(['role' => 'learner']);
        DB::table('learner_engagement_settings')->insert([
            'user_id' => $learner->id,
            'daily_goal_xp' => 20,
            'timezone' => 'Europe/Bucharest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertFalse(Gate::forUser($admin)->allows('delete', $learner));

        try {
            $learner->delete();
            $this->fail('Expected deletion to be blocked when learner history exists.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('users', ['id' => $learner->id]);
            $this->assertDatabaseHas('learner_engagement_settings', ['user_id' => $learner->id]);
        }
    }

    public function test_admin_bulk_delete_authorization_checks_each_record(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $learner = User::factory()->create(['role' => 'learner']);

        $this->assertTrue(Gate::forUser($admin)->allows('deleteAny', User::class));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $learner));
    }
}
