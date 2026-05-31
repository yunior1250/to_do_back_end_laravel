<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;

use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Task::class);
        return TaskResource::collection($request->user()->tasks()->with(['category', 'tags', 'subtasks'])->paginate(20));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request)
    {
        $data = $request->validated();

        $tagIds = $data['tag_ids'] ?? null;
        unset($data['tag_ids']);

        $task = $request->user()->tasks()->create($data);

        if ($tagIds !== null) {
            $task->tags()->sync($tagIds);
        }

        return (new TaskResource($task->load(['category', 'tags', 'subtasks'])))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        $this->authorize('view', $task);
        return new TaskResource($task->load(['category', 'tags', 'subtasks']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        $data = $request->validated();
        $tagIds = $data['tag_ids'] ?? null;
        unset($data['tag_ids']);

        $task->update($data);

        if ($request->has('tag_ids')) {
            $task->tags()->sync($tagIds ?? []);
        }

        return new TaskResource($task->load(['category', 'tags', 'subtasks']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);
        $task->delete();

        return response()->noContent();
    }
}
