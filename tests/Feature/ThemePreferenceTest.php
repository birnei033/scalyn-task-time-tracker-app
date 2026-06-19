<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_pages_render_light_theme_without_toggle(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('const resolvedTheme = "light";', false);
        $response->assertDontSee('data-theme-toggle', false);
    }

    public function test_authenticated_pages_render_light_theme_without_toggle(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('const resolvedTheme = "light";', false);
        $response->assertDontSee('data-theme-toggle', false);
    }

    public function test_profile_updates_ignore_theme_preference(): void
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

        $this->assertSame('light', $user->theme_preference);
    }

    public function test_profile_updates_without_theme_preference(): void
    {
        $user = User::factory()->create([
            'theme_preference' => 'light',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('light', $user->theme_preference);
    }
}
