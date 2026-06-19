<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskProgressEntry;
use App\Models\TimeEntry;
use App\Models\User;
use App\Support\TimeDisplay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_dashboard_shows_monthly_total_hours_for_managers_and_members(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-18 09:00:00'));

        try {
            $manager = User::factory()->create(['role' => 'manager', 'name' => 'Scalyn Manager']);
            $member = User::factory()->create(['role' => 'member', 'name' => 'Scalyn Member']);
            $otherMember = User::factory()->create(['role' => 'member', 'name' => 'Scalyn Teammate']);
            $client = Client::factory()->create(['name' => 'Monthly Metrics Co']);

            $managerTask = Task::factory()->create([
                'client_id' => $client->id,
                'assigned_user_id' => $manager->id,
                'title' => 'Manager current month task',
            ]);
            $memberTask = Task::factory()->create([
                'client_id' => $client->id,
                'assigned_user_id' => $member->id,
                'title' => 'Member current month task',
            ]);
            $otherMemberTask = Task::factory()->create([
                'client_id' => $client->id,
                'assigned_user_id' => $otherMember->id,
                'title' => 'Teammate current month task',
            ]);

            TimeEntry::factory()->create([
                'task_id' => $managerTask->id,
                'user_id' => $manager->id,
                'date' => '2026-06-03',
                'hours' => 4,
            ]);
            TimeEntry::factory()->create([
                'task_id' => $memberTask->id,
                'user_id' => $member->id,
                'date' => '2026-06-10',
                'hours' => 3.5,
            ]);
            TimeEntry::factory()->create([
                'task_id' => $otherMemberTask->id,
                'user_id' => $otherMember->id,
                'date' => '2026-06-14',
                'hours' => 2.25,
            ]);
            TimeEntry::factory()->create([
                'task_id' => $managerTask->id,
                'user_id' => $manager->id,
                'date' => '2026-05-31',
                'hours' => 8,
            ]);
            TimeEntry::factory()->create([
                'task_id' => $memberTask->id,
                'user_id' => $member->id,
                'date' => '2026-07-01',
                'hours' => 6,
            ]);

            $this->actingAs($manager)->get(route('dashboard'))
                ->assertOk()
                ->assertSee('Monthly Time')
                ->assertSee('All logged work this month')
                ->assertDontSee('Total Hours')
                ->assertDontSee('All logged work</div>')
                ->assertSee(TimeDisplay::formatHours(9.75));

            $this->actingAs($member)->get(route('dashboard'))
                ->assertOk()
                ->assertSee('Monthly Time')
                ->assertSee('All logged work this month')
                ->assertDontSee('Total Hours')
                ->assertDontSee('All logged work</div>')
                ->assertSee(TimeDisplay::formatHours(3.5));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_admin_can_create_and_archive_client(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('clients.store'), [
            'name' => 'Acme Studio',
            'company' => 'Acme',
            'contact_person' => 'Alex Admin',
            'email' => 'alex@example.com',
            'status' => 'active',
            'notes' => 'Retainer client',
        ])->assertRedirect(route('clients.index'));

        $client = Client::where('name', 'Acme Studio')->firstOrFail();
        $this->actingAs($admin)->delete(route('clients.destroy', $client))->assertRedirect(route('clients.index'));

        $this->assertSame('archived', $client->fresh()->status);
        $this->assertNotNull($client->fresh()->archived_at);

        $this->actingAs($admin)->get(route('clients.index'))
            ->assertOk()
            ->assertDontSee('Acme Studio');

        $this->actingAs($admin)->get(route('clients.archives'))
            ->assertOk()
            ->assertSee('Acme Studio')
            ->assertSee(route('clients.force-delete', $client), false);
    }

    public function test_admin_can_permanently_delete_archived_client(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = Client::factory()->create(['name' => 'Bluebird Retail', 'status' => 'archived', 'archived_at' => now()]);

        $this->actingAs($admin)->delete(route('clients.force-delete', $client))
            ->assertRedirect(route('clients.archives'));

        $this->assertDatabaseMissing('clients', [
            'id' => $client->id,
        ]);
    }

    public function test_admin_can_restore_archived_client(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = Client::factory()->create([
            'name' => 'Bluebird Retail',
            'status' => 'archived',
            'archived_at' => now(),
        ]);

        $this->actingAs($admin)->patch(route('clients.restore', $client))
            ->assertRedirect(route('clients.index'));

        $client->refresh();
        $this->assertSame('active', $client->status);
        $this->assertNull($client->archived_at);

        $this->actingAs($admin)->get(route('clients.index'))
            ->assertOk()
            ->assertSee('Bluebird Retail');

        $this->actingAs($admin)->get(route('clients.archives'))
            ->assertOk()
            ->assertDontSee('Bluebird Retail');
    }

    public function test_member_cannot_permanently_delete_archived_client(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $client = Client::factory()->create(['name' => 'Bluebird Retail', 'status' => 'archived', 'archived_at' => now()]);

        $this->actingAs($member)->delete(route('clients.force-delete', $client))
            ->assertForbidden();
    }

    public function test_member_cannot_restore_archived_client(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $client = Client::factory()->create([
            'name' => 'Bluebird Retail',
            'status' => 'archived',
            'archived_at' => now(),
        ]);

        $this->actingAs($member)->get(route('clients.archives'))
            ->assertOk()
            ->assertDontSee('Restore Client')
            ->assertDontSee('data-delete-method="PATCH"', false);

        $this->actingAs($member)->patch(route('clients.restore', $client))
            ->assertForbidden();
    }

    public function test_index_pages_link_to_create_forms(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('clients.index'))
            ->assertOk()
            ->assertSee('Add Client')
            ->assertSee(route('clients.create'), false)
            ->assertSee(route('clients.archives'), false);

        $this->actingAs($admin)->get(route('clients.archives'))
            ->assertOk()
            ->assertSee('Add Client')
            ->assertSee(route('clients.index'), false);

        $this->actingAs($admin)->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Create Task')
            ->assertSee(route('tasks.create'), false)
            ->assertDontSee('name="modal_form" value="task-create"', false)
            ->assertSee('name="client_id"', false)
            ->assertSee('name="assigned_user_id"', false);

        $member = User::factory()->create(['role' => 'member']);
        $this->actingAs($member)->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('name="client_id"', false)
            ->assertDontSee('name="assigned_user_id"', false);

        $this->actingAs($admin)->get(route('time-entries.index'))
            ->assertOk()
            ->assertSee('Add Time')
            ->assertSee(route('time-entries.create'), false)
            ->assertDontSee('name="modal_form" value="time-entry-create"', false);
    }

    public function test_required_indicators_render_on_guest_auth_forms(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('required-indicator', false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('required-indicator', false);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('required-indicator', false);

        $this->get(route('password.reset', 'fake-token'))
            ->assertOk()
            ->assertSee('required-indicator', false);
    }

    public function test_required_indicators_render_on_authenticated_forms(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = Client::factory()->create();
        $otherUser = User::factory()->create(['role' => 'member']);
        $task = Task::factory()->create(['assigned_user_id' => $admin->id, 'client_id' => $client->id]);
        $timeEntry = TimeEntry::factory()->create([
            'task_id' => $task->id,
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)->get(route('clients.create'))
            ->assertOk()
            ->assertSee('required-indicator', false);

        $this->actingAs($admin)->get(route('clients.edit', $client))
            ->assertOk()
            ->assertSee('required-indicator', false);

        $this->actingAs($admin)->get(route('users.create'))
            ->assertOk()
            ->assertSee('required-indicator', false);

        $this->actingAs($admin)->get(route('users.edit', $otherUser))
            ->assertOk()
            ->assertSee('required-indicator', false);

        $this->actingAs($admin)->get(route('tasks.create'))
            ->assertOk()
            ->assertSee('required-indicator', false);

        $this->actingAs($admin)->get(route('tasks.edit', $task))
            ->assertOk()
            ->assertSee('required-indicator', false);

        $this->actingAs($admin)->get(route('time-entries.create'))
            ->assertOk()
            ->assertSee('required-indicator', false);

        $this->actingAs($admin)->get(route('time-entries.edit', $timeEntry))
            ->assertOk()
            ->assertSee('required-indicator', false)
            ->assertSee('max="480"', false);

        $this->actingAs($admin)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('required-indicator', false);
    }

    public function test_client_create_validation_returns_to_create_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('clients.create'))
            ->followingRedirects()
            ->post(route('clients.store'), [
                'company' => 'Acme',
                'contact_person' => 'Alex',
                'email' => 'alex@example.com',
                'status' => 'active',
                'notes' => 'Retainer client',
            ])
            ->assertSee('Add Client')
            ->assertSee('name="name"', false)
            ->assertSee('The name field is required.', false);
    }

    public function test_client_email_is_required_on_create_and_edit_forms(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = Client::factory()->create();

        $this->actingAs($admin)->get(route('clients.create'))
            ->assertOk()
            ->assertSee('Email', false)
            ->assertSee('required-indicator', false);

        $this->assertRequiredFieldMarkup(
            $this->actingAs($admin)->get(route('clients.create'))->getContent(),
            'email'
        );

        $this->actingAs($admin)->get(route('clients.edit', $client))
            ->assertOk()
            ->assertSee('Email', false)
            ->assertSee('required-indicator', false);

        $this->assertRequiredFieldMarkup(
            $this->actingAs($admin)->get(route('clients.edit', $client))->getContent(),
            'email'
        );
    }

    public function test_client_create_requires_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('clients.create'))
            ->post(route('clients.store'), [
                'name' => 'Acme Studio',
                'company' => 'Acme',
                'contact_person' => 'Alex Admin',
                'status' => 'active',
                'notes' => 'Retainer client',
            ])
            ->assertRedirect(route('clients.create'))
            ->assertSessionHasErrors('email');
    }

    public function test_client_update_requires_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = Client::factory()->create([
            'name' => 'Acme Studio',
            'email' => 'alex@example.com',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->from(route('clients.edit', $client))
            ->patch(route('clients.update', $client), [
                'name' => 'Acme Studio Updated',
                'company' => 'Acme',
                'contact_person' => 'Alex Admin',
                'status' => 'archived',
                'notes' => 'Updated client',
            ])
            ->assertRedirect(route('clients.edit', $client))
            ->assertSessionHasErrors('email');
    }

    public function test_admin_can_update_client_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = Client::factory()->create([
            'name' => 'Acme Studio',
            'email' => 'alex@example.com',
            'status' => 'active',
        ]);

        $this->actingAs($admin)->patch(route('clients.update', $client), [
            'name' => 'Acme Studio Updated',
            'company' => 'Acme',
            'contact_person' => 'Alex Admin',
            'email' => 'updated@example.com',
            'status' => 'archived',
            'notes' => 'Updated client',
        ])->assertRedirect(route('clients.index'));

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Acme Studio Updated',
            'email' => 'updated@example.com',
            'status' => 'archived',
        ]);
    }

    public function test_member_cannot_create_clients(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)->get(route('clients.create'))->assertForbidden();
    }

    public function test_status_badge_opens_modal_and_updates_task_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = Client::factory()->create(['name' => 'Northwind Logistics']);
        $task = Task::factory()->create([
            'client_id' => $client->id,
            'title' => 'Portal redesign discovery',
            'status' => 'open',
        ]);

        $this->actingAs($admin)->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('data-task-status-trigger', false)
            ->assertSee('Update status for Portal redesign discovery', false)
            ->assertSee('To Review');

        $this->actingAs($admin)->patch(route('tasks.status.update', $task), [
            'modal_form' => 'task-status-modal',
            'task_id' => $task->id,
            'status' => 'to_review',
        ])->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'to_review',
        ]);

        $this->assertDatabaseHas('task_activity_entries', [
            'task_id' => $task->id,
            'action' => 'updated',
            'field' => 'status',
            'old_value' => 'Open',
            'new_value' => 'To Review',
        ]);
    }

    public function test_status_modal_reopens_with_selected_task_on_validation_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = Client::factory()->create(['name' => 'Northwind Logistics']);
        $task = Task::factory()->create([
            'client_id' => $client->id,
            'title' => 'Portal redesign discovery',
            'status' => 'open',
        ]);

        $this->actingAs($admin)
            ->from(route('tasks.index'))
            ->followingRedirects()
            ->patch(route('tasks.status.update', $task), [
                'modal_form' => 'task-status-modal',
                'task_id' => $task->id,
            ])
            ->assertSee('data-auto-open="true"', false)
            ->assertSee('Portal redesign discovery')
            ->assertSee('Northwind Logistics');
    }

    public function test_priority_badge_opens_modal_and_updates_task_priority(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $client = Client::factory()->create(['name' => 'Northwind Logistics']);
        $task = Task::factory()->create([
            'client_id' => $client->id,
            'title' => 'Portal redesign discovery',
            'priority' => 'medium',
        ]);

        $returnTo = route('tasks.index', ['search' => 'Portal']);

        $this->actingAs($manager)->get($returnTo)
            ->assertOk()
            ->assertSee('data-task-priority-trigger', false)
            ->assertSee('Update priority for Portal redesign discovery', false)
            ->assertSee('Medium');

        $this->actingAs($manager)->patch(route('tasks.priority.update', $task), [
            'modal_form' => 'task-priority-modal',
            'task_id' => $task->id,
            'return_to' => $returnTo,
            'priority' => 'high',
        ])->assertRedirect($returnTo);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'priority' => 'high',
        ]);

        $this->assertDatabaseHas('task_activity_entries', [
            'task_id' => $task->id,
            'action' => 'updated',
            'field' => 'priority',
            'old_value' => 'Medium',
            'new_value' => 'High',
        ]);
    }

    public function test_priority_modal_reopens_with_selected_task_on_validation_error(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $client = Client::factory()->create(['name' => 'Northwind Logistics']);
        $task = Task::factory()->create([
            'client_id' => $client->id,
            'title' => 'Portal redesign discovery',
            'priority' => 'medium',
        ]);

        $this->actingAs($manager)
            ->from(route('tasks.show', $task))
            ->followingRedirects()
            ->patch(route('tasks.priority.update', $task), [
                'modal_form' => 'task-priority-modal',
                'task_id' => $task->id,
                'return_to' => route('tasks.show', $task),
            ])
            ->assertSee('data-auto-open="true"', false)
            ->assertSee('Portal redesign discovery')
            ->assertSee('Northwind Logistics')
            ->assertSee('The priority field is required.', false);
    }

    public function test_member_can_log_time_from_task_modal_and_update_status(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id]);

        $this->actingAs($member)->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Log Time')
            ->assertDontSee('name="progress_notes"', false);

        $this->actingAs($member)->get(route('tasks.edit', $task))
            ->assertOk()
            ->assertSee('Edit Task')
            ->assertSee('name="progress_notes"', false)
            ->assertDontSee('Log Time')
            ->assertDontSee('name="date"', false)
            ->assertDontSee('name="hours"', false)
            ->assertDontSee('name="notes"', false);

        $this->actingAs($member)->post(route('tasks.log-time'), [
            'task_id' => $task->id,
            'return_to' => route('tasks.index'),
            'status' => 'to_review',
            'date' => '2026-06-08',
            'minutes' => 150,
            'notes' => 'Implemented onboarding screen',
        ])->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('time_entries', [
            'user_id' => $member->id,
            'task_id' => $task->id,
            'hours' => 2.5,
        ]);

        $this->assertDatabaseHas('task_activity_entries', [
            'task_id' => $task->id,
            'user_id' => $member->id,
            'field' => 'status',
            'old_value' => 'Open',
            'new_value' => 'To Review',
        ]);

        $task->refresh();
        $this->assertSame('to_review', $task->status);

        $this->actingAs($member)->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('Implemented onboarding screen');
    }

    public function test_priority_submission_returns_to_the_originating_page(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $client = Client::factory()->create(['name' => 'Northwind Logistics']);
        $task = Task::factory()->create([
            'client_id' => $client->id,
            'title' => 'Portal redesign discovery',
            'priority' => 'medium',
        ]);

        $tableReturnTo = route('tasks.index', ['status' => 'open', 'search' => 'Portal']);
        $detailReturnTo = route('tasks.show', $task);

        $this->actingAs($manager)->patch(route('tasks.priority.update', $task), [
            'modal_form' => 'task-priority-modal',
            'task_id' => $task->id,
            'return_to' => $tableReturnTo,
            'priority' => 'low',
        ])->assertRedirect($tableReturnTo);

        $this->actingAs($manager)->patch(route('tasks.priority.update', $task), [
            'modal_form' => 'task-priority-modal',
            'task_id' => $task->id,
            'return_to' => $detailReturnTo,
            'priority' => 'high',
        ])->assertRedirect($detailReturnTo);
    }

    public function test_task_table_renders_bulk_status_controls_for_updatable_tasks(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        Task::factory()->create([
            'assigned_user_id' => $manager->id,
            'title' => 'Bulk status task',
        ]);

        $response = $this->actingAs($manager)->get(route('tasks.index'));

        $response
            ->assertOk()
            ->assertSee('data-task-bulk-status-form', false)
            ->assertSee('data-task-bulk-status-select-all', false)
            ->assertSee('data-task-bulk-status-checkbox', false)
            ->assertSee('Apply Status')
            ->assertSee('form-select-sm', false)
            ->assertSee('btn btn-primary btn-sm px-3', false);

        $this->assertMatchesRegularExpression('/<div class="border-top border-bottom bg-body-tertiary px-3 py-3 px-lg-4 py-lg-3" data-task-bulk-status-panel>/', $response->getContent());
    }

    public function test_bulk_status_submission_updates_selected_tasks_and_records_activity(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $client = Client::factory()->create(['name' => 'Northwind Logistics']);
        $taskA = Task::factory()->create([
            'client_id' => $client->id,
            'assigned_user_id' => $manager->id,
            'title' => 'Bulk task A',
            'status' => 'open',
        ]);
        $taskB = Task::factory()->create([
            'client_id' => $client->id,
            'assigned_user_id' => $manager->id,
            'title' => 'Bulk task B',
            'status' => 'in_progress',
        ]);

        $returnTo = route('tasks.index', ['search' => 'Bulk']);

        $this->actingAs($manager)->post(route('tasks.bulk-status.update'), [
            'task_ids' => [$taskA->id, $taskB->id],
            'status' => 'completed',
            'return_to' => $returnTo,
        ])->assertRedirect($returnTo);

        $this->assertDatabaseHas('tasks', [
            'id' => $taskA->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $taskB->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('task_activity_entries', [
            'task_id' => $taskA->id,
            'action' => 'updated',
            'field' => 'status',
            'old_value' => 'Open',
            'new_value' => 'Completed',
        ]);

        $this->assertDatabaseHas('task_activity_entries', [
            'task_id' => $taskB->id,
            'action' => 'updated',
            'field' => 'status',
            'old_value' => 'In Progress',
            'new_value' => 'Completed',
        ]);
    }

    public function test_bulk_status_requires_at_least_one_selected_task(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $task = Task::factory()->create(['assigned_user_id' => $manager->id]);

        $this->actingAs($manager)
            ->from(route('tasks.index'))
            ->post(route('tasks.bulk-status.update'), [
                'status' => 'completed',
                'return_to' => route('tasks.index'),
            ])
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHasErrors('task_ids');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => $task->status,
        ]);
    }

    public function test_bulk_status_rejects_uneditable_tasks(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $manager = User::factory()->create(['role' => 'manager']);
        $task = Task::factory()->create([
            'assigned_user_id' => $manager->id,
            'status' => 'open',
        ]);

        $this->actingAs($member)
            ->from(route('tasks.index'))
            ->post(route('tasks.bulk-status.update'), [
                'task_ids' => [$task->id],
                'status' => 'completed',
                'return_to' => route('tasks.index'),
            ])
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHasErrors('task_ids');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'open',
        ]);
    }

    public function test_member_can_log_time_from_task_detail_page_and_return_there(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id]);

        $this->actingAs($member)->post(route('tasks.log-time'), [
            'task_id' => $task->id,
            'return_to' => route('tasks.show', $task).'#logged-time-pane',
            'status' => 'in_progress',
            'date' => '2026-06-08',
            'minutes' => 90,
            'notes' => 'Logged time from the detail page.',
        ])->assertRedirect(route('tasks.show', $task).'#logged-time-pane');

        $this->assertDatabaseHas('time_entries', [
            'user_id' => $member->id,
            'task_id' => $task->id,
            'hours' => 1.5,
        ]);

        $this->actingAs($member)->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('Logged time from the detail page.');
    }

    public function test_task_show_renders_logged_time_edit_modal_trigger_and_modal(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id, 'title' => 'Invoice workflow cleanup']);
        $entry = TimeEntry::factory()->create([
            'task_id' => $task->id,
            'user_id' => $member->id,
            'hours' => 2.25,
            'notes' => 'Reviewed invoice routing and cleaned up the approval flow.',
        ]);

        $this->actingAs($member)->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('id="time-entry-edit-modal"', false)
            ->assertSee('data-time-entry-edit-trigger', false)
            ->assertSee('data-time-entry-edit-action="'.route('time-entries.update', $entry).'"', false)
            ->assertSee('data-time-entry-edit-return-to="'.route('tasks.show', $task).'#logged-time-pane"', false)
            ->assertSee('data-delete-return-to="'.route('tasks.show', $task).'#logged-time-pane"', false)
            ->assertSee('aria-label="Edit time entry for '.$entry->task->title.'"', false)
            ->assertDontSee(route('time-entries.edit', $entry), false);
    }

    public function test_task_show_updates_logged_time_and_returns_to_the_logged_time_pane(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id, 'title' => 'Invoice workflow cleanup']);
        $entry = TimeEntry::factory()->create([
            'task_id' => $task->id,
            'user_id' => $member->id,
            'hours' => 2.25,
            'notes' => 'Reviewed invoice routing and cleaned up the approval flow.',
        ]);

        $this->actingAs($member)
            ->patch(route('time-entries.update', $entry), [
                'task_id' => $task->id,
                'date' => '2026-06-08',
                'minutes' => 300,
                'notes' => 'Updated from the task detail page.',
                'return_to' => route('tasks.show', $task).'#logged-time-pane',
                'editing_entry' => $entry->id,
                'modal_form' => 'time-entry-edit-modal',
            ])
            ->assertRedirect(route('tasks.show', $task).'#logged-time-pane');

        $this->assertDatabaseHas('time_entries', [
            'id' => $entry->id,
            'hours' => 5,
            'date' => '2026-06-08 00:00:00',
        ]);
    }

    public function test_task_show_reopens_logged_time_edit_modal_with_validation_errors(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id, 'title' => 'Invoice workflow cleanup']);
        $entry = TimeEntry::factory()->create([
            'task_id' => $task->id,
            'user_id' => $member->id,
            'hours' => 2.25,
            'notes' => 'Reviewed invoice routing and cleaned up the approval flow.',
        ]);

        $response = $this->actingAs($member)
            ->from(route('tasks.show', $task))
            ->followingRedirects()
            ->patch(route('time-entries.update', $entry), [
                'task_id' => $task->id,
                'date' => '2026-06-08',
                'minutes' => 500,
                'notes' => 'Invalid edit.',
                'return_to' => route('tasks.show', $task).'#logged-time-pane',
                'editing_entry' => $entry->id,
                'modal_form' => 'time-entry-edit-modal',
            ]);

        $response
            ->assertOk()
            ->assertSee('id="time-entry-edit-modal"', false)
            ->assertSee('data-auto-open="true"', false)
            ->assertSee('name="editing_entry" value="'.$entry->id.'"', false)
            ->assertSee('value="500"', false)
            ->assertSee('Time will be logged to this task', false);
    }

    public function test_time_entry_create_accepts_valid_hours_within_range(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id]);

        $this->actingAs($member)->post(route('time-entries.store'), [
            'task_id' => $task->id,
            'date' => '2026-06-08',
            'minutes' => 240,
            'notes' => 'Within the allowed range.',
        ])->assertRedirect(route('time-entries.index'));

        $this->assertDatabaseHas('time_entries', [
            'user_id' => $member->id,
            'task_id' => $task->id,
            'hours' => 4,
        ]);
    }

    public function test_time_entry_update_accepts_valid_hours_within_range(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id]);
        $entry = TimeEntry::factory()->create([
            'user_id' => $member->id,
            'task_id' => $task->id,
            'hours' => 2,
        ]);

        $this->actingAs($member)->patch(route('time-entries.update', $entry), [
            'task_id' => $task->id,
            'date' => '2026-06-09',
            'minutes' => 240,
            'notes' => 'Updated within range.',
        ])->assertRedirect(route('time-entries.index'));

        $this->assertDatabaseHas('time_entries', [
            'id' => $entry->id,
            'user_id' => $member->id,
            'task_id' => $task->id,
            'hours' => 4,
        ]);
    }

    public function test_time_entry_create_rejects_negative_and_over_limit_hours(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id]);

        foreach ([-1, 481] as $minutes) {
            $this->actingAs($member)
                ->from(route('time-entries.create'))
                ->post(route('time-entries.store'), [
                    'task_id' => $task->id,
                    'date' => '2026-06-08',
                    'minutes' => $minutes,
                    'notes' => 'Invalid hours test.',
                ])
                ->assertRedirect(route('time-entries.create'))
                ->assertSessionHasErrors('minutes');
        }
    }

    public function test_time_entry_update_rejects_negative_and_over_limit_hours(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id]);
        $entry = TimeEntry::factory()->create([
            'user_id' => $member->id,
            'task_id' => $task->id,
            'hours' => 2,
        ]);

        foreach ([-1, 481] as $minutes) {
            $this->actingAs($member)
                ->from(route('time-entries.edit', $entry))
                ->patch(route('time-entries.update', $entry), [
                    'task_id' => $task->id,
                    'date' => '2026-06-09',
                    'minutes' => $minutes,
                    'notes' => 'Invalid hours test.',
                ])
                ->assertRedirect(route('time-entries.edit', $entry))
                ->assertSessionHasErrors('minutes');
        }
    }

    public function test_time_entry_index_opens_the_edit_modal_for_a_selected_row(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $client = Client::factory()->create(['name' => 'Acme']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id, 'client_id' => $client->id, 'title' => 'Daily QA']);
        $entry = TimeEntry::factory()->create([
            'task_id' => $task->id,
            'user_id' => $member->id,
            'date' => '2026-06-08',
            'hours' => 1.5,
        ]);

        $response = $this->actingAs($member)->get(route('time-entries.index', [
            'from' => '2026-06-08',
            'to' => '2026-06-08',
            'editing_entry' => $entry->id,
        ]));

        $response
            ->assertOk()
            ->assertSee('id="time-entry-edit-modal"', false)
            ->assertSee('data-auto-open="true"', false)
            ->assertSee('name="editing_entry" value="'.$entry->id.'"', false)
            ->assertSee('name="minutes" value="'.TimeDisplay::hoursToMinutes(1.5).'"', false)
            ->assertSee('name="return_to"', false)
            ->assertSee('Update Entry');
    }

    public function test_time_entry_index_modal_updates_entry_and_returns_to_filtered_view(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $client = Client::factory()->create(['name' => 'Acme']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id, 'client_id' => $client->id, 'title' => 'Daily QA']);
        $entry = TimeEntry::factory()->create([
            'task_id' => $task->id,
            'user_id' => $member->id,
            'date' => '2026-06-08',
            'hours' => 1.5,
        ]);

        $filters = [
            'from' => '2026-06-08',
            'to' => '2026-06-08',
            'sort' => 'date',
            'direction' => 'asc',
        ];
        $returnTo = route('time-entries.index', $filters);

        $this->actingAs($member)
            ->patch(route('time-entries.update', $entry), [
                'task_id' => $task->id,
                'date' => '2026-06-08',
                'minutes' => 180,
                'notes' => 'Updated from the modal.',
                'return_to' => $returnTo,
                'editing_entry' => $entry->id,
                'modal_form' => 'time-entry-edit-modal',
            ])
            ->assertRedirect($returnTo);

        $this->assertDatabaseHas('time_entries', [
            'id' => $entry->id,
            'task_id' => $task->id,
            'hours' => 3,
        ]);

        $this->actingAs($member)->get($returnTo)
            ->assertOk()
            ->assertSee(TimeDisplay::formatHours(3))
            ->assertSee('data-time-entry-edit-trigger', false);
    }

    public function test_time_entry_index_modal_validation_reopens_with_old_values_and_errors(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $client = Client::factory()->create(['name' => 'Acme']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id, 'client_id' => $client->id, 'title' => 'Daily QA']);
        $entry = TimeEntry::factory()->create([
            'task_id' => $task->id,
            'user_id' => $member->id,
            'date' => '2026-06-08',
            'hours' => 1.5,
        ]);

        $filters = [
            'from' => '2026-06-08',
            'to' => '2026-06-08',
        ];

        $response = $this->actingAs($member)
            ->from(route('time-entries.index', $filters))
            ->patch(route('time-entries.update', $entry), [
                'task_id' => $task->id,
                'date' => '2026-06-09',
                'minutes' => 500,
                'notes' => 'Invalid minutes test.',
                'return_to' => route('time-entries.index', $filters),
                'editing_entry' => $entry->id,
                'modal_form' => 'time-entry-edit-modal',
            ]);

        $response->assertRedirect(route('time-entries.index', $filters));
        $response->assertSessionHasErrors('minutes');

        $this->actingAs($member)->get(route('time-entries.index', $filters))
            ->assertOk()
            ->assertSee('data-auto-open="true"', false)
            ->assertSee('name="editing_entry" value="'.$entry->id.'"', false)
            ->assertSee('value="500"', false)
            ->assertSee('invalid-feedback', false);
    }

    public function test_time_entry_delete_is_available_on_task_and_list_views_and_removes_entry(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id, 'title' => 'Delete QA']);
        $entry = TimeEntry::factory()->create([
            'task_id' => $task->id,
            'user_id' => $member->id,
            'date' => '2026-06-08',
            'hours' => 2,
            'notes' => 'Delete me.',
        ]);

        $this->actingAs($member)->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('data-delete-confirm', false)
            ->assertSee(route('time-entries.destroy', $entry), false);

        $this->actingAs($member)->get(route('time-entries.index'))
            ->assertOk()
            ->assertSee('data-delete-confirm', false)
            ->assertSee(route('time-entries.destroy', $entry), false);

        $this->actingAs($member)->get(route('timesheets.index'))
            ->assertOk()
            ->assertSee('data-delete-confirm', false)
            ->assertSee(route('time-entries.destroy', $entry), false);

        $this->actingAs($member)->delete(route('time-entries.destroy', $entry))
            ->assertRedirect(route('time-entries.index'));

        $this->assertDatabaseMissing('time_entries', [
            'id' => $entry->id,
        ]);
    }

    public function test_time_entry_delete_on_task_show_returns_to_the_logged_time_pane(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id, 'title' => 'Delete QA']);
        $entry = TimeEntry::factory()->create([
            'task_id' => $task->id,
            'user_id' => $member->id,
            'date' => '2026-06-08',
            'hours' => 2,
            'notes' => 'Delete me.',
        ]);

        $this->actingAs($member)->delete(route('time-entries.destroy', $entry), [
            'return_to' => route('tasks.show', $task).'#logged-time-pane',
        ])->assertRedirect(route('tasks.show', $task).'#logged-time-pane');

        $this->assertDatabaseMissing('time_entries', [
            'id' => $entry->id,
        ]);
    }

    public function test_time_entry_delete_is_hidden_and_forbidden_for_other_users(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $otherUser = User::factory()->create(['role' => 'member']);
        $task = Task::factory()->create(['assigned_user_id' => $otherUser->id, 'title' => 'Other QA']);
        $entry = TimeEntry::factory()->create([
            'task_id' => $task->id,
            'user_id' => $otherUser->id,
            'date' => '2026-06-08',
            'hours' => 2,
            'notes' => 'Protected entry.',
        ]);

        $this->actingAs($member)->get(route('time-entries.index'))
            ->assertOk()
            ->assertDontSee('data-delete-confirm', false)
            ->assertDontSee(route('time-entries.destroy', $entry), false);

        $this->actingAs($member)->get(route('timesheets.index'))
            ->assertOk()
            ->assertDontSee('data-delete-confirm', false)
            ->assertDontSee(route('time-entries.destroy', $entry), false);

        $this->actingAs($member)->delete(route('time-entries.destroy', $entry))
            ->assertForbidden();
    }

    public function test_time_entry_index_hides_edit_affordance_for_unauthorized_entries(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $otherUser = User::factory()->create(['role' => 'member']);
        $client = Client::factory()->create(['name' => 'Acme']);
        $task = Task::factory()->create(['assigned_user_id' => $otherUser->id, 'client_id' => $client->id, 'title' => 'Other QA']);
        TimeEntry::factory()->create([
            'task_id' => $task->id,
            'user_id' => $otherUser->id,
            'date' => '2026-06-08',
            'hours' => 2,
        ]);

        $this->actingAs($member)->get(route('time-entries.index'))
            ->assertOk()
            ->assertDontSee('Other QA')
            ->assertDontSee('data-time-entry-edit-trigger', false);
    }

    public function test_task_show_renders_the_comments_tab_and_existing_comments(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id, 'title' => 'Portal redesign discovery']);

        TaskComment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $member->id,
            'body' => '<p><strong>Heads up</strong>, the mockups are ready.</p>',
        ]);

        $this->actingAs($member)->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('Comments')
            ->assertSee('Add a comment')
            ->assertSee('Heads up')
            ->assertSee('<strong>Heads up</strong>', false);
    }

    public function test_task_comment_author_can_create_edit_and_delete_own_comment(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id, 'title' => 'Portal redesign discovery']);
        $returnTo = route('tasks.show', $task).'#comments-pane';

        $this->actingAs($member)->post(route('tasks.comments.store', $task), [
            'body' => '<p><strong>Important</strong> follow-up from design.</p>',
            'return_to' => $returnTo,
        ])->assertRedirect($returnTo);

        $comment = TaskComment::where('task_id', $task->id)->firstOrFail();
        $this->assertSame('<p><strong>Important</strong> follow-up from design.</p>', $comment->body);

        $this->actingAs($member)->patch(route('tasks.comments.update', [$task, $comment]), [
            'comment_id' => $comment->id,
            'edit_body' => '<p>Updated <em>comment</em> copy.</p>',
            'return_to' => $returnTo,
        ])->assertRedirect($returnTo);

        $this->assertDatabaseHas('task_comments', [
            'id' => $comment->id,
            'body' => '<p>Updated <em>comment</em> copy.</p>',
        ]);

        $this->actingAs($member)->delete(route('tasks.comments.destroy', [$task, $comment]), [
            'return_to' => $returnTo,
        ])->assertRedirect($returnTo);

        $this->assertDatabaseMissing('task_comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_task_comment_validation_errors_return_to_the_comments_tab(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id]);
        $comment = TaskComment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $member->id,
            'body' => '<p>Original comment.</p>',
        ]);
        $returnTo = route('tasks.show', $task).'#comments-pane';

        $this->actingAs($member)->post(route('tasks.comments.store', $task), [
            'body' => '   ',
            'return_to' => $returnTo,
        ])->assertRedirect($returnTo)
            ->assertSessionHasErrors('body');

        $this->actingAs($member)->patch(route('tasks.comments.update', [$task, $comment]), [
            'comment_id' => $comment->id,
            'edit_body' => '   ',
            'return_to' => $returnTo,
        ])->assertRedirect($returnTo)
            ->assertSessionHasErrors('edit_body');
    }

    public function test_non_author_cannot_edit_or_delete_someone_elses_comment(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $manager = User::factory()->create(['role' => 'manager']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id]);
        $comment = TaskComment::factory()->create([
            'task_id' => $task->id,
            'user_id' => $member->id,
            'body' => '<p>Member-only comment.</p>',
        ]);
        $returnTo = route('tasks.show', $task).'#comments-pane';

        $this->actingAs($manager)->patch(route('tasks.comments.update', [$task, $comment]), [
            'comment_id' => $comment->id,
            'edit_body' => '<p>Trying to edit.</p>',
            'return_to' => $returnTo,
        ])->assertForbidden();

        $this->actingAs($manager)->delete(route('tasks.comments.destroy', [$task, $comment]), [
            'return_to' => $returnTo,
        ])->assertForbidden();
    }

    public function test_member_cannot_edit_unassigned_task(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $task = Task::factory()->create();

        $this->actingAs($member)->post(route('tasks.log-time'), [
            'task_id' => $task->id,
            'status' => 'completed',
            'date' => '2026-06-08',
            'minutes' => 60,
        ])->assertForbidden();
    }

    public function test_manager_can_log_time_for_an_assigned_task(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $assignee = User::factory()->create(['role' => 'member', 'name' => 'Scalyn Member Two']);
        $task = Task::factory()->create(['assigned_user_id' => $assignee->id]);

        $this->actingAs($manager)->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('name="user_id"', false);

        $this->actingAs($manager)->post(route('tasks.log-time'), [
            'task_id' => $task->id,
            'user_id' => $assignee->id,
            'return_to' => route('tasks.index'),
            'status' => 'in_progress',
            'date' => '2026-06-09',
            'minutes' => 180,
            'notes' => 'Reviewed QA findings and adjusted the task scope.',
        ])->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('time_entries', [
            'user_id' => $assignee->id,
            'task_id' => $task->id,
            'hours' => 3,
        ]);

        $task->refresh();
        $this->assertSame('in_progress', $task->status);
    }

    public function test_manager_can_update_task_details(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $client = Client::factory()->create(['name' => 'Northwind Logistics']);
        $assignee = User::factory()->create(['role' => 'member', 'name' => 'Scalyn Member Two']);
        $task = Task::factory()->create([
            'client_id' => Client::factory()->create(['name' => 'Acme Manufacturing'])->id,
            'assigned_user_id' => $manager->id,
            'title' => 'Old Title',
            'description' => 'Existing description',
            'priority' => 'low',
        ]);

        $response = $this->actingAs($manager)->get(route('tasks.edit', $task));

        $response->assertOk()
            ->assertSee('Edit Task')
            ->assertSee('data-required="true"', false);

        $this->assertRequiredFieldMarkup($response->getContent(), 'title');
        $this->assertRequiredFieldMarkup($response->getContent(), 'client_id');
        $this->assertRequiredFieldMarkup($response->getContent(), 'assigned_user_id');
        $this->assertRequiredFieldMarkup($response->getContent(), 'status');
        $this->assertRequiredFieldMarkup($response->getContent(), 'priority');

        $this->actingAs($manager)->put(route('tasks.update', $task), [
            'client_id' => $client->id,
            'assigned_user_id' => $assignee->id,
            'title' => 'New Title',
            'description' => 'Updated description',
            'status' => 'in_progress',
            'priority' => 'high',
        ])->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'client_id' => $client->id,
            'title' => 'New Title',
            'priority' => 'high',
            'status' => 'in_progress',
        ]);

        $this->assertDatabaseHas('task_activity_entries', [
            'task_id' => $task->id,
            'action' => 'updated',
            'field' => 'status',
            'old_value' => 'Open',
            'new_value' => 'In Progress',
        ]);

        $this->assertDatabaseHas('task_activity_entries', [
            'task_id' => $task->id,
            'action' => 'updated',
            'field' => 'client_id',
            'old_value' => 'Acme Manufacturing',
            'new_value' => 'Northwind Logistics',
        ]);

        $this->actingAs($manager)->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('Activity history')
            ->assertSee('Status changed from Open to In Progress')
            ->assertSee('Client changed from Acme Manufacturing to Northwind Logistics');
    }

    public function test_task_create_requires_every_field_except_attachments(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $client = Client::factory()->create();
        $assignee = User::factory()->create(['role' => 'member']);

        $basePayload = [
            'client_id' => $client->id,
            'assigned_user_id' => $assignee->id,
            'title' => 'Required fields task',
            'description' => '<p>Required description.</p>',
            'status' => 'open',
            'priority' => 'medium',
        ];

        foreach ([
            'client_id',
            'assigned_user_id',
            'title',
            'status',
            'priority',
        ] as $field) {
            $payload = $basePayload;
            unset($payload[$field]);

            $this->actingAs($manager)
                ->from(route('tasks.create'))
                ->post(route('tasks.store'), $payload)
                ->assertRedirect(route('tasks.create'))
                ->assertSessionHasErrors($field);
        }

        $payload = $basePayload;
        $payload['description'] = '<p><br></p>';

        $this->actingAs($manager)
            ->from(route('tasks.create'))
            ->post(route('tasks.store'), $payload)
            ->assertRedirect(route('tasks.create'))
            ->assertSessionHasErrors('description');
    }

    public function test_task_update_requires_every_field_except_attachments(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $client = Client::factory()->create();
        $assignee = User::factory()->create(['role' => 'member']);
        $task = Task::factory()->create([
            'client_id' => $client->id,
            'assigned_user_id' => $manager->id,
            'title' => 'Existing task',
            'description' => '<p>Existing description.</p>',
            'status' => 'open',
            'priority' => 'medium',
        ]);

        $basePayload = [
            'client_id' => $client->id,
            'assigned_user_id' => $assignee->id,
            'title' => 'Updated task',
            'description' => '<p>Updated description.</p>',
            'status' => 'in_progress',
            'priority' => 'high',
        ];

        foreach ([
            'client_id',
            'assigned_user_id',
            'title',
            'status',
            'priority',
        ] as $field) {
            $payload = $basePayload;
            unset($payload[$field]);

            $this->actingAs($manager)
                ->from(route('tasks.edit', $task))
                ->put(route('tasks.update', $task), $payload)
                ->assertRedirect(route('tasks.edit', $task))
                ->assertSessionHasErrors($field);
        }

        $payload = $basePayload;
        $payload['description'] = '<p><br></p>';

        $this->actingAs($manager)
            ->from(route('tasks.edit', $task))
            ->put(route('tasks.update', $task), $payload)
            ->assertRedirect(route('tasks.edit', $task))
            ->assertSessionHasErrors('description');
    }

    public function test_task_search_matches_client_and_assignee_names(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = Client::factory()->create(['name' => 'Northwind Logistics']);
        $assignee = User::factory()->create(['name' => 'Maya Santos', 'role' => 'member']);
        $matchingTask = Task::factory()->create([
            'client_id' => $client->id,
            'assigned_user_id' => $assignee->id,
            'title' => 'Portal redesign discovery',
        ]);
        Task::factory()->create([
            'title' => 'Unrelated task',
        ]);

        $this->actingAs($admin)->get(route('tasks.index', ['search' => 'Northwind']))
            ->assertOk()
            ->assertSee('Portal redesign discovery')
            ->assertDontSee('Unrelated task');

        $this->actingAs($admin)->get(route('tasks.index', ['search' => 'Maya Santos']))
            ->assertOk()
            ->assertSee('Portal redesign discovery');
    }

    public function test_task_search_matches_description_comments_and_progress_notes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = Client::factory()->create(['name' => 'Acme Corp']);
        $assignee = User::factory()->create(['name' => 'Jordan Lee', 'role' => 'member']);

        $descriptionTask = Task::factory()->create([
            'client_id' => $client->id,
            'assigned_user_id' => $assignee->id,
            'title' => 'Billing cleanup',
            'description' => 'Includes deployment blocker remediation notes.',
        ]);

        $commentTask = Task::factory()->create([
            'client_id' => $client->id,
            'assigned_user_id' => $assignee->id,
            'title' => 'Support follow-up',
            'description' => 'Generic task description.',
        ]);

        $progressTask = Task::factory()->create([
            'client_id' => $client->id,
            'assigned_user_id' => $assignee->id,
            'title' => 'Release prep',
            'description' => 'Generic task description.',
        ]);

        TaskComment::factory()->create([
            'task_id' => $commentTask->id,
            'user_id' => $admin->id,
            'body' => '<p>Needs QA sign-off before shipping.</p>',
        ]);

        TaskProgressEntry::create([
            'task_id' => $progressTask->id,
            'user_id' => $admin->id,
            'date' => '2026-06-18',
            'notes' => 'Sprint review complete and ready for merge.',
        ]);

        Task::factory()->create([
            'title' => 'Unrelated task',
        ]);

        $this->actingAs($admin)->get(route('tasks.index', ['search' => 'deployment blocker']))
            ->assertOk()
            ->assertSee('Billing cleanup')
            ->assertDontSee('Unrelated task');

        $this->actingAs($admin)->get(route('tasks.index', ['search' => 'QA sign-off']))
            ->assertOk()
            ->assertSee('Support follow-up')
            ->assertDontSee('Unrelated task');

        $this->actingAs($admin)->get(route('tasks.index', ['search' => 'Sprint review']))
            ->assertOk()
            ->assertSee('Release prep')
            ->assertDontSee('Unrelated task');
    }

    public function test_client_search_matches_contact_person_and_notes_on_active_and_archived_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $activeClient = Client::factory()->create([
            'status' => 'active',
            'name' => 'Alpha Active',
            'contact_person' => 'Maria Dela Cruz',
            'email' => 'alpha@example.com',
            'notes' => 'Primary onboarding contact for active work.',
        ]);

        $archivedClient = Client::factory()->create([
            'status' => 'archived',
            'name' => 'Old Archive Co',
            'contact_person' => 'Ben Reyes',
            'email' => 'archive@example.com',
            'notes' => 'Archived after project closeout review.',
            'archived_at' => now(),
        ]);

        Client::factory()->create([
            'status' => 'active',
            'name' => 'Other Active',
            'contact_person' => 'No Match',
            'email' => 'other@example.com',
            'notes' => 'Different record.',
        ]);

        $this->actingAs($admin)->get(route('clients.index', ['search' => 'Maria Dela Cruz']))
            ->assertOk()
            ->assertSee('Alpha Active')
            ->assertDontSee('Other Active');

        $this->actingAs($admin)->get(route('clients.archives', ['search' => 'closeout review']))
            ->assertOk()
            ->assertSee('Old Archive Co')
            ->assertDontSee('Alpha Active');

        $this->assertTrue($activeClient->exists);
        $this->assertTrue($archivedClient->exists);
    }

    public function test_task_filters_by_client_and_assigned_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $clientA = Client::factory()->create(['name' => 'Northwind Logistics']);
        $clientB = Client::factory()->create(['name' => 'Bluebird Retail']);
        $assigneeA = User::factory()->create(['role' => 'member', 'name' => 'Maya Santos']);
        $assigneeB = User::factory()->create(['role' => 'member', 'name' => 'Alex Gomez']);

        Task::factory()->create([
            'client_id' => $clientA->id,
            'assigned_user_id' => $assigneeA->id,
            'title' => 'Northwind Task',
        ]);

        Task::factory()->create([
            'client_id' => $clientB->id,
            'assigned_user_id' => $assigneeB->id,
            'title' => 'Bluebird Task',
        ]);

        $this->actingAs($admin)->get(route('tasks.index', [
            'client_id' => $clientA->id,
        ]))
            ->assertOk()
            ->assertSee('Northwind Task')
            ->assertDontSee('Bluebird Task');

        $this->actingAs($admin)->get(route('tasks.index', [
            'assigned_user_id' => $assigneeA->id,
        ]))
            ->assertOk()
            ->assertSee('Northwind Task')
            ->assertDontSee('Bluebird Task');

        $this->actingAs($admin)->get(route('tasks.index', [
            'client_id' => $clientA->id,
            'assigned_user_id' => $assigneeA->id,
        ]))
            ->assertOk()
            ->assertSee('Northwind Task')
            ->assertDontSee('Bluebird Task');
    }

    public function test_task_filters_preserve_query_strings_in_sort_links(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = Client::factory()->create(['name' => 'Northwind Logistics']);
        $assignee = User::factory()->create(['role' => 'member', 'name' => 'Maya Santos']);

        Task::factory()->create([
            'client_id' => $client->id,
            'assigned_user_id' => $assignee->id,
            'title' => 'Portal redesign discovery',
        ]);

        $this->actingAs($admin)->get(route('tasks.index', [
            'search' => 'Portal',
            'status' => 'open',
            'client_id' => $client->id,
            'assigned_user_id' => $assignee->id,
        ]))
            ->assertOk()
            ->assertSee('search=Portal', false)
            ->assertSee('status=open', false)
            ->assertSee('client_id='.$client->id, false)
            ->assertSee('assigned_user_id='.$assignee->id, false);
    }

    public function test_task_create_still_saves_when_activity_table_is_missing(): void
    {
        Schema::dropIfExists('task_activity_entries');

        $admin = User::factory()->create(['role' => 'admin']);
        $client = Client::factory()->create();
        $assignee = User::factory()->create(['role' => 'member', 'name' => 'Scalyn Assignee']);

        $response = $this->actingAs($admin)->get(route('tasks.create'));

        $response->assertOk()
            ->assertSee('Create Task')
            ->assertSee('data-required="true"', false)
            ->assertSee('To Review');

        $this->assertRequiredFieldMarkup($response->getContent(), 'title');
        $this->assertRequiredFieldMarkup($response->getContent(), 'client_id');
        $this->assertRequiredFieldMarkup($response->getContent(), 'assigned_user_id');
        $this->assertRequiredFieldMarkup($response->getContent(), 'status');
        $this->assertRequiredFieldMarkup($response->getContent(), 'priority');

        $this->actingAs($admin)
            ->post(route('tasks.store'), [
                'client_id' => $client->id,
                'assigned_user_id' => $assignee->id,
                'title' => 'Fallback Task',
                'description' => 'Should save even without the audit table.',
                'status' => 'to_review',
                'priority' => 'medium',
            ])
            ->assertRedirect(route('tasks.index'));

        $task = Task::where('title', 'Fallback Task')->first();
        $this->assertNotNull($task);
        $this->assertDatabaseHas('tasks', [
            'title' => 'Fallback Task',
            'status' => 'to_review',
            'priority' => 'medium',
        ]);

        $this->actingAs($admin)->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Fallback Task');
    }

    public function test_reports_index_renders_card_export_links(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)->get(route('reports.index', [
            'from' => '2026-06-01',
            'to' => '2026-06-30',
            'client_id' => '',
            'user_id' => '',
        ]))
            ->assertOk()
            ->assertSee('report=clientHours', false)
            ->assertSee('report=taskHours', false)
            ->assertSee('report=userHours', false)
            ->assertSee('from=2026-06-01', false)
            ->assertSee('to=2026-06-30', false);
    }

    public function test_reports_defaults_to_monthly_view_with_current_month_dates(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-18 09:00:00'));

        try {
            $manager = User::factory()->create(['role' => 'manager']);

            $this->actingAs($manager)->get(route('reports.index'))
                ->assertOk()
                ->assertSee('<option value="monthly" selected>Monthly</option>', false)
                ->assertSee('name="from" value="2026-06-01"', false)
                ->assertSee('name="to" value="2026-06-30"', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_reports_client_hours_csv_export_is_filtered_to_that_card(): void
    {
        $fixtures = $this->createReportExportFixtures();

        $this->actingAs($fixtures['manager'])->get(route('reports.export', [
            'report' => 'clientHours',
            'from' => '2026-06-01',
            'to' => '2026-06-30',
            'client_id' => $fixtures['northwindClient']->id,
            'user_id' => $fixtures['manager']->id,
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader('content-disposition', 'attachment; filename="scalyn-hours-per-client.csv"')
            ->assertSee('Excess Time')
            ->assertSee('Northwind Logistics')
            ->assertSee(TimeDisplay::formatHours(3.25))
            ->assertSee(TimeDisplay::formatMinutes(75))
            ->assertDontSee('Bluebird Retail')
            ->assertDontSee('Invoice Cleanup')
            ->assertDontSee('Scalyn Member');
    }

    public function test_reports_client_hours_csv_export_shows_zero_excess_for_under_budget_clients(): void
    {
        $fixtures = $this->createReportExportFixtures();

        $this->actingAs($fixtures['manager'])->get(route('reports.export', [
            'report' => 'clientHours',
            'from' => '2026-06-01',
            'to' => '2026-06-30',
            'client_id' => $fixtures['bluebirdClient']->id,
            'user_id' => $fixtures['member']->id,
        ]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename="scalyn-hours-per-client.csv"')
            ->assertSee('Bluebird Retail')
            ->assertSee(TimeDisplay::formatHours(5))
            ->assertSee(TimeDisplay::formatMinutes(0));
    }

    public function test_reports_task_hours_csv_export_is_filtered_to_that_card(): void
    {
        $fixtures = $this->createReportExportFixtures();

        $this->actingAs($fixtures['manager'])->get(route('reports.export', [
            'report' => 'taskHours',
            'from' => '2026-06-01',
            'to' => '2026-06-30',
            'user_id' => $fixtures['member']->id,
        ]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename="scalyn-hours-per-task.csv"')
            ->assertSee('Task,Time')
            ->assertSee('Website Build')
            ->assertSee('Invoice Cleanup')
            ->assertSee(TimeDisplay::formatHours(1.75))
            ->assertSee(TimeDisplay::formatHours(5))
            ->assertDontSee(TimeDisplay::formatHours(3.25))
            ->assertDontSee('Northwind Logistics')
            ->assertDontSee('Scalyn Manager');
    }

    public function test_reports_employee_hours_csv_export_is_filtered_to_that_card(): void
    {
        $fixtures = $this->createReportExportFixtures();

        $this->actingAs($fixtures['manager'])->get(route('reports.export', [
            'report' => 'userHours',
            'from' => '2026-06-01',
            'to' => '2026-06-30',
            'client_id' => $fixtures['northwindClient']->id,
        ]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename="scalyn-hours-per-employee.csv"')
            ->assertSee('Name,Time')
            ->assertSee('Scalyn Manager')
            ->assertSee('Scalyn Member')
            ->assertSee(TimeDisplay::formatHours(3.25))
            ->assertSee(TimeDisplay::formatHours(1.75))
            ->assertDontSee('Bluebird Retail')
            ->assertDontSee(TimeDisplay::formatHours(5));
    }

    public function test_reports_csv_export_returns_only_headers_when_no_rows_match(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)->get(route('reports.export', [
            'report' => 'clientHours',
            'from' => '2025-01-01',
            'to' => '2025-01-31',
        ]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertSee('Name,Time')
            ->assertDontSee('Northwind Logistics')
            ->assertDontSee('Website Build');
    }

    public function test_timesheet_renders_export_link_with_current_filters(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $client = Client::factory()->create(['name' => 'Acme']);
        $task = Task::factory()->create(['assigned_user_id' => $manager->id, 'client_id' => $client->id, 'title' => 'Portal Review']);
        TimeEntry::factory()->create(['task_id' => $task->id, 'user_id' => $manager->id, 'date' => '2026-06-08', 'hours' => 2]);

        $this->actingAs($manager)->get(route('timesheets.index', [
            'view' => 'weekly',
            'from' => '2026-06-08',
            'to' => '2026-06-14',
            'client_id' => $client->id,
            'user_id' => $manager->id,
            'sort' => 'hours',
            'direction' => 'desc',
        ]))
            ->assertOk()
            ->assertSee(route('timesheets.export', [
                'view' => 'weekly',
                'from' => '2026-06-08',
                'to' => '2026-06-14',
                'client_id' => $client->id,
                'user_id' => $manager->id,
                'sort' => 'hours',
                'direction' => 'desc',
            ]));
    }

    public function test_timesheet_csv_export_respects_filters_and_headers(): void
    {
        $fixtures = $this->createTimesheetExportFixtures();

        $this->actingAs($fixtures['manager'])->get(route('timesheets.export', [
            'view' => 'weekly',
            'from' => '2026-06-08',
            'to' => '2026-06-14',
            'client_id' => $fixtures['northwindClient']->id,
            'user_id' => $fixtures['member']->id,
            'sort' => 'date',
            'direction' => 'asc',
        ]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader('content-disposition', 'attachment; filename="scalyn-timesheets.csv"')
            ->assertSee('Date,User,Client,Task,Notes,Time')
            ->assertSee(TimeDisplay::formatHours(1.75))
            ->assertDontSee('Scalyn Manager')
            ->assertDontSee('Bluebird Retail')
            ->assertDontSee('Out of range note');
    }

    public function test_timesheet_csv_export_keeps_member_scope_even_with_other_user_filter(): void
    {
        $fixtures = $this->createTimesheetExportFixtures();

        $this->actingAs($fixtures['member'])->get(route('timesheets.export', [
            'view' => 'weekly',
            'from' => '2026-06-08',
            'to' => '2026-06-14',
            'client_id' => $fixtures['northwindClient']->id,
            'user_id' => $fixtures['manager']->id,
        ]))
            ->assertOk()
            ->assertSee('Date,User,Client,Task,Notes,Time')
            ->assertSee(TimeDisplay::formatHours(1.75))
            ->assertDontSee('Scalyn Manager')
            ->assertDontSee('Manager note');
    }

    public function test_timesheet_shows_total_hours(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id, 'title' => 'Daily QA']);
        $entry = TimeEntry::factory()->create(['task_id' => $task->id, 'user_id' => $member->id, 'date' => '2026-06-08', 'hours' => 4]);

        $this->actingAs($member)->get(route('timesheets.index', [
            'from' => '2026-06-08',
            'to' => '2026-06-08',
            'view' => 'daily',
        ]))
            ->assertOk()
            ->assertSee($entry->task->title)
            ->assertSee(TimeDisplay::formatHours(4));
    }

    public function test_timesheet_defaults_to_monthly_view_with_current_month_dates(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-18 09:00:00'));

        try {
            $member = User::factory()->create(['role' => 'member']);

            $this->actingAs($member)->get(route('timesheets.index'))
                ->assertOk()
                ->assertSee('<option value="monthly" selected>Monthly</option>', false)
                ->assertSee('name="from" value="2026-06-01"', false)
                ->assertSee('name="to" value="2026-06-30"', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_timesheet_opens_the_time_entry_edit_modal_for_a_selected_row(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $client = Client::factory()->create(['name' => 'Acme']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id, 'client_id' => $client->id, 'title' => 'Daily QA']);
        $entry = TimeEntry::factory()->create(['task_id' => $task->id, 'user_id' => $member->id, 'date' => '2026-06-08', 'hours' => 4]);

        $response = $this->actingAs($member)->get(route('timesheets.index', [
            'from' => '2026-06-08',
            'to' => '2026-06-08',
            'view' => 'daily',
            'editing_entry' => $entry->id,
        ]));

        $response
            ->assertOk()
            ->assertSee('id="time-entry-edit-modal"', false)
            ->assertSee('data-auto-open="true"', false)
            ->assertSee('name="editing_entry" value="'.$entry->id.'"', false)
            ->assertSee('name="minutes" value="'.TimeDisplay::hoursToMinutes(4).'"', false)
            ->assertSee('name="return_to"', false)
            ->assertSee('Update Entry');
    }

    public function test_timesheet_modal_updates_entry_and_returns_to_filtered_view(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $client = Client::factory()->create(['name' => 'Acme']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id, 'client_id' => $client->id, 'title' => 'Daily QA']);
        $entry = TimeEntry::factory()->create(['task_id' => $task->id, 'user_id' => $member->id, 'date' => '2026-06-08', 'hours' => 4]);

        $filters = [
            'from' => '2026-06-08',
            'to' => '2026-06-08',
            'view' => 'daily',
            'sort' => 'hours',
            'direction' => 'desc',
        ];
        $returnTo = route('timesheets.index', $filters);

        $this->actingAs($member)
            ->patch(route('time-entries.update', $entry), [
                'user_id' => $member->id,
                'task_id' => $task->id,
                'date' => '2026-06-08',
                'minutes' => 360,
                'notes' => 'Updated from timesheets modal.',
                'return_to' => $returnTo,
                'editing_entry' => $entry->id,
                'modal_form' => 'time-entry-edit-modal',
            ])
            ->assertRedirect($returnTo);

        $this->assertDatabaseHas('time_entries', [
            'id' => $entry->id,
            'hours' => 6,
            'date' => '2026-06-08 00:00:00',
        ]);

        $this->actingAs($member)->get($returnTo)
            ->assertOk()
            ->assertSee(TimeDisplay::formatHours(6))
            ->assertSee('data-time-entry-edit-trigger', false);
    }

    public function test_timesheet_modal_validation_reopens_with_old_values_and_errors(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $client = Client::factory()->create(['name' => 'Acme']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id, 'client_id' => $client->id, 'title' => 'Daily QA']);
        $entry = TimeEntry::factory()->create(['task_id' => $task->id, 'user_id' => $member->id, 'date' => '2026-06-08', 'hours' => 4]);

        $filters = [
            'from' => '2026-06-08',
            'to' => '2026-06-08',
            'view' => 'daily',
        ];

        $response = $this->actingAs($member)
            ->from(route('timesheets.index', $filters))
            ->patch(route('time-entries.update', $entry), [
                'user_id' => $member->id,
                'task_id' => $task->id,
                'date' => '2026-06-09',
                'minutes' => 500,
                'notes' => 'Invalid minutes test.',
                'return_to' => route('timesheets.index', $filters),
                'editing_entry' => $entry->id,
                'modal_form' => 'time-entry-edit-modal',
            ]);

        $response->assertRedirect(route('timesheets.index', $filters));
        $response->assertSessionHasErrors('minutes');

        $this->actingAs($member)->get(route('timesheets.index', $filters))
            ->assertOk()
            ->assertSee('data-auto-open="true"', false)
            ->assertSee('name="editing_entry" value="'.$entry->id.'"', false)
            ->assertSee('value="500"', false)
            ->assertSee('invalid-feedback', false);
    }

    public function test_timesheet_renders_client_filter_and_sort_headers(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $client = Client::factory()->create(['name' => 'Acme']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id, 'client_id' => $client->id, 'title' => 'Portal Cleanup']);
        TimeEntry::factory()->create(['task_id' => $task->id, 'user_id' => $member->id, 'date' => '2026-06-08', 'hours' => 2]);

        $this->actingAs($member)->get(route('timesheets.index', [
            'from' => '2026-06-08',
            'to' => '2026-06-08',
            'view' => 'daily',
        ]))
            ->assertOk()
            ->assertSee('data-timesheet-filter-form', false)
            ->assertSee('data-timesheet-view-select', false)
            ->assertSee('data-timesheet-from-input', false)
            ->assertSee('data-timesheet-to-input', false)
            ->assertSee('name="client_id"', false)
            ->assertSee('All clients')
            ->assertSee('sort=date', false)
            ->assertSee('sort=hours', false);
    }

    public function test_timesheet_defaults_to_weekly_view_with_current_week_dates(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-18 09:00:00'));

        try {
            $member = User::factory()->create(['role' => 'member']);

            $this->actingAs($member)->get(route('timesheets.index', ['view' => 'weekly']))
                ->assertOk()
                ->assertSee('<option value="weekly" selected>Weekly</option>', false)
                ->assertSee('name="from" value="2026-06-15"', false)
                ->assertSee('name="to" value="2026-06-21"', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_timesheet_defaults_to_daily_view_with_current_day_dates(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-18 09:00:00'));

        try {
            $member = User::factory()->create(['role' => 'member']);

            $this->actingAs($member)->get(route('timesheets.index', ['view' => 'daily']))
                ->assertOk()
                ->assertSee('<option value="daily" selected>Daily</option>', false)
                ->assertSee('name="from" value="2026-06-18"', false)
                ->assertSee('name="to" value="2026-06-18"', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_timesheet_filter_renders_reset_button_linking_to_base_route(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $client = Client::factory()->create(['name' => 'Acme']);
        $task = Task::factory()->create(['assigned_user_id' => $manager->id, 'client_id' => $client->id, 'title' => 'Portal Review']);
        TimeEntry::factory()->create(['task_id' => $task->id, 'user_id' => $manager->id, 'date' => '2026-06-08', 'hours' => 2]);

        $response = $this->actingAs($manager)->get(route('timesheets.index', [
            'view' => 'weekly',
            'from' => '2026-06-08',
            'to' => '2026-06-14',
            'client_id' => $client->id,
            'user_id' => $manager->id,
            'sort' => 'hours',
            'direction' => 'desc',
        ]));

        $response->assertOk();
        $response->assertSee('aria-label="Reset filters"', false);
        $response->assertSee('href="'.route('timesheets.index').'"', false);
        $response->assertDontSee('href="'.route('timesheets.index', ['view' => 'weekly']).'"', false);
    }

    public function test_timesheet_filters_entries_by_client(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $clientA = Client::factory()->create(['name' => 'Northwind']);
        $clientB = Client::factory()->create(['name' => 'Bluebird']);
        $taskA = Task::factory()->create(['assigned_user_id' => $member->id, 'client_id' => $clientA->id, 'title' => 'Northwind Audit']);
        $taskB = Task::factory()->create(['assigned_user_id' => $member->id, 'client_id' => $clientB->id, 'title' => 'Bluebird Review']);

        TimeEntry::factory()->create(['task_id' => $taskA->id, 'user_id' => $member->id, 'date' => '2026-06-08', 'hours' => 3]);
        TimeEntry::factory()->create(['task_id' => $taskB->id, 'user_id' => $member->id, 'date' => '2026-06-08', 'hours' => 5]);

        $this->actingAs($member)->get(route('timesheets.index', [
            'from' => '2026-06-08',
            'to' => '2026-06-08',
            'view' => 'daily',
            'client_id' => $clientA->id,
        ]))
            ->assertOk()
            ->assertSee('Northwind Audit')
            ->assertSee('1 entries');
    }

    public function test_timesheet_sorts_entries_by_hours(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $client = Client::factory()->create(['name' => 'Acme']);
        $slowTask = Task::factory()->create(['assigned_user_id' => $member->id, 'client_id' => $client->id, 'title' => 'Small Task']);
        $fastTask = Task::factory()->create(['assigned_user_id' => $member->id, 'client_id' => $client->id, 'title' => 'Large Task']);

        TimeEntry::factory()->create(['task_id' => $slowTask->id, 'user_id' => $member->id, 'date' => '2026-06-08', 'hours' => 2]);
        TimeEntry::factory()->create(['task_id' => $fastTask->id, 'user_id' => $member->id, 'date' => '2026-06-08', 'hours' => 8]);

        $this->actingAs($member)->get(route('timesheets.index', [
            'from' => '2026-06-08',
            'to' => '2026-06-08',
            'view' => 'daily',
            'sort' => 'hours',
            'direction' => 'desc',
        ]))
            ->assertOk()
            ->assertSeeInOrder(['Large Task', 'Small Task']);
    }

    public function test_timesheet_ignores_invalid_sort_values(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $client = Client::factory()->create(['name' => 'Acme']);
        $olderTask = Task::factory()->create(['assigned_user_id' => $member->id, 'client_id' => $client->id, 'title' => 'Older Task']);
        $newerTask = Task::factory()->create(['assigned_user_id' => $member->id, 'client_id' => $client->id, 'title' => 'Newer Task']);

        TimeEntry::factory()->create(['task_id' => $newerTask->id, 'user_id' => $member->id, 'date' => '2026-06-09', 'hours' => 1]);
        TimeEntry::factory()->create(['task_id' => $olderTask->id, 'user_id' => $member->id, 'date' => '2026-06-08', 'hours' => 1]);

        $this->actingAs($member)->get(route('timesheets.index', [
            'from' => '2026-06-08',
            'to' => '2026-06-09',
            'view' => 'weekly',
            'sort' => 'made-up-column',
            'direction' => 'desc',
        ]))
            ->assertOk()
            ->assertSeeInOrder(['Older Task', 'Newer Task']);
    }

    private function createTimesheetExportFixtures(): array
    {
        $manager = User::factory()->create(['role' => 'manager', 'name' => 'Scalyn Manager']);
        $member = User::factory()->create(['role' => 'member', 'name' => 'Scalyn Member']);
        $northwindClient = Client::factory()->create(['name' => 'Northwind Logistics']);
        $bluebirdClient = Client::factory()->create(['name' => 'Bluebird Retail']);
        $northwindTask = Task::factory()->create(['client_id' => $northwindClient->id, 'title' => 'Website Build']);
        $bluebirdTask = Task::factory()->create(['client_id' => $bluebirdClient->id, 'title' => 'Invoice Cleanup']);

        TimeEntry::factory()->create([
            'task_id' => $northwindTask->id,
            'user_id' => $manager->id,
            'date' => '2026-06-08',
            'hours' => 3.25,
            'notes' => 'Manager note',
        ]);

        TimeEntry::factory()->create([
            'task_id' => $northwindTask->id,
            'user_id' => $member->id,
            'date' => '2026-06-09',
            'hours' => 1.75,
            'notes' => 'Member note',
        ]);

        TimeEntry::factory()->create([
            'task_id' => $bluebirdTask->id,
            'user_id' => $member->id,
            'date' => '2026-06-10',
            'hours' => 5,
            'notes' => 'Bluebird note',
        ]);

        TimeEntry::factory()->create([
            'task_id' => $northwindTask->id,
            'user_id' => $manager->id,
            'date' => '2026-05-31',
            'hours' => 2,
            'notes' => 'Out of range note',
        ]);

        return compact('manager', 'member', 'northwindClient', 'bluebirdClient', 'northwindTask', 'bluebirdTask');
    }

    private function createReportExportFixtures(): array
    {
        $manager = User::factory()->create(['role' => 'manager', 'name' => 'Scalyn Manager']);
        $member = User::factory()->create(['role' => 'member', 'name' => 'Scalyn Member']);
        $northwindClient = Client::factory()->create(['name' => 'Northwind Logistics', 'budget_per_month' => 120]);
        $bluebirdClient = Client::factory()->create(['name' => 'Bluebird Retail', 'budget_per_month' => 600]);
        $northwindTask = Task::factory()->create(['client_id' => $northwindClient->id, 'title' => 'Website Build']);
        $bluebirdTask = Task::factory()->create(['client_id' => $bluebirdClient->id, 'title' => 'Invoice Cleanup']);

        TimeEntry::factory()->create([
            'task_id' => $northwindTask->id,
            'user_id' => $manager->id,
            'date' => '2026-06-08',
            'hours' => 3.25,
        ]);

        TimeEntry::factory()->create([
            'task_id' => $northwindTask->id,
            'user_id' => $member->id,
            'date' => '2026-06-09',
            'hours' => 1.75,
        ]);

        TimeEntry::factory()->create([
            'task_id' => $bluebirdTask->id,
            'user_id' => $member->id,
            'date' => '2026-06-08',
            'hours' => 5,
        ]);

        TimeEntry::factory()->create([
            'task_id' => $northwindTask->id,
            'user_id' => $manager->id,
            'date' => '2026-05-31',
            'hours' => 10,
        ]);

        return compact('manager', 'member', 'northwindClient', 'bluebirdClient', 'northwindTask', 'bluebirdTask');
    }

    private function assertRequiredFieldMarkup(string $html, string $fieldName): void
    {
        $this->assertMatchesRegularExpression(
            '/name="'.preg_quote($fieldName, '/').'"[\s\S]*?required/',
            $html
        );
    }
}
