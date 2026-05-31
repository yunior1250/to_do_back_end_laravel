<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubtaskRequest;
use App\Http\Requests\UpdateSubtaskRequest;
use App\Http\Resources\SubtaskResource;
use Illuminate\Http\Request;
use App\Models\Subtask;
use App\Models\Task;

class SubtaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Task $task)
    {
        $this->authorize("view", $task);
        return SubtaskResource::collection($task->subtasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubtaskRequest $request, Task $task)
    {
        $subtask = $task->subtasks()->create($request->validated());
        return (new SubtaskResource($subtask))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Subtask $subtask)
    {
        $this->authorize('view', $subtask);
        return new SubtaskResource($subtask);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSubtaskRequest $request, Subtask $subtask)
    {
        $subtask->update($request->validated());

        return new SubtaskResource($subtask);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Subtask $subtask)
    {
        $this->authorize('delete', $subtask);
        $subtask->delete();
        return response()->noContent();

    }
}
