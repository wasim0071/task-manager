<?php

namespace Database\Seeders;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        if (Task::query()->exists()) {
            return;
        }

        $tasks = [
            [
                'title' => 'Publish the API contract',
                'description' => 'Confirm resource shape, status codes, and validation errors before the interface locks to them.',
                'priority' => TaskPriority::High,
                'status' => TaskStatus::Pending,
                'due_date' => now()->addDay()->toDateString(),
            ],
            [
                'title' => 'Walk the empty and error states',
                'description' => 'Check the board with no matches, a failed save, and a weather lookup that misses the city.',
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::Pending,
                'due_date' => now()->addDays(3)->toDateString(),
            ],
            [
                'title' => 'Write the deployment notes',
                'description' => 'Capture how the Laravel API, built Vue assets, and MySQL would move behind a load balancer.',
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::Pending,
                'due_date' => now()->subDay()->toDateString(),
            ],
            [
                'title' => 'Pair with QA on delete',
                'description' => 'Confirm the row asks before it removes a task, and that the totals update after the API responds.',
                'priority' => TaskPriority::High,
                'status' => TaskStatus::Pending,
                'due_date' => now()->addDays(5)->toDateString(),
            ],
            [
                'title' => 'Index status and due date',
                'description' => 'Keep the open-task query on an index so the board stays quick as the table grows.',
                'priority' => TaskPriority::Low,
                'status' => TaskStatus::Completed,
                'due_date' => now()->subDays(2)->toDateString(),
                'completed_at' => now()->subDay(),
            ],
            [
                'title' => 'Cache the weather lookup',
                'description' => 'Store a successful Open-Meteo response for ten minutes so the panel does not call the public API on every refresh.',
                'priority' => TaskPriority::Low,
                'status' => TaskStatus::Completed,
                'due_date' => null,
                'completed_at' => now()->subHours(5),
            ],
            [
                'title' => 'Tighten the validation copy',
                'description' => 'Make the title and priority errors say what to fix, not only which rule failed.',
                'priority' => TaskPriority::Medium,
                'status' => TaskStatus::Pending,
                'due_date' => now()->toDateString(),
            ],
            [
                'title' => 'Record the schema in the README',
                'description' => 'Leave the tasks table, indexes, and column meanings where a reviewer can find them quickly.',
                'priority' => TaskPriority::Low,
                'status' => TaskStatus::Pending,
                'due_date' => now()->addDays(2)->toDateString(),
            ],
        ];

        foreach ($tasks as $index => $task) {
            $moment = now()->subMinutes($index);

            Task::query()->create(array_merge($task, [
                'created_at' => $moment,
                'updated_at' => $moment,
            ]));
        }
    }
}
