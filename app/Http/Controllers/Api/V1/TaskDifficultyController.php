<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskDifficulty;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaskDifficultyController extends Controller
{
    public function store(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:technical,resource,external,other',
            'description' => 'required|string',
            'severity' => 'required|in:low,medium,high,critical',
            'proposed_solution' => 'nullable|string'
        ]);

        $difficulty = TaskDifficulty::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'type' => $request->type,
            'description' => $request->description,
            'severity' => $request->severity,
            'proposed_solution' => $request->proposed_solution,
            'status' => 'open'
        ]);

        if ($task->status !== 'bloque') {
            $task->update(['status' => 'bloque']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Difficulté signalée avec succès',
            'data' => $difficulty->load('user')
        ], 201);
    }

    public function update(Request $request, TaskDifficulty $difficulty): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:open,in_progress,resolved',
            'resolution' => 'nullable|string',
            'resolved_by' => 'nullable|exists:users,id'
        ]);

        $difficulty->update([
            'status' => $request->status,
            'resolution' => $request->resolution,
            'resolved_by' => $request->resolved_by,
            'resolved_at' => $request->status === 'resolved' ? now() : null
        ]);

        if ($request->status === 'resolved') {
            $openDifficulties = TaskDifficulty::where('task_id', $difficulty->task_id)
                ->where('status', '!=', 'resolved')
                ->count();

            if ($openDifficulties === 0) {
                $difficulty->task->update(['status' => 'en_cours']);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Difficulté mise à jour avec succès',
            'data' => $difficulty->load(['user', 'resolver'])
        ]);
    }

    public function index(Task $task): JsonResponse
    {
        $difficulties = $task->difficulties()
            ->with(['user', 'resolver'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $difficulties
        ]);
    }
}
