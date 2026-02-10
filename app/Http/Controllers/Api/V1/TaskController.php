<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTaskRequest;
use App\Http\Requests\Api\UpdateTaskRequest;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskTag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TaskController extends Controller
{
    /**
     * Display a listing of tasks with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Task::with(['project', 'assignedUsers', 'tags', 'creator']);

        // Filtres
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('assigned_to')) {
            $query->assignedTo($request->assigned_to);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('overdue') && $request->overdue === 'true') {
            $query->overdue();
        }

        // Recherche par titre ou description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Tri
        $sortField = $request->get('sort_by', 'priority');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($sortField === 'priority') {
            $query->orderByRaw("FIELD(priority, 'critique', 'haute', 'normale', 'basse')");
        } else {
            $query->orderBy($sortField, $sortOrder);
        }

        $tasks = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $tasks,
            'message' => 'Tâches récupérées avec succès'
        ]);
    }

    /**
     * Store a newly created task
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            // Générer le code automatiquement
            $data['code'] = Task::generateUniqueCode();
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            $data['status'] = $data['status'] ?? 'a_faire';
            $data['priority'] = $data['priority'] ?? 'normale';
            $data['type'] = $data['type'] ?? 'autre';

            // Extraire les assignés et tags
            $assignedTo = $data['assigned_to'];
            $tags = $data['tags'] ?? [];
            unset($data['assigned_to'], $data['tags']);

            $task = Task::create($data);

            // Assigner les utilisateurs
            foreach ($assignedTo as $userId) {
                TaskAssignment::create([
                    'task_id' => $task->id,
                    'user_id' => $userId,
                    'assigned_at' => now(),
                    'assigned_by' => Auth::id()
                ]);
            }

            // Gérer les tags
            $tagIds = [];
            foreach ($tags as $tagName) {
                $tag = TaskTag::firstOrCreate(
                    ['name' => $tagName],
                    ['created_by' => Auth::id()]
                );
                $tagIds[] = $tag->id;
            }
            if (!empty($tagIds)) {
                $task->tags()->sync($tagIds);
            }

            DB::commit();

            $task->load(['project', 'assignedUsers', 'tags', 'creator']);

            return response()->json([
                'success' => true,
                'data' => $task,
                'message' => 'Tâche créée avec succès'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la tâche: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified task
     */
    public function show(Task $task): JsonResponse
    {
        $task->load([
            'project',
            'assignedUsers',
            'tags',
            'creator',
            'updater',
            'parentTask',
            'subTasks'
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'task' => $task,
                'is_overdue' => $task->isOverdue(),
                'is_urgent' => $task->isUrgent(),
                'progress_percentage' => $task->getProgressPercentage(),
                'progress_color' => $task->getProgressColor(),
                'priority_color' => $task->getPriorityColor(),
                'status_color' => $task->getStatusColor(),
                'comments_count' => $task->comments_count,
                'files_count' => $task->files_count,
                'has_active_difficulties' => $task->has_active_difficulties
            ]
        ]);
    }

    /**
     * Update the specified task
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        // Vérifier les permissions
        if (!$task->canBeEditedBy(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas les permissions pour modifier cette tâche'
            ], 403);
        }

        DB::beginTransaction();

        try {
            $data = $request->validated();
            $data['updated_by'] = Auth::id();

            // Gérer les assignations si modifiées
            if (isset($data['assigned_to'])) {
                $assignedTo = $data['assigned_to'];
                unset($data['assigned_to']);

                // Supprimer les anciennes assignations
                $task->assignments()->delete();

                // Créer les nouvelles
                foreach ($assignedTo as $userId) {
                    TaskAssignment::create([
                        'task_id' => $task->id,
                        'user_id' => $userId,
                        'assigned_at' => now(),
                        'assigned_by' => Auth::id()
                    ]);
                }
            }

            // Gérer les tags si modifiés
            if (isset($data['tags'])) {
                $tags = $data['tags'];
                unset($data['tags']);

                $tagIds = [];
                foreach ($tags as $tagName) {
                    $tag = TaskTag::firstOrCreate(
                        ['name' => $tagName],
                        ['created_by' => Auth::id()]
                    );
                    $tagIds[] = $tag->id;
                }
                $task->tags()->sync($tagIds);
            }

            $task->update($data);

            DB::commit();

            $task->load(['project', 'assignedUsers', 'tags', 'creator', 'updater']);

            return response()->json([
                'success' => true,
                'data' => $task,
                'message' => 'Tâche mise à jour avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la tâche: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified task
     */
    public function destroy(Task $task): JsonResponse
    {
        // Vérifier les permissions (seul le chef de projet peut supprimer)
        $user = Auth::user();
        if ($user->id !== $task->project->project_manager_id && !$user->hasRole(['super_admin', 'admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le chef de projet peut supprimer une tâche'
            ], 403);
        }

        try {
            $task->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tâche supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la tâche: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update task status
     */
    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:a_faire,en_cours,bloque,test,termine',
            'comment' => 'required_if:status,bloque|string'
        ], [
            'comment.required_if' => 'Un commentaire est obligatoire pour le statut "bloqué"'
        ]);

        // Vérifier les permissions
        if (!$task->canBeEditedBy(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas les permissions pour modifier le statut de cette tâche'
            ], 403);
        }

        // Vérifier si le changement de statut est possible
        $errors = $task->canChangeStatusTo($request->status);
        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'errors' => $errors
            ], 422);
        }

        try {
            $oldStatus = $task->status;
            $task->update([
                'status' => $request->status,
                'updated_by' => Auth::id()
            ]);

            // Si terminé, mettre à jour les heures réelles
            if ($request->status === 'termine') {
                $task->updateActualHours();

                // Mettre à jour le pourcentage du projet
                $project = $task->project;
                $totalTasks = $project->tasks()->count();
                $completedTasks = $project->tasks()->where('status', 'termine')->count();
                $progressPercentage = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;
                $project->updateProgress($progressPercentage);
            }

            return response()->json([
                'success' => true,
                'data' => $task->fresh(['assignedUsers', 'project']),
                'message' => 'Statut mis à jour avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available task statuses
     */
    public function getStatuses(): JsonResponse
    {
        $statuses = [
            ['value' => 'a_faire', 'label' => 'À faire', 'color' => 'gray'],
            ['value' => 'en_cours', 'label' => 'En cours', 'color' => 'blue'],
            ['value' => 'bloque', 'label' => 'Bloqué', 'color' => 'red'],
            ['value' => 'test', 'label' => 'Test', 'color' => 'orange'],
            ['value' => 'termine', 'label' => 'Terminé', 'color' => 'green']
        ];

        return response()->json([
            'success' => true,
            'data' => $statuses
        ]);
    }

    /**
     * Modify task assignments
     */
    public function assign(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'assigned_to' => 'required|array|min:1',
            'assigned_to.*' => 'integer|exists:users,id'
        ]);

        if (!$task->canBeEditedBy(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas les permissions pour modifier les assignations'
            ], 403);
        }

        try {
            // Supprimer les anciennes assignations
            $task->assignments()->delete();

            // Créer les nouvelles
            foreach ($request->assigned_to as $userId) {
                TaskAssignment::create([
                    'task_id' => $task->id,
                    'user_id' => $userId,
                    'assigned_at' => now(),
                    'assigned_by' => Auth::id()
                ]);
            }

            $task->load('assignedUsers');

            return response()->json([
                'success' => true,
                'data' => $task->assignedUsers,
                'message' => 'Assignations mises à jour avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des assignations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get project users for assignment
     */
    public function getProjectUsers($projectId): JsonResponse
    {
        try {
            $project = \App\Models\Project::findOrFail($projectId);

            // Membres de l'équipe projet + chef de projet
            $teamMembers = $project->teamMembers()->with('user')->get()->pluck('user');
            $projectManager = $project->projectManager;

            $users = collect([$projectManager])->merge($teamMembers)->unique('id');

            return response()->json([
                'success' => true,
                'data' => $users->values()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des utilisateurs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get task comments
     */
    public function getComments(Task $task): JsonResponse
    {
        $comments = $task->comments()->with('user')->get();

        return response()->json([
            'success' => true,
            'data' => $comments
        ]);
    }

    /**
     * Add comment to task
     */
    public function addComment(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'content' => 'required|string',
            'mentions' => 'nullable|array',
            'mentions.*' => 'integer|exists:users,id'
        ]);

        try {
            $comment = \App\Models\TaskComment::create([
                'task_id' => $task->id,
                'user_id' => Auth::id(),
                'content' => $request->content,
                'mentions' => $request->mentions
            ]);

            $comment->load('user');

            return response()->json([
                'success' => true,
                'data' => $comment,
                'message' => 'Commentaire ajouté avec succès'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout du commentaire: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get task time entries
     */
    public function getTimeEntries(Task $task): JsonResponse
    {
        $timeEntries = $task->timeEntries()->with('user')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $timeEntries
        ]);
    }

    /**
     * Add time entry to task
     */
    public function addTimeEntry(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0.25|max:24',
            'description' => 'nullable|string'
        ]);

        try {
            $timeEntry = \App\Models\TaskTimeEntry::create([
                'task_id' => $task->id,
                'user_id' => Auth::id(),
                'date' => $request->date,
                'hours' => $request->hours,
                'description' => $request->description
            ]);

            // Mettre à jour les heures réelles de la tâche
            $task->updateActualHours();

            $timeEntry->load('user');

            return response()->json([
                'success' => true,
                'data' => $timeEntry,
                'message' => 'Temps saisi avec succès'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la saisie du temps: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get task time summary
     */
    public function getTimeSummary(Task $task): JsonResponse
    {
        $totalHours = $task->timeEntries()->sum('hours');
        $timeByUser = $task->timeEntries()
            ->with('user')
            ->selectRaw('user_id, SUM(hours) as total_hours, COUNT(*) as entries_count')
            ->groupBy('user_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'task' => $task,
                'estimated_hours' => $task->estimated_hours,
                'actual_hours' => $totalHours,
                'variance' => $totalHours - ($task->estimated_hours ?? 0),
                'progress_percentage' => $task->getProgressPercentage(),
                'progress_color' => $task->getProgressColor(),
                'time_by_user' => $timeByUser
            ]
        ]);
    }

    /**
     * Get task history
     */
    public function getHistory(Task $task): JsonResponse
    {
        $history = $task->history()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }
}
