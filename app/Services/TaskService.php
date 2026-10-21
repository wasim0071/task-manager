<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TaskService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Task>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Task::query();

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->matching($search);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        $this->applySort(
            $query,
            (string) ($filters['sort'] ?? 'created_at'),
            (string) ($filters['direction'] ?? 'desc'),
        );

        $perPage = (int) ($filters['per_page'] ?? 8);
        $perPage = max(1, min($perPage, 50));

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Counts stay global so the board totals do not change while a filter is active.
     *
     * @return array{total: int, open: int, completed: int}
     */
    public function stats(): array
    {
        $counts = Task::query()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as completed', [TaskStatus::Completed->value])
            ->first();

        $total = (int) ($counts->total ?? 0);
        $completed = (int) ($counts->completed ?? 0);

        return [
            'total' => $total,
            'completed' => $completed,
            'open' => $total - $completed,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Task
    {
        $attributes['status'] ??= TaskStatus::Pending->value;

        return Task::query()->create($this->withCompletionTimestamp(new Task, $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Task $task, array $attributes): Task
    {
        $task->update($this->withCompletionTimestamp($task, $attributes));

        return $task->refresh();
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }

    /**
     * Completion time is owned by the server. Clients can change status, not the timestamp.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function withCompletionTimestamp(Task $task, array $attributes): array
    {
        if (! array_key_exists('status', $attributes)) {
            return $attributes;
        }

        $status = $attributes['status'] instanceof TaskStatus
            ? $attributes['status']
            : TaskStatus::from((string) $attributes['status']);

        $attributes['completed_at'] = $status === TaskStatus::Completed
            ? ($task->completed_at ?? now())
            : null;

        return $attributes;
    }

    /**
     * @param  Builder<Task>  $query
     */
    private function applySort(Builder $query, string $sort, string $direction): void
    {
        $allowed = ['created_at', 'due_date', 'priority', 'title'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'created_at';
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        if ($sort === 'priority') {
            $query->orderByRaw("case priority when 'high' then 1 when 'medium' then 2 else 3 end {$direction}");

            return;
        }

        if ($sort === 'due_date') {
            $query->orderByRaw('case when due_date is null then 1 else 0 end');
            $query->orderBy('due_date', $direction);

            return;
        }

        $query->orderBy($sort, $direction);
    }
}
