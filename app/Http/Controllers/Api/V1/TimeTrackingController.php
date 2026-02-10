<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TaskTimeEntry;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class TimeTrackingController extends Controller
{
    public function startSession(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'description' => 'nullable|string|max:255'
        ]);

        $activeSession = TaskTimeEntry::where('user_id', auth()->id())
            ->whereNull('end_time')
            ->first();

        if ($activeSession) {
            return response()->json([
                'success' => false,
                'message' => 'Une session de temps est déjà active',
                'data' => $activeSession->load(['task', 'user'])
            ], 409);
        }

        $session = TaskTimeEntry::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'start_time' => now(),
            'description' => $request->description
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Session de temps démarrée',
            'data' => $session->load(['task', 'user'])
        ], 201);
    }

    public function stopSession(TaskTimeEntry $timeEntry): JsonResponse
    {
        if ($timeEntry->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Session non trouvée'
            ], 404);
        }

        if ($timeEntry->end_time) {
            return response()->json([
                'success' => false,
                'message' => 'Cette session est déjà terminée'
            ], 400);
        }

        $timeEntry->update([
            'end_time' => now(),
            'hours' => now()->diffInMinutes($timeEntry->start_time) / 60
        ]);

        $timeEntry->task->updateActualHours();

        return response()->json([
            'success' => true,
            'message' => 'Session de temps arrêtée',
            'data' => $timeEntry->load(['task', 'user'])
        ]);
    }

    public function getCurrentSession(): JsonResponse
    {
        $session = TaskTimeEntry::where('user_id', auth()->id())
            ->whereNull('end_time')
            ->with(['task', 'user'])
            ->first();

        return response()->json([
            'success' => true,
            'data' => $session
        ]);
    }

    public function updateSession(Request $request, TaskTimeEntry $timeEntry): JsonResponse
    {
        if ($timeEntry->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Session non trouvée'
            ], 404);
        }

        $request->validate([
            'description' => 'sometimes|string|max:255',
            'hours' => 'sometimes|numeric|min:0.1|max:24',
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date|after:start_time'
        ]);

        $timeEntry->update($request->only(['description', 'hours', 'start_time', 'end_time']));

        if ($request->has('hours') || $request->has('end_time')) {
            $timeEntry->task->updateActualHours();
        }

        return response()->json([
            'success' => true,
            'message' => 'Session mise à jour',
            'data' => $timeEntry->load(['task', 'user'])
        ]);
    }

    public function deleteSession(TaskTimeEntry $timeEntry): JsonResponse
    {
        if ($timeEntry->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Session non trouvée'
            ], 404);
        }

        $taskId = $timeEntry->task_id;
        $timeEntry->delete();

        Task::find($taskId)?->updateActualHours();

        return response()->json([
            'success' => true,
            'message' => 'Session supprimée'
        ]);
    }

    public function getUserSessions(Request $request): JsonResponse
    {
        $query = TaskTimeEntry::where('user_id', auth()->id())
            ->with(['task', 'user']);

        if ($request->has('task_id')) {
            $query->where('task_id', $request->task_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('start_time', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('start_time', '<=', $request->date_to);
        }

        $sessions = $query->orderBy('start_time', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    public function getTimeReport(Request $request): JsonResponse
    {
        $query = TaskTimeEntry::where('user_id', auth()->id())
            ->whereNotNull('hours')
            ->with(['task']);

        if ($request->has('date_from')) {
            $query->whereDate('start_time', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('start_time', '<=', $request->date_to);
        }

        $sessions = $query->get();

        $totalHours = $sessions->sum('hours');
        $tasksWorked = $sessions->groupBy('task_id')->count();

        $dailyBreakdown = $sessions->groupBy(function($session) {
            return $session->start_time->format('Y-m-d');
        })->map(function($daySessions) {
            return [
                'total_hours' => $daySessions->sum('hours'),
                'sessions_count' => $daySessions->count(),
                'tasks' => $daySessions->groupBy('task_id')->keys()
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'total_hours' => round($totalHours, 2),
                'tasks_worked' => $tasksWorked,
                'daily_breakdown' => $dailyBreakdown,
                'sessions' => $sessions
            ]
        ]);
    }
}
