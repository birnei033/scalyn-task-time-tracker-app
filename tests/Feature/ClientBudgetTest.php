<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Support\TimeDisplay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ClientBudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_budget_is_saved_and_edit_form_rehydrates_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('clients.store'), [
            'name' => 'Acme Studio',
            'company' => 'Acme',
            'contact_person' => 'Alex Admin',
            'email' => 'alex@example.com',
            'budget_per_month' => 480,
            'status' => 'active',
            'notes' => 'Retainer client',
        ])->assertRedirect(route('clients.index'));

        $client = Client::where('name', 'Acme Studio')->firstOrFail();
        $this->assertSame(480, $client->budget_per_month);

        $this->actingAs($admin)->get(route('clients.edit', $client))
            ->assertOk()
            ->assertSee('name="budget_per_month"', false)
            ->assertSee('value="480"', false);

        $this->actingAs($admin)->patch(route('clients.update', $client), [
            'name' => 'Acme Studio Updated',
            'company' => 'Acme',
            'contact_person' => 'Alex Admin',
            'email' => 'updated@example.com',
            'budget_per_month' => 720,
            'status' => 'active',
            'notes' => 'Updated client',
        ])->assertRedirect(route('clients.index'));

        $this->assertSame(720, $client->fresh()->budget_per_month);
    }

    public function test_client_budget_validation_keeps_old_value_on_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('clients.create'))
            ->followingRedirects()
            ->post(route('clients.store'), [
                'name' => 'Acme Studio',
                'company' => 'Acme',
                'contact_person' => 'Alex Admin',
                'email' => 'alex@example.com',
                'budget_per_month' => 'abc',
                'status' => 'active',
                'notes' => 'Retainer client',
            ])
            ->assertSee('name="budget_per_month"', false)
            ->assertSee('value="abc"', false)
            ->assertSee('The budget per month field must be an integer.', false);
    }

    public function test_client_show_displays_monthly_total_and_excess_when_over_budget(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-18 09:00:00'));

        try {
            $admin = User::factory()->create(['role' => 'admin']);
            $client = Client::factory()->create([
                'name' => 'Control Networks',
                'budget_per_month' => 600,
            ]);
            $task = Task::factory()->create([
                'client_id' => $client->id,
                'assigned_user_id' => $admin->id,
                'title' => 'Client audit',
            ]);

            TimeEntry::factory()->create([
                'task_id' => $task->id,
                'user_id' => $admin->id,
                'date' => '2026-06-03',
                'hours' => 7.5,
            ]);

            TimeEntry::factory()->create([
                'task_id' => $task->id,
                'user_id' => $admin->id,
                'date' => '2026-06-15',
                'hours' => 5.25,
            ]);

            TimeEntry::factory()->create([
                'task_id' => $task->id,
                'user_id' => $admin->id,
                'date' => '2026-05-31',
                'hours' => 3,
            ]);

            $response = $this->actingAs($admin)->get(route('clients.show', $client));

            $response->assertOk()
                ->assertSee('Total Hours this month')
                ->assertSee(TimeDisplay::formatHours(12.75))
                ->assertSee('Excess hours of the budget per month')
                ->assertSee(TimeDisplay::formatMinutes(165));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_client_show_hides_excess_when_budget_is_missing_or_not_exceeded(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-18 09:00:00'));

        try {
            $admin = User::factory()->create(['role' => 'admin']);
            $client = Client::factory()->create([
                'name' => 'Northwind Logistics',
                'budget_per_month' => null,
            ]);
            $task = Task::factory()->create([
                'client_id' => $client->id,
                'assigned_user_id' => $admin->id,
                'title' => 'Portal cleanup',
            ]);

            TimeEntry::factory()->create([
                'task_id' => $task->id,
                'user_id' => $admin->id,
                'date' => '2026-06-10',
                'hours' => 4,
            ]);

            $response = $this->actingAs($admin)->get(route('clients.show', $client));

            $response->assertOk()
                ->assertSee('Total Hours this month')
                ->assertSee(TimeDisplay::formatHours(4))
                ->assertSee('No monthly budget set.')
                ->assertDontSee('Excess hours of the budget per month');
        } finally {
            Carbon::setTestNow();
        }
    }
}
