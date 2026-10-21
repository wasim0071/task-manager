<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $tasks) {}

    public function index(IndexTaskRequest $request): AnonymousResourceCollection
    {
        return TaskResource::collection($this->tasks->paginate($request->validated()))
            ->additional([
                'stats' => $this->tasks->stats(),
            ]);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->tasks->create($request->validated());

        return (new TaskResource($task))
            ->additional(['message' => 'Task created.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Task $task): TaskResource
    {
        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $task = $this->tasks->update($task, $request->validated());

        return (new TaskResource($task))->additional([
            'message' => 'Task updated.',
        ]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->tasks->delete($task);

        return response()->json([
            'message' => 'Task deleted.',
        ]);
    }
}
