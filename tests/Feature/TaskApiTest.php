<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Support\InputLimits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_tasks_with_board_stats(): void
    {
        Task::factory()->count(2)->create();
        Task::factory()->completed()->create();

        $response = $this->getJson('/api/v1/tasks');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('stats.total', 3)
            ->assertJsonPath('stats.completed', 1)
            ->assertJsonPath('stats.open', 2)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'description', 'priority', 'status', 'due_date', 'completed_at', 'is_overdue'],
                ],
                'meta' => ['current_page', 'last_page', 'total'],
            ]);
    }

    public function test_it_paginates_the_list(): void
    {
        Task::factory()->count(7)->create();

        $this->getJson('/api/v1/tasks?per_page=6')
            ->assertOk()
            ->assertJsonCount(6, 'data')
            ->assertJsonPath('meta.total', 7)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_it_filters_by_status_and_searches_without_treating_percent_as_a_wildcard(): void
    {
        Task::factory()->create(['title' => '100% coverage', 'description' => null]);
        Task::factory()->create(['title' => 'Plain task', 'description' => 'coverage notes']);
        Task::factory()->completed()->create(['title' => '100% archived', 'description' => null]);

        $this->getJson('/api/v1/tasks?search='.urlencode('100%').'&status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', '100% coverage');
    }

    public function test_it_sorts_priority_with_high_first(): void
    {
        Task::factory()->create(['title' => 'Low one', 'priority' => TaskPriority::Low]);
        Task::factory()->create(['title' => 'High one', 'priority' => TaskPriority::High]);

        $this->getJson('/api/v1/tasks?sort=priority&direction=asc')
            ->assertOk()
            ->assertJsonPath('data.0.priority', 'high');
    }

    public function test_it_rejects_an_unknown_sort_column(): void
    {
        $this->getJson('/api/v1/tasks?sort=created_at;drop')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sort');
    }

    public function test_it_creates_a_task_and_ignores_a_client_supplied_completion_time(): void
    {
        $response = $this->postJson('/api/v1/tasks', [
            'title' => 'Ship the board',
            'description' => 'Keep the API and the interface on the same contract.',
            'priority' => 'high',
            'due_date' => '2026-10-22',
            'completed_at' => '2000-01-01 00:00:00',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Ship the board')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.due_date', '2026-10-22')
            ->assertJsonPath('data.completed_at', null)
            ->assertJsonPath('message', 'Task created.');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Ship the board',
            'completed_at' => null,
        ]);
    }

    public function test_it_validates_a_new_task(): void
    {
        $this->postJson('/api/v1/tasks', [
            'title' => '',
            'priority' => 'urgent',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'priority']);
    }

    public function test_it_rejects_oversized_task_input(): void
    {
        $this->postJson('/api/v1/tasks', [
            'title' => '   ',
            'priority' => 'low',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('title');

        $this->postJson('/api/v1/tasks', [
            'title' => str_repeat('a', InputLimits::TITLE + 1),
            'description' => str_repeat('b', InputLimits::DESCRIPTION + 1),
            'priority' => 'low',
            'due_date' => '1800-01-01',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'description', 'due_date']);

        $task = Task::factory()->create();

        $this->putJson("/api/v1/tasks/{$task->id}", [
            'title' => str_repeat('c', InputLimits::TITLE + 1),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('title');

        $this->getJson('/api/v1/tasks?search='.urlencode(str_repeat('d', InputLimits::SEARCH + 1)))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('search');

        $this->getJson('/api/v1/tasks?page=1001&per_page=500')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page', 'per_page']);

        $this->postJson('/api/v1/tasks', [
            'title' => str_repeat('e', InputLimits::TITLE),
            'description' => str_repeat('f', InputLimits::DESCRIPTION),
            'priority' => 'medium',
            'due_date' => InputLimits::DUE_MAX,
        ])->assertCreated()
            ->assertJsonPath('data.due_date', InputLimits::DUE_MAX);
    }

    public function test_it_updates_a_task_without_clearing_completion(): void
    {
        $task = Task::factory()->completed()->create([
            'completed_at' => '2026-01-01 10:00:00',
        ]);

        $this->putJson("/api/v1/tasks/{$task->id}", [
            'title' => 'Still done',
            'priority' => 'low',
            'status' => 'completed',
        ])->assertOk()
            ->assertJsonPath('data.title', 'Still done')
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('message', 'Task updated.');

        $fresh = $task->fresh();
        $this->assertNotNull($fresh);
        $this->assertTrue($fresh->completed_at?->equalTo('2026-01-01 10:00:00'));
        $this->assertSame(TaskStatus::Completed, $fresh->status);
    }

    public function test_it_marks_a_task_completed_and_can_reopen_it(): void
    {
        $task = Task::factory()->create();

        $this->patchJson("/api/v1/tasks/{$task->id}", [
            'status' => 'completed',
        ])->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->assertNotNull($task->fresh()->completed_at);

        $this->patchJson("/api/v1/tasks/{$task->id}", [
            'status' => 'pending',
        ])->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.completed_at', null);
    }

    public function test_it_flags_an_open_task_with_a_past_due_date(): void
    {
        $task = Task::factory()->create([
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $this->getJson("/api/v1/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonPath('data.is_overdue', true);

        $done = Task::factory()->completed()->create([
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $this->getJson("/api/v1/tasks/{$done->id}")
            ->assertOk()
            ->assertJsonPath('data.is_overdue', false);
    }

    public function test_it_deletes_a_task(): void
    {
        $task = Task::factory()->create();

        $this->deleteJson("/api/v1/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Task deleted.');

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_it_returns_not_found_for_a_missing_task(): void
    {
        $this->getJson('/api/v1/tasks/9999')->assertNotFound();
        $this->deleteJson('/api/v1/tasks/9999')->assertNotFound();
    }
}
