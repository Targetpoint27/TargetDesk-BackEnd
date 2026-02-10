<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Jobs\SendTaskNotificationJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaskNotificationController extends Controller
{
    public function notifyAssignment(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'message' => 'nullable|string'
        ]);

        $users = \App\Models\User::whereIn('id', $request->user_ids)->get();

        foreach ($users as $user) {
            SendTaskNotificationJob::dispatch(
                $user,
                $task,
                'task_assigned',
                [
                    'message' => $request->message,
                    'assigned_by' => auth()->user()->name
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifications d\'assignation envoyées'
        ]);
    }

    public function notifyStatusChange(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'old_status' => 'required|string',
            'new_status' => 'required|string',
            'message' => 'nullable|string'
        ]);

        foreach ($task->assignees as $user) {
            SendTaskNotificationJob::dispatch(
                $user,
                $task,
                'task_status_changed',
                [
                    'old_status' => $request->old_status,
                    'new_status' => $request->new_status,
                    'message' => $request->message,
                    'changed_by' => auth()->user()->name
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifications de changement de statut envoyées'
        ]);
    }

    public function notifyDeadlineApproaching(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'days_remaining' => 'required|integer|min:0'
        ]);

        foreach ($task->assignees as $user) {
            SendTaskNotificationJob::dispatch(
                $user,
                $task,
                'task_deadline_approaching',
                [
                    'days_remaining' => $request->days_remaining,
                    'deadline' => $task->due_date->format('Y-m-d H:i:s')
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifications d\'échéance envoyées'
        ]);
    }

    public function notifyCommentAdded(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'comment' => 'required|string',
            'mentions' => 'nullable|array',
            'mentions.*' => 'exists:users,id'
        ]);

        $notifiedUsers = collect($request->mentions ?? []);

        // Notify mentioned users
        if ($notifiedUsers->isNotEmpty()) {
            $users = \App\Models\User::whereIn('id', $notifiedUsers)->get();

            foreach ($users as $user) {
                SendTaskNotificationJob::dispatch(
                    $user,
                    $task,
                    'task_comment_mention',
                    [
                        'comment' => $request->comment,
                        'mentioned_by' => auth()->user()->name
                    ]
                );
            }
        }

        // Notify task assignees (excluding author and mentioned users)
        foreach ($task->assignees as $user) {
            if ($user->id !== auth()->id() && !$notifiedUsers->contains($user->id)) {
                SendTaskNotificationJob::dispatch(
                    $user,
                    $task,
                    'task_comment_added',
                    [
                        'comment' => $request->comment,
                        'commented_by' => auth()->user()->name
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifications de commentaire envoyées'
        ]);
    }

    public function notifyDifficultyReported(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'type' => 'required|string',
            'severity' => 'required|string',
            'description' => 'required|string'
        ]);

        // Notify project manager and task creator
        $usersToNotify = collect([$task->creator, $task->project->creator])
            ->filter()
            ->unique('id')
            ->reject(function ($user) {
                return $user->id === auth()->id();
            });

        foreach ($usersToNotify as $user) {
            SendTaskNotificationJob::dispatch(
                $user,
                $task,
                'task_difficulty_reported',
                [
                    'type' => $request->type,
                    'severity' => $request->severity,
                    'description' => $request->description,
                    'reported_by' => auth()->user()->name
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifications de difficulté envoyées'
        ]);
    }
}
