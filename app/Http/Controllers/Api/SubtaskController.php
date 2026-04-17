<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        return $task->subtasks;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Task $task)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'is_completed' => 'sometimes|boolean'
        ]);
        $subtask = $task->subtasks()->create($data);
        return response()->json([
            'message' => 'Subtask created successfully',
            'subtask' => $subtask
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Subtask $subtask)
    {
        return response()->json($subtask);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Subtask $subtask)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'is_completed' => 'sometimes|boolean'
        ]);
        $subtask->update($data);
        return response()->json([
            'message' => 'Subtask updated successfully',
            'subtask' => $subtask
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Subtask $subtask)
    {
        $subtask->delete();
        return response()->json([
            'message' => 'subtask deleted successfully'
        ], 200);

    }
}
