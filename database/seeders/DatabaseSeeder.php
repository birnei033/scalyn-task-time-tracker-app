<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Task;
use App\Models\Team;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $operationsTeam = Team::updateOrCreate(
            ['name' => 'Scalyn Operations'],
            ['name' => 'Scalyn Operations'],
        );

        $deliveryTeam = Team::updateOrCreate(
            ['name' => 'Client Delivery'],
            ['name' => 'Client Delivery'],
        );

        $users = [
            'admin' => User::updateOrCreate(
                ['email' => 'admin@scalyn.local'],
                [
                    'name' => 'Scalyn Admin',
                    'role' => 'admin',
                    'team_id' => $operationsTeam->id,
                    'password' => Hash::make('Password123!'),
                    'email_verified_at' => now(),
                ],
            ),
            'manager' => User::updateOrCreate(
                ['email' => 'manager@scalyn.local'],
                [
                    'name' => 'Scalyn Manager',
                    'role' => 'manager',
                    'team_id' => $operationsTeam->id,
                    'password' => Hash::make('Password123!'),
                    'email_verified_at' => now(),
                ],
            ),
            'member' => User::updateOrCreate(
                ['email' => 'member@scalyn.local'],
                [
                    'name' => 'Scalyn Member',
                    'role' => 'member',
                    'team_id' => $deliveryTeam->id,
                    'password' => Hash::make('Password123!'),
                    'email_verified_at' => now(),
                ],
            ),
        ];

        $clients = [
            'acme' => Client::updateOrCreate(
                ['name' => 'Acme Manufacturing'],
                [
                    'name' => 'Acme Manufacturing',
                    'contact_person' => 'Jordan Lee',
                    'email' => 'jordan.lee@acme.test',
                    'company' => 'Acme Manufacturing Inc.',
                    'status' => 'active',
                    'notes' => 'Priority client for portal maintenance and weekly support.',
                    'archived_at' => null,
                ],
            ),
            'northwind' => Client::updateOrCreate(
                ['name' => 'Northwind Logistics'],
                [
                    'name' => 'Northwind Logistics',
                    'contact_person' => 'Maya Santos',
                    'email' => 'maya.santos@northwind.test',
                    'company' => 'Northwind Logistics',
                    'status' => 'active',
                    'notes' => 'Tracks shipping dashboard updates and reporting fixes.',
                    'archived_at' => null,
                ],
            ),
            'bluebird' => Client::updateOrCreate(
                ['name' => 'Bluebird Retail'],
                [
                    'name' => 'Bluebird Retail',
                    'contact_person' => 'Sam Rivera',
                    'email' => 'sam.rivera@bluebird.test',
                    'company' => 'Bluebird Retail Group',
                    'status' => 'archived',
                    'notes' => 'Completed launch support. Kept for historical reporting.',
                    'archived_at' => now()->subMonths(2),
                ],
            ),
        ];

        $tasks = [
            Task::updateOrCreate(
                ['title' => 'Portal redesign discovery', 'client_id' => $clients['acme']->id],
                [
                    'client_id' => $clients['acme']->id,
                    'assigned_user_id' => $users['manager']->id,
                    'title' => 'Portal redesign discovery',
                    'description' => 'Collect requirements and review the current client portal experience.',
                    'status' => 'in_progress',
                    'priority' => 'high',
                ],
            ),
            Task::updateOrCreate(
                ['title' => 'Invoice workflow cleanup', 'client_id' => $clients['acme']->id],
                [
                    'client_id' => $clients['acme']->id,
                    'assigned_user_id' => $users['member']->id,
                    'title' => 'Invoice workflow cleanup',
                    'description' => 'Remove duplicate steps from invoice review and approval.',
                    'status' => 'open',
                    'priority' => 'medium',
                ],
            ),
            Task::updateOrCreate(
                ['title' => 'Shipping dashboard refresh', 'client_id' => $clients['northwind']->id],
                [
                    'client_id' => $clients['northwind']->id,
                    'assigned_user_id' => $users['member']->id,
                    'title' => 'Shipping dashboard refresh',
                    'description' => 'Improve the dashboard layout and update summary cards.',
                    'status' => 'in_progress',
                    'priority' => 'high',
                ],
            ),
            Task::updateOrCreate(
                ['title' => 'Support SLA follow-up', 'client_id' => $clients['northwind']->id],
                [
                    'client_id' => $clients['northwind']->id,
                    'assigned_user_id' => $users['manager']->id,
                    'title' => 'Support SLA follow-up',
                    'description' => 'Review open support tickets and send a status update.',
                    'status' => 'to_review',
                    'priority' => 'medium',
                ],
            ),
            Task::updateOrCreate(
                ['title' => 'Launch checklist closeout', 'client_id' => $clients['bluebird']->id],
                [
                    'client_id' => $clients['bluebird']->id,
                    'assigned_user_id' => $users['admin']->id,
                    'title' => 'Launch checklist closeout',
                    'description' => 'Wrap up the final launch items and document lessons learned.',
                    'status' => 'completed',
                    'priority' => 'low',
                ],
            ),
        ];

        TimeEntry::updateOrCreate(
            [
                'user_id' => $users['manager']->id,
                'task_id' => $tasks[0]->id,
                'date' => '2026-06-08',
            ],
            [
                'user_id' => $users['manager']->id,
                'task_id' => $tasks[0]->id,
                'date' => '2026-06-08',
                'hours' => 3.50,
                'notes' => 'Completed discovery call and drafted follow-up questions.',
            ],
        );

        TimeEntry::updateOrCreate(
            [
                'user_id' => $users['member']->id,
                'task_id' => $tasks[1]->id,
                'date' => '2026-06-08',
            ],
            [
                'user_id' => $users['member']->id,
                'task_id' => $tasks[1]->id,
                'date' => '2026-06-08',
                'hours' => 2.25,
                'notes' => 'Reviewed invoice routing and cleaned up the approval flow.',
            ],
        );

        TimeEntry::updateOrCreate(
            [
                'user_id' => $users['member']->id,
                'task_id' => $tasks[2]->id,
                'date' => '2026-06-09',
            ],
            [
                'user_id' => $users['member']->id,
                'task_id' => $tasks[2]->id,
                'date' => '2026-06-09',
                'hours' => 4.00,
                'notes' => 'Updated dashboard cards and adjusted responsive spacing.',
            ],
        );

        TimeEntry::updateOrCreate(
            [
                'user_id' => $users['manager']->id,
                'task_id' => $tasks[3]->id,
                'date' => '2026-06-09',
            ],
            [
                'user_id' => $users['manager']->id,
                'task_id' => $tasks[3]->id,
                'date' => '2026-06-09',
                'hours' => 1.75,
                'notes' => 'Collected ticket status updates and emailed the client summary.',
            ],
        );

        TimeEntry::updateOrCreate(
            [
                'user_id' => $users['admin']->id,
                'task_id' => $tasks[4]->id,
                'date' => '2026-06-10',
            ],
            [
                'user_id' => $users['admin']->id,
                'task_id' => $tasks[4]->id,
                'date' => '2026-06-10',
                'hours' => 1.50,
                'notes' => 'Closed out the launch checklist and documented follow-up items.',
            ],
        );
    }
}
