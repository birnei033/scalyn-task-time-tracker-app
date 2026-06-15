<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
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
            'hours' => 2.5,
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

    public function test_member_can_log_time_from_task_detail_page_and_return_there(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $task = Task::factory()->create(['assigned_user_id' => $member->id]);

        $this->actingAs($member)->post(route('tasks.log-time'), [
            'task_id' => $task->id,
            'return_to' => route('tasks.show', $task).'#logged-time-pane',
            'status' => 'in_progress',
            'date' => '2026-06-08',
            'hours' => 1.5,
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
            'hours' => 1,
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
            'hours' => 3,
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

        $this->actingAs($manager)->get(route('tasks.edit', $task))
            ->assertOk()
            ->assertSee('Edit Task')
            ->assertSee('name="title"', false)
            ->assertSee('name="client_id"', false)
            ->assertSee('name="priority"', false);

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

        $this->actingAs($admin)->get(route('tasks.create'))
            ->assertOk()
            ->assertSee('Create Task')
            ->assertSee('name="title"', false)
            ->assertSee('name="client_id"', false)
            ->assertSee('name="priority"', false)
            ->assertSee('To Review');

        $this->actingAs($admin)
            ->post(route('tasks.store'), [
                'client_id' => $client->id,
                'assigned_user_id' => null,
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
            ->assertSee('Name,Hours')
            ->assertSee('Northwind Logistics')
            ->assertSee('3.25')
            ->assertDontSee('Bluebird Retail')
            ->assertDontSee('Invoice Cleanup')
            ->assertDontSee('Scalyn Member');
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
            ->assertSee('Task,Hours')
            ->assertSee('Website Build')
            ->assertSee('Invoice Cleanup')
            ->assertSee('1.75')
            ->assertSee('5.00')
            ->assertDontSee('3.25')
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
            ->assertSee('Name,Hours')
            ->assertSee('Scalyn Manager')
            ->assertSee('Scalyn Member')
            ->assertSee('3.25')
            ->assertSee('1.75')
            ->assertDontSee('Bluebird Retail')
            ->assertDontSee('5.00');
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
            ->assertSee('Name,Hours')
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
            ->assertSee('Date,User,Client,Task,Notes,Hours')
            ->assertSee('"Jun 09, 2026","Scalyn Member","Northwind Logistics","Website Build","Member note",1.75', false)
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
            ->assertSee('Date,User,Client,Task,Notes,Hours')
            ->assertSee('"Jun 09, 2026","Scalyn Member","Northwind Logistics","Website Build","Member note",1.75', false)
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
            ->assertSee('4.00');
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
            ->assertSee('name="client_id"', false)
            ->assertSee('All clients')
            ->assertSee('sort=date', false)
            ->assertSee('sort=hours', false);
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
            ->assertDontSee('Bluebird Review');
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
        $northwindClient = Client::factory()->create(['name' => 'Northwind Logistics']);
        $bluebirdClient = Client::factory()->create(['name' => 'Bluebird Retail']);
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
}
