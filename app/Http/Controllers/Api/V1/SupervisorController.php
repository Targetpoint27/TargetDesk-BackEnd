<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\User;
use App\Models\Call;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * @OA\Tag(
 * name="Supervisor",
 * description="Fonctionnalités de supervision et reporting (Epic 8)"
 * )
 */
class SupervisorController extends BaseApiController
{
    /**
     * @OA\Get(
     * path="/api/v1/call-center/supervisor/team-view",
     * summary="Consulter la vue d'équipe",
     * description="Récupère la liste des agents du département avec leur statut et charge de travail en temps réel.",
     * tags={"Supervisor"},
     * security={{"sanctum":{}}},
     * @OA\Response(
     * response=200,
     * description="Vue équipe récupérée",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="data", type="array", @OA\Items(
     * @OA\Property(property="id", type="integer"),
     * @OA\Property(property="name", type="string"),
     * @OA\Property(property="status", type="string", example="active"),
     * @OA\Property(property="calls_today", type="integer"),
     * @OA\Property(property="active_calls", type="integer"),
     * @OA\Property(property="avg_handling_time", type="string"),
     * @OA\Property(property="load_status", type="string", enum={"vert", "orange", "rouge"})
     * ))
     * )
     * )
     * )
     */
    public function teamView(Request $request)
    {
        try {
            $supervisor = Auth::user();
            // Assuming supervisor sees agents in their department
            // If department_id is null, maybe show all (for admin)
            // $query = User::where('id', '!=', $supervisor->id);
            $query = User::query();
            
            if ($supervisor->department_id) {
                $query->where('department_id', $supervisor->department_id);
            }

            $agents = $query->get();

            $teamData = $agents->map(function ($agent) {
                // Real-time metrics
                $todayCalls = Call::where('assigned_to', $agent->id)
                                  ->whereDate('updated_at', today())
                                  ->count();
                                  
                $activeCalls = Call::where('assigned_to', $agent->id)
                                   ->where('status', 'en_cours')
                                   ->count();

                // Calculate Load Status (Green/Orange/Red)
                // Logic: > 3 active calls = Red, > 1 = Orange, else Green
                $loadStatus = 'vert';
                if ($activeCalls >= 3) $loadStatus = 'rouge';
                elseif ($activeCalls >= 1) $loadStatus = 'orange';

                // Calculate Avg Handling Time (AHT)
                $avgTime = Call::where('assigned_to', $agent->id)
                               ->whereNotNull('treatment_time_seconds')
                               ->avg('treatment_time_seconds');

                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'status' => $agent->status ?? 'unknown', // User status (online/offline)
                    'calls_today' => $todayCalls,
                    'active_calls' => $activeCalls,
                    'avg_handling_time' => $avgTime ? gmdate("H:i:s", $avgTime) : "00:00:00",
                    'load_status' => $loadStatus,
                    'last_activity' => $agent->last_login // or fetch last call update
                ];
            });

            return $this->successResponse($teamData, 'Vue équipe récupérée', 200);

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur récupération vue équipe', 500);
        }
    }

    /**
     * @OA\Get(
     * path="/api/v1/call-center/supervisor/team-stats",
     * summary="Consulter les statistiques d'équipe (US-CC-044)",
     * description="Récupère les KPIs globaux de l'équipe (Volume, SLA, Satisfaction).",
     * tags={"Supervisor"},
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="period", in="query", @OA\Schema(type="string", enum={"today", "week", "month"})),
     * @OA\Response(response=200, description="Statistiques récupérées")
     * )
     */
    public function teamStats(Request $request)
    {
        try {
            $period = $request->input('period', 'month');
            $deptId = Auth::user()->department_id;

            // Date Filters
            $query = Call::query();
            if ($deptId) $query->where('department_id', $deptId);

            $now = now();
            if ($period === 'today') $query->whereDate('created_at', $now);
            elseif ($period === 'week') $query->whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()]);
            elseif ($period === 'month') $query->whereMonth('created_at', $now->month);

            $calls = $query->get();

            // KPIs
            $total = $calls->count();
            $missed = $calls->whereIn('status', ['a_rappeler'])->count(); // Simplified logic for missed
            $resolved = $calls->where('status', 'resolue')->count();
            
            // SLA Compliance (Example logic)
            // Ideally we check if resolved_at < sla_deadline from complaints
            $complaints = Complaint::whereIn('call_id', $calls->pluck('id'))->get();
            $slaBreaches = $complaints->filter(function($c) {
                return $c->sla_deadline && $c->sla_deadline->isPast() && $c->status !== 'resolue';
            })->count();

            // Graph Data: Volume by Date
            $volumeGraph = $calls->groupBy(function($item) {
                return $item->created_at->format('Y-m-d');
            })->map->count();

            return $this->successResponse([
                'kpis' => [
                    'total_calls' => $total,
                    'missed_calls' => $missed,
                    'response_rate' => $total > 0 ? round((($total - $missed) / $total) * 100) . '%' : '0%',
                    'avg_wait_time' => '00:00:30', // Placeholder or calc from DB
                    'sla_compliance' => $complaints->count() > 0 ? round((1 - ($slaBreaches / $complaints->count())) * 100) . '%' : '100%'
                ],
                'graphs' => [
                    'volume_evolution' => $volumeGraph,
                    'status_distribution' => $calls->groupBy('status')->map->count()
                ]
            ], 'Statistiques équipe récupérées', 200);

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur statistiques', 500);
        }
    }

    /**
     * @OA\Put(
     * path="/api/v1/call-center/supervisor/calls/{id}/reassign",
     * summary="Réassigner un appel (US-CC-045)",
     * description="Transfère un appel d'un agent à un autre.",
     * tags={"Supervisor"},
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"new_agent_id"},
     * @OA\Property(property="new_agent_id", type="integer", example=2),
     * @OA\Property(property="reason", type="string", example="Surcharge de l'agent initial")
     * )
     * ),
     * @OA\Response(response=200, description="Appel réassigné")
     * )
     */
    public function reassign(\App\Http\Requests\ReassignCallRequest $request, $id)
    {
        try {
            $call = Call::findOrFail($id);
            $validated = $request->validated();
            
            $oldAgentId = $call->assigned_to;
            $newAgentId = $validated['new_agent_id'];
            
            // Update Assignment
            $call->assigned_to = $newAgentId;
            $call->save();

            // Log History
            $oldAgentName = $oldAgentId ? User::find($oldAgentId)->name : 'Non assigné';
            $newAgentName = User::find($newAgentId)->name;
            
            \App\Models\CallStatusHistory::create([
                'call_id' => $call->id,
                'old_status' => $call->status,
                'new_status' => $call->status, // Status doesn't change, just owner
                'comment' => "Réassignation par superviseur: $oldAgentName -> $newAgentName. Motif: " . ($validated['reason'] ?? 'Aucun'),
                'changed_by' => Auth::id(),
                'created_at' => now()
            ]);

            return $this->successResponse(
                $call->fresh(['assignedAgent']), 
                'Appel réassigné avec succès', 
                200
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la réassignation', 500);
        }
    }

    /**
     * @OA\Get(
     * path="/api/v1/call-center/supervisor/queue",
     * summary="Consulter la file d'appels à traiter (US-CC-046)",
     * description="Affiche tous les appels en attente du département, triés par urgence.",
     * tags={"Supervisor"},
     * security={{"sanctum":{}}},
     * @OA\Response(response=200, description="File d'attente récupérée")
     * )
     */
    public function queueView(Request $request)
    {
        try {
            $deptId = Auth::user()->department_id;
            
            // Fetch calls that need attention
            $calls = Call::where('department_id', $deptId)
                ->whereIn('status', ['en_attente', 'a_rappeler']) // Pending statuses
                ->with(['assignedAgent'])
                ->orderByRaw("FIELD(urgency, 'urgent', 'normal', 'faible')") // Sort by Urgency first
                ->orderBy('created_at', 'asc') // Then oldest first
                ->get();

            return $this->successResponse($calls, 'File d\'appels récupérée', 200);

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur récupération file d\'attente', 500);
        }
    }

    /**
     * @OA\Get(
     * path="/api/v1/call-center/supervisor/complaints",
     * summary="Consulter les réclamations de l'équipe (US-CC-047)",
     * description="Affiche toutes les réclamations du département avec indicateurs SLA.",
     * tags={"Supervisor"},
     * security={{"sanctum":{}}},
     * @OA\Response(response=200, description="Réclamations équipe récupérées")
     * )
     */
    public function complaintsView(Request $request)
    {
        try {
            $deptId = Auth::user()->department_id;

            // Find complaints linked to calls in my department
            $complaints = Complaint::whereHas('call', function ($query) use ($deptId) {
                    $query->where('department_id', $deptId);
                })
                ->with(['call', 'assignedAgent'])
                ->orderByRaw("FIELD(severity, 'critique', 'eleve', 'moyen', 'faible')")
                ->get();

            // Add SLA status for frontend highlighting
            $formatted = $complaints->map(function($c) {
                $c->sla_status = ($c->sla_deadline < now() && $c->status !== 'resolue') ? 'overdue' : 'ok';
                return $c;
            });

            return $this->successResponse($formatted, 'Réclamations équipe récupérées', 200);

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur récupération réclamations', 500);
        }
    }
}