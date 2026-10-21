<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'description', 'priority', 'status', 'due_date', 'completed_at'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function isOverdue(): bool
    {
        return $this->status === TaskStatus::Pending
            && $this->due_date !== null
            && $this->due_date->lt(today());
    }

    /**
     * @param  Builder<Task>  $query
     * @return Builder<Task>
     */
    public function scopeMatching(Builder $query, string $term): Builder
    {
        $escaped = addcslashes($term, '\\%_');
        $like = '%'.$escaped.'%';

        return $query->where(function (Builder $query) use ($like): void {
            $query->whereRaw('title like ? escape ?', [$like, '\\'])
                ->orWhereRaw('description like ? escape ?', [$like, '\\']);
        });
    }
}
