<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_user_with_flash_feedback(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $operations = Team::factory()->create(['name' => 'Operations']);
        $delivery = Team::factory()->create(['name' => 'Delivery']);

        $createResponse = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'New Manager',
            'email' => 'manager@example.com',
            'role' => 'manager',
            'team_id' => $operations->id,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $createResponse->assertRedirect(route('users.index'));
        $createResponse->assertSessionHas('status', 'User created.');

        $user = User::where('email', 'manager@example.com')->firstOrFail();

        $updateResponse = $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => 'Updated Manager',
            'email' => 'updated-manager@example.com',
            'role' => 'member',
            'team_id' => $delivery->id,
        ]);

        $updateResponse->assertRedirect(route('users.index'));
        $updateResponse->assertSessionHas('status', 'User updated.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Manager',
            'email' => 'updated-manager@example.com',
            'role' => 'member',
            'team_id' => $delivery->id,
        ]);

        $deleteResponse = $this->actingAs($admin)->delete(route('users.destroy', $user));

        $deleteResponse->assertRedirect(route('users.index'));
        $deleteResponse->assertSessionHas('status', 'User deleted.');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_users_index_supports_search_filters_and_pagination(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $operations = Team::factory()->create(['name' => 'Operations']);
        $delivery = Team::factory()->create(['name' => 'Delivery']);

        User::factory()->count(12)->create([
            'role' => 'member',
            'team_id' => $operations->id,
            'name' => 'Operations Member',
        ]);

        $filteredUser = User::factory()->create([
            'role' => 'manager',
            'team_id' => $delivery->id,
            'name' => 'Bluebird Lead',
            'email' => 'bluebird@example.com',
        ]);

        $response = $this->actingAs($admin)->get(route('users.index', [
            'search' => 'Bluebird',
            'role' => 'manager',
            'team_id' => $delivery->id,
        ]));

        $response->assertOk();
        $response->assertSee('Bluebird Lead');
        $response->assertDontSee('Operations Member');

        $this->actingAs($admin)->get(route('users.index'))
            ->assertOk()
            ->assertSee('Next');

        $this->assertTrue($filteredUser->exists);
    }

    public function test_users_index_searches_team_names_and_roles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $delivery = Team::factory()->create(['name' => 'Delivery']);
        $operations = Team::factory()->create(['name' => 'Operations']);

        $teamMatch = User::factory()->create([
            'role' => 'member',
            'team_id' => $delivery->id,
            'name' => 'Delivery Specialist',
        ]);

        $roleMatch = User::factory()->create([
            'role' => 'manager',
            'team_id' => $operations->id,
            'name' => 'Operations Lead',
        ]);

        User::factory()->create([
            'role' => 'member',
            'team_id' => $operations->id,
            'name' => 'Unrelated User',
        ]);

        $this->actingAs($admin)->get(route('users.index', ['search' => 'Delivery']))
            ->assertOk()
            ->assertSee('Delivery Specialist')
            ->assertDontSee('Unrelated User');

        $this->actingAs($admin)->get(route('users.index', ['search' => 'manager']))
            ->assertOk()
            ->assertSee('Operations Lead')
            ->assertDontSee('Unrelated User');

        $this->assertTrue($teamMatch->exists);
        $this->assertTrue($roleMatch->exists);
    }

    public function test_manager_can_update_user_assignment_from_team_page(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $operations = Team::factory()->create(['name' => 'Operations']);
        $target = User::factory()->create(['role' => 'member', 'team_id' => $operations->id]);

        $response = $this->actingAs($manager)->patch(route('team.users.update', $target), [
            'role' => 'manager',
            'team_id' => $operations->id,
        ]);

        $response->assertRedirect(route('team.index'));
        $response->assertSessionHas('status', 'User updated.');

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'role' => 'manager',
            'team_id' => $operations->id,
        ]);
    }

    public function test_member_cannot_access_user_management(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)->get(route('users.index'))->assertForbidden();
        $this->actingAs($member)->get(route('users.create'))->assertForbidden();
    }

    public function test_user_validation_returns_errors(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('users.index'))
            ->post(route('users.store'), [
                'email' => 'invalid-email',
                'role' => 'manager',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHasErrors(['name', 'email']);
    }
}
