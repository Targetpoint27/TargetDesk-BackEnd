<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\TaskTimeEntry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProjectTimeTrackingController extends Controller
{
    public function getProjectTimeSummary(Project $project, Request $request): JsonResponse
    {
        $query = TaskTimeEntry::whereHas('task', function($q) use ($project) {
                $q->where('project_id', $project->id);
            })
            ->with(['task', 'user']);

        if ($request->has('date_from')) {
            $query->whereDate('start_time', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('start_time', '<=', $request->date_to);
        }

        $timeEntries = $query->get();

        $summary = [
            'project' => $project,
            'total_estimated_hours' => $project->tasks()->sum('estimated_hours'),
            'total_actual_hours' => $timeEntries->sum('hours'),
            'variance' => $timeEntries->sum('hours') - $project->tasks()->sum('estimated_hours'),
            'completion_percentage' => $project->tasks()->sum('estimated_hours') > 0
                ? ($timeEntries->sum('hours') / $project->tasks()->sum('estimated_hours')) * 100
                : 0,
            'by_user' => $timeEntries->groupBy('user_id')->map(function($userEntries) {
                return [
                    'user' => $userEntries->first()->user,
                    'total_hours' => $userEntries->sum('hours'),
                    'entries_count' => $userEntries->count(),
                    'tasks_worked' => $userEntries->unique('task_id')->count()
                ];
            }),
            'by_task_type' => $timeEntries->groupBy('task.type')->map(function($typeEntries) {
                return [
                    'total_hours' => $typeEntries->sum('hours'),
                    'percentage' => count($timeEntries) > 0 ? ($typeEntries->sum('hours') / $timeEntries->sum('hours')) * 100 : 0
                ];
            })
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    public function getProjectTimeEntries(Project $project, Request $request): JsonResponse
    {
        $query = TaskTimeEntry::whereHas('task', function($q) use ($project) {
                $q->where('project_id', $project->id);
            })
            ->with(['task', 'user']);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('task_type')) {
            $query->whereHas('task', function($q) use ($request) {
                $q->where('type', $request->task_type);
            });
        }

        if ($request->has('date_from')) {
            $query->whereDate('start_time', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('start_time', '<=', $request->date_to);
        }

        $timeEntries = $query->orderBy('start_time', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $timeEntries
        ]);
    }

    public function getProjectTimeAnalytics(Project $project, Request $request): JsonResponse
    {
        $query = TaskTimeEntry::whereHas('task', function($q) use ($project) {
                $q->where('project_id', $project->id);
            })
            ->with(['task', 'user']);

        if ($request->has('date_from')) {
            $query->whereDate('start_time', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('start_time', '<=', $request->date_to);
        }

        $timeEntries = $query->get();

        $analytics = [
            'time_evolution' => $timeEntries->groupBy(function($entry) {
                return $entry->start_time->format('Y-m-d');
            })->map(function($dayEntries) {
                return $dayEntries->sum('hours');
            }),
            'user_distribution' => $timeEntries->groupBy('user.name')->map(function($userEntries) use ($timeEntries) {
                return [
                    'hours' => $userEntries->sum('hours'),
                    'percentage' => $timeEntries->sum('hours') > 0
                        ? round(($userEntries->sum('hours') / $timeEntries->sum('hours')) * 100, 2)
                        : 0
                ];
            })
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }
}
