<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_pages_render_the_theme_toggle(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('data-theme-toggle', false);
    }

    public function test_authenticated_pages_render_the_theme_toggle_and_saved_theme(): void
    {
        $user = User::factory()->create([
            'theme_preference' => 'dark',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('data-theme-toggle', false);
        $response->assertSee('data-theme-preference="dark"', false);
    }

    public function test_profile_theme_preference_is_persisted(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'theme_preference' => 'dark',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('dark', $user->theme_preference);
    }

    public function test_profile_theme_preference_can_be_reset_to_system(): void
    {
        $user = User::factory()->create([
            'theme_preference' => 'light',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'theme_preference' => 'system',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertNull($user->theme_preference);
    }
}
