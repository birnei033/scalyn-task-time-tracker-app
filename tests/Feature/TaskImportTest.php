<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TaskImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_open_task_import_flow(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)->get(route('tasks.import.create'))
            ->assertOk()
            ->assertSee('Import Tasks')
            ->assertSee('name="file"', false);
    }

    public function test_member_cannot_access_task_import_flow(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)->get(route('tasks.import.create'))
            ->assertForbidden();
    }

    public function test_upload_parses_headers_and_sample_rows(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $csv = <<<CSV
Client,Title,Assigned User,Description,Status,Priority
Northwind,Portal Cleanup,Maya Santos,Landing page cleanup,In Progress,High
Northwind,QA Review,,Checklist review,Open,Medium
CSV;

        $response = $this->actingAs($manager)->post(route('tasks.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('tasks.csv', $csv),
        ]);

        $response->assertRedirect();

        $location = $response->headers->get('Location');
        $path = parse_url($location, PHP_URL_PATH) ?: $location;

        $this->actingAs($manager)->get($path)
            ->assertOk()
            ->assertSee('Client')
            ->assertSee('Portal Cleanup')
            ->assertSee('QA Review')
            ->assertSee('name="mapping[client_id]"', false);
    }

    public function test_required_mappings_are_enforced(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $csv = <<<CSV
Client,Title
Northwind,Portal Cleanup
CSV;

        $upload = $this->actingAs($manager)->post(route('tasks.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('tasks.csv', $csv),
        ]);

        $location = $upload->headers->get('Location');
        $path = parse_url($location, PHP_URL_PATH) ?: $location;
        $token = basename($path);

        $this->actingAs($manager)->post(route('tasks.import.process', $token), [
            'mapping' => [
                'client_id' => 'Client',
                'title' => '',
            ],
        ])->assertSessionHasErrors(['mapping.title']);
    }

    public function test_valid_csv_creates_tasks_with_defaults_and_exact_matches(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $client = Client::factory()->create(['name' => 'Northwind']);
        $assignee = User::factory()->create(['role' => 'member', 'name' => 'Maya Santos']);
        $csv = <<<CSV
Client,Title,Assigned User,Description,Status,Priority
Northwind,Portal Cleanup,Maya Santos,Landing page cleanup,In Progress,High
Northwind,QA Review,,,,
CSV;

        $upload = $this->actingAs($manager)->post(route('tasks.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('tasks.csv', $csv),
        ]);

        $location = $upload->headers->get('Location');
        $path = parse_url($location, PHP_URL_PATH) ?: $location;
        $token = basename($path);

        $this->actingAs($manager)->post(route('tasks.import.process', $token), [
            'mapping' => [
                'client_id' => 'Client',
                'assigned_user_id' => 'Assigned User',
                'title' => 'Title',
                'description' => 'Description',
                'status' => 'Status',
                'priority' => 'Priority',
            ],
        ])
            ->assertOk()
            ->assertSee('Created')
            ->assertSee('2')
            ->assertSee('QA Review')
            ->assertSee('Portal Cleanup');

        $this->assertDatabaseHas('tasks', [
            'client_id' => $client->id,
            'assigned_user_id' => $assignee->id,
            'title' => 'Portal Cleanup',
            'status' => 'in_progress',
            'priority' => 'high',
        ]);

        $this->assertDatabaseHas('tasks', [
            'client_id' => $client->id,
            'title' => 'QA Review',
            'status' => 'open',
            'priority' => 'medium',
        ]);
    }

    public function test_invalid_rows_are_reported_without_blocking_valid_rows(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        Client::factory()->create(['name' => 'Northwind']);
        User::factory()->create(['role' => 'member', 'name' => 'Maya Santos']);
        $csv = <<<CSV
Client,Title,Assigned User
Northwind,Portal Cleanup,
Missing Client,Bad Task,Maya Santos
CSV;

        $upload = $this->actingAs($manager)->post(route('tasks.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('tasks.csv', $csv),
        ]);

        $location = $upload->headers->get('Location');
        $path = parse_url($location, PHP_URL_PATH) ?: $location;
        $token = basename($path);

        $this->actingAs($manager)->post(route('tasks.import.process', $token), [
            'mapping' => [
                'client_id' => 'Client',
                'assigned_user_id' => 'Assigned User',
                'title' => 'Title',
                'description' => '',
                'status' => '',
                'priority' => '',
            ],
        ])
            ->assertOk()
            ->assertSee('Created')
            ->assertSee('Failed')
            ->assertSee('Missing Client', false);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Portal Cleanup',
        ]);

        $this->assertDatabaseMissing('tasks', [
            'title' => 'Bad Task',
        ]);
    }
}
