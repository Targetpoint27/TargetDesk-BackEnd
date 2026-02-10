<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TaskView;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaskViewController extends Controller
{
    public function index(): JsonResponse
    {
        $views = TaskView::where('user_id', auth()->id())
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $views
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'filters' => 'required|array',
            'sort_by' => 'nullable|string|in:title,status,priority,deadline,created_at,updated_at',
            'sort_direction' => 'nullable|string|in:asc,desc',
            'is_default' => 'boolean'
        ]);

        if ($request->is_default) {
            TaskView::where('user_id', auth()->id())
                ->update(['is_default' => false]);
        }

        $view = TaskView::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'filters' => json_encode($request->filters),
            'sort_by' => $request->sort_by ?? 'updated_at',
            'sort_direction' => $request->sort_direction ?? 'desc',
            'is_default' => $request->is_default ?? false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vue personnalisée créée avec succès',
            'data' => $view
        ], 201);
    }

    public function show(TaskView $view): JsonResponse
    {
        if ($view->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vue non trouvée'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $view
        ]);
    }

    public function update(Request $request, TaskView $view): JsonResponse
    {
        if ($view->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vue non trouvée'
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'filters' => 'sometimes|array',
            'sort_by' => 'nullable|string|in:title,status,priority,deadline,created_at,updated_at',
            'sort_direction' => 'nullable|string|in:asc,desc',
            'is_default' => 'boolean'
        ]);

        if ($request->is_default) {
            TaskView::where('user_id', auth()->id())
                ->where('id', '!=', $view->id)
                ->update(['is_default' => false]);
        }

        $view->update([
            'name' => $request->name ?? $view->name,
            'filters' => isset($request->filters) ? json_encode($request->filters) : $view->filters,
            'sort_by' => $request->sort_by ?? $view->sort_by,
            'sort_direction' => $request->sort_direction ?? $view->sort_direction,
            'is_default' => $request->is_default ?? $view->is_default
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vue mise à jour avec succès',
            'data' => $view
        ]);
    }

    public function destroy(TaskView $view): JsonResponse
    {
        if ($view->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vue non trouvée'
            ], 404);
        }

        $view->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vue supprimée avec succès'
        ]);
    }

    public function apply(TaskView $view): JsonResponse
    {
        if ($view->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vue non trouvée'
            ], 404);
        }

        $filters = json_decode($view->filters, true);

        $query = Task::with(['assignees', 'project', 'tags'])
            ->whereHas('assignees', function($q) {
                $q->where('user_id', auth()->id());
            });

        foreach ($filters as $key => $value) {
            if (empty($value)) continue;

            switch ($key) {
                case 'status':
                    if (is_array($value)) {
                        $query->whereIn('status', $value);
                    } else {
                        $query->where('status', $value);
                    }
                    break;
                case 'priority':
                    if (is_array($value)) {
                        $query->whereIn('priority', $value);
                    } else {
                        $query->where('priority', $value);
                    }
                    break;
                case 'project_id':
                    if (is_array($value)) {
                        $query->whereIn('project_id', $value);
                    } else {
                        $query->where('project_id', $value);
                    }
                    break;
                case 'tags':
                    if (is_array($value)) {
                        $query->whereHas('tags', function($q) use ($value) {
                            $q->whereIn('task_tags.id', $value);
                        });
                    }
                    break;
                case 'deadline':
                    if (isset($value['from'])) {
                        $query->where('deadline', '>=', $value['from']);
                    }
                    if (isset($value['to'])) {
                        $query->where('deadline', '<=', $value['to']);
                    }
                    break;
            }
        }

        $tasks = $query->orderBy($view->sort_by, $view->sort_direction)->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'view' => $view,
                'tasks' => $tasks
            ]
        ]);
    }
}
