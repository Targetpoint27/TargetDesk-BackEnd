<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreProjectRequest;
use App\Models\Project;
use App\Models\ProjectTeam;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Project::with(['projectManager', 'client', 'teamMembers.user']);

        if ($request->has('my_projects')) {
            $query->myProjects(Auth::id());
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('active_only')) {
            $query->active();
        }

        if ($request->has('department')) {
            $query->where('department', $request->department);
        }

        $projects = $query->orderBy('created_at', 'desc')
                         ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $projects->items(),
            'meta' => [
                'current_page' => $projects->currentPage(),
                'total' => $projects->total(),
                'per_page' => $projects->perPage(),
                'last_page' => $projects->lastPage()
            ]
        ]);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            if (!isset($data['code']) || empty($data['code'])) {
                $data['code'] = Project::generateUniqueCode();
            }

            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $project = Project::create($data);

            ProjectTeam::create([
                'project_id' => $project->id,
                'user_id' => $project->project_manager_id,
                'role' => 'autre',
                'is_active' => true,
                'added_by' => Auth::id(),
                'joined_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Projet créé avec succès',
                'data' => $project->load(['projectManager', 'client', 'teamMembers.user'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du projet',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Project $project): JsonResponse
    {
        $project->load([
            'projectManager',
            'client',
            'teamMembers.user',
            'histories' => function($q) {
                $q->latest()->limit(10);
            }
        ]);

        return response()->json([
            'success' => true,
            'data' => $project
        ]);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        if (!$project->canBeEditedBy(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas l\'autorisation de modifier ce projet'
            ], 403);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'objectives' => 'nullable|string|max:5000',
            'estimated_budget' => 'nullable|numeric|min:0',
            'planned_end_date' => 'sometimes|required|date|after:start_date',
            'project_manager_id' => 'sometimes|required|exists:users,id',
            'department' => 'sometimes|required|string|max:255',
            'client_type' => 'nullable|in:interne,externe',
            'client_id' => 'nullable|required_if:client_type,interne|exists:clients,id',
            'external_client_info' => 'nullable|required_if:client_type,externe|array',
            'risk_indicator' => 'nullable|in:low,medium,high'
        ]);

        $validatedData['updated_by'] = Auth::id();

        $project->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Projet mis à jour avec succès',
            'data' => $project->load(['projectManager', 'client', 'teamMembers.user'])
        ]);
    }

    public function destroy(Project $project): JsonResponse
    {
        if (!$project->canBeEditedBy(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas l\'autorisation de supprimer ce projet'
            ], 403);
        }

        if ($project->status !== Project::STATUS_ANNULE) {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les projets annulés peuvent être supprimés'
            ], 400);
        }

        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Projet supprimé avec succès'
        ]);
    }

    public function updateStatus(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:en_cours,en_attente,en_danger,termine,annule',
            'comment' => 'nullable|string|max:1000'
        ]);

        if (!$project->canBeEditedBy(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas l\'autorisation de modifier le statut'
            ], 403);
        }

        if ($request->status === Project::STATUS_TERMINE && !$project->canBeCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Le projet ne peut pas être terminé (tâches en cours)'
            ], 400);
        }

        $oldStatus = $project->status;
        $project->update([
            'status' => $request->status,
            'updated_by' => Auth::id(),
            'actual_end_date' => $request->status === Project::STATUS_TERMINE ? now()->toDateString() : null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour avec succès',
            'data' => $project
        ]);
    }

    public function addTeamMember(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:developeur,designer,testeur,analyste,autre',
            'hourly_rate' => 'nullable|numeric|min:0'
        ]);

        if (!$project->canBeEditedBy(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas l\'autorisation de gérer l\'équipe'
            ], 403);
        }

        $existingMember = ProjectTeam::where('project_id', $project->id)
                                   ->where('user_id', $request->user_id)
                                   ->where('is_active', true)
                                   ->first();

        if ($existingMember) {
            return response()->json([
                'success' => false,
                'message' => 'Cet utilisateur est déjà membre de l\'équipe'
            ], 400);
        }

        $teamMember = ProjectTeam::create([
            'project_id' => $project->id,
            'user_id' => $request->user_id,
            'role' => $request->role,
            'hourly_rate' => $request->hourly_rate,
            'is_active' => true,
            'added_by' => Auth::id(),
            'joined_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Membre ajouté à l\'équipe avec succès',
            'data' => $teamMember->load('user')
        ], 201);
    }

    public function removeTeamMember(Project $project, ProjectTeam $teamMember): JsonResponse
    {
        if ($teamMember->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Membre non trouvé dans ce projet'
            ], 404);
        }

        if (!$project->canBeEditedBy(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas l\'autorisation de gérer l\'équipe'
            ], 403);
        }

        if ($teamMember->user_id === $project->project_manager_id) {
            return response()->json([
                'success' => false,
                'message' => 'Le chef de projet ne peut pas être retiré de l\'équipe'
            ], 400);
        }

        $teamMember->removeMember();

        return response()->json([
            'success' => true,
            'message' => 'Membre retiré de l\'équipe avec succès'
        ]);
    }

    public function getTeam(Project $project): JsonResponse
    {
        $team = $project->teamMembers()->with('user')->get();

        return response()->json([
            'success' => true,
            'data' => $team
        ]);
    }

    public function updateProgress(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'progress_percentage' => 'required|integer|min:0|max:100'
        ]);

        if (!$project->canBeEditedBy(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas l\'autorisation de modifier le projet'
            ], 403);
        }

        $project->updateProgress($request->progress_percentage);

        return response()->json([
            'success' => true,
            'message' => 'Progression mise à jour avec succès',
            'data' => [
                'progress_percentage' => $project->fresh()->progress_percentage,
                'progress_color' => $project->fresh()->progress_color
            ]
        ]);
    }

    public function getProgress(Project $project): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'progress_percentage' => $project->progress_percentage,
                'progress_color' => $project->progress_color,
                'calculated_progress' => $project->calculateProgress()
            ]
        ]);
    }

    public function getByDepartment(Request $request, string $department): JsonResponse
    {
        $projects = Project::where('department', $department)
                          ->with(['projectManager', 'client'])
                          ->when($request->has('status'), function($q) use ($request) {
                              $q->where('status', $request->status);
                          })
                          ->orderBy('created_at', 'desc')
                          ->get();

        return response()->json([
            'success' => true,
            'data' => $projects
        ]);
    }

    public function getByManager(Request $request, User $manager): JsonResponse
    {
        $projects = Project::where('project_manager_id', $manager->id)
                          ->with(['client', 'teamMembers.user'])
                          ->when($request->has('status'), function($q) use ($request) {
                              $q->where('status', $request->status);
                          })
                          ->orderBy('created_at', 'desc')
                          ->get();

        return response()->json([
            'success' => true,
            'data' => $projects
        ]);
    }

    public function getStatistics(): JsonResponse
    {
        $stats = [
            'total_projects' => Project::count(),
            'active_projects' => Project::active()->count(),
            'completed_projects' => Project::where('status', Project::STATUS_TERMINE)->count(),
            'cancelled_projects' => Project::where('status', Project::STATUS_ANNULE)->count(),
            'projects_by_status' => Project::select('status', DB::raw('count(*) as count'))
                                          ->groupBy('status')
                                          ->pluck('count', 'status'),
            'projects_by_department' => Project::select('department', DB::raw('count(*) as count'))
                                              ->groupBy('department')
                                              ->pluck('count', 'department'),
            'average_progress' => Project::active()->avg('progress_percentage'),
            'at_risk_projects' => Project::where('risk_indicator', 'high')->active()->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function duplicate(Project $project): JsonResponse
    {
        if (!$project->canBeEditedBy(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas l\'autorisation de dupliquer ce projet'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $newProjectData = $project->toArray();
            unset($newProjectData['id'], $newProjectData['created_at'], $newProjectData['updated_at']);

            $newProjectData['name'] = $project->name . ' (Copie)';
            $newProjectData['code'] = Project::generateUniqueCode();
            $newProjectData['status'] = Project::STATUS_EN_ATTENTE;
            $newProjectData['progress_percentage'] = 0;
            $newProjectData['actual_budget'] = null;
            $newProjectData['actual_end_date'] = null;
            $newProjectData['created_by'] = Auth::id();
            $newProjectData['updated_by'] = Auth::id();

            $newProject = Project::create($newProjectData);

            foreach ($project->teamMembers as $member) {
                ProjectTeam::create([
                    'project_id' => $newProject->id,
                    'user_id' => $member->user_id,
                    'role' => $member->role,
                    'hourly_rate' => $member->hourly_rate,
                    'is_active' => true,
                    'added_by' => Auth::id(),
                    'joined_at' => now()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Projet dupliqué avec succès',
                'data' => $newProject->load(['projectManager', 'client', 'teamMembers.user'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la duplication',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
