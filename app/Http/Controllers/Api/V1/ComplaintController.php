<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreComplaintRequest;
use App\Models\Complaint;
use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 * name="Complaints",
 * description="Gestion des réclamations"
 * )
 */
class ComplaintController extends BaseApiController
{

    /**
     * Logic to determine SLA Date based on Severity
     */
    private function calculateSla($severity)
    {
        $now = now();
        
        switch ($severity) {
            case 'critique':
                return $now->addHours(4);  // Urgent: 4 hours
            case 'eleve':
                return $now->addHours(24); // High: 24 hours
            case 'moyen':
                return $now->addHours(48); // Medium: 2 days
            case 'faible':
            default:
                return $now->addDays(5);   // Low: 1 week (business week approx)
        }
    }

    /**
     * @OA\Get(
     * path="/api/v1/call-center/complaints",
     * summary="Lister les réclamations",
     * description="Récupère la liste des réclamations avec filtres (statut, gravité, SLA dépassé).",
     * tags={"Complaints"},
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="filter[status]", in="query", @OA\Schema(type="string")),
     * @OA\Parameter(name="filter[severity]", in="query", @OA\Schema(type="string")),
     * @OA\Parameter(name="filter[is_overdue]", in="query", @OA\Schema(type="boolean"), description="true pour voir uniquement les retards"),
     * @OA\Response(
     * response=200,
     * description="Liste récupérée",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="data", type="object",
     * @OA\Property(property="total", type="integer"),
     * @OA\Property(property="overdue_count", type="integer"),
     * @OA\Property(property="complaints", type="array", @OA\Items(type="object"))
     * )
     * )
     * )
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = Complaint::with(['client', 'assignedAgent', 'creator']);

            // Filters
            if ($request->has('filter.status')) {
                $query->where('status', $request->input('filter.status'));
            }
            if ($request->has('filter.severity')) {
                $query->where('severity', $request->input('filter.severity'));
            }
            if ($request->has('filter.assigned_to')) {
                $assigned = $request->input('filter.assigned_to');
                if ($assigned === 'me') {
                    $query->where('assigned_to', Auth::id());
                } elseif ($assigned === 'unassigned') {
                    $query->whereNull('assigned_to');
                }
            }

            // SLA Overdue Filter
            if ($request->boolean('filter.is_overdue')) {
                $query->where('sla_deadline', '<', now())
                      ->whereNotIn('status', ['resolue', 'cloture']);
            }

            // Sorting: Severity first (Critique > Eleve > ...), then Deadline (Urgent first)
            $query->orderByRaw("FIELD(severity, 'critique', 'eleve', 'moyen', 'faible')")
                  ->orderBy('sla_deadline', 'asc');

            $complaints = $query->get();

            // Computed Stats for Dashboard
            $overdueCount = Complaint::where('sla_deadline', '<', now())
                                   ->whereNotIn('status', ['resolue', 'cloture'])
                                   ->count();

            // Add "Meta" data for Frontend (Colors & Time Remaining)
            $complaintsWithMeta = $complaints->map(function ($complaint) {
                $data = $complaint->toArray();
                
                $deadline = $complaint->sla_deadline;
                $now = now();

                // Logic: Is it Overdue?
                $isClosed = in_array($complaint->status, ['resolue', 'cloture']);
                $data['is_overdue'] = !$isClosed && $deadline->isPast();
                
                // Logic: Time Remaining text
                if ($isClosed) {
                    $data['sla_text'] = "Traité";
                } elseif ($deadline->isPast()) {
                    $data['sla_text'] = "En retard de " . $deadline->diffForHumans($now, true);
                } else {
                    $data['sla_text'] = "Reste " . $now->diffForHumans($deadline, true);
                }

                return $data;
            });

            return $this->successResponse([
                'total' => $complaints->count(),
                'overdue_count' => $overdueCount,
                'complaints' => $complaintsWithMeta
            ], 'Liste des réclamations récupérée', 200);

        } catch (\Exception $e) {
            Log::error('Erreur liste réclamations', ['error' => $e->getMessage()]);
            return $this->errorResponse('Erreur serveur', 500);
        }
    }

    /**
     * @OA\Post(
     * path="/api/v1/call-center/complaints/{id}/resolve",
     * summary="Résoudre une réclamation",
     * description="Marque la réclamation comme résolue avec le résumé de la solution et la satisfaction client.",
     * tags={"Complaints"},
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"resolution_summary", "client_satisfaction"},
     * @OA\Property(property="resolution_summary", type="string", example="Produit remplacé et client remboursé des frais de port."),
     * @OA\Property(property="client_satisfaction", type="string", enum={"satisfait", "partiellement_satisfait", "non_satisfait"}),
     * @OA\Property(property="compensation_details", type="string", example="Bon d'achat de 10€ offert")
     * )
     * ),
     * @OA\Response(response=200, description="Réclamation résolue")
     * )
     */
    public function resolve(\App\Http\Requests\ResolveComplaintRequest $request, $id)
    {
        try {
            $complaint = Complaint::findOrFail($id);

            // Business Rule: Can only resolve if it's not already closed
            if ($complaint->status === 'cloture') {
                return $this->errorResponse('Cette réclamation est déjà clôturée.', 400);
            }

            $validated = $request->validated();

            // Update Complaint
            $complaint->status = 'resolue'; // US Requirement
            $complaint->resolution_summary = $validated['resolution_summary'];
            $complaint->client_satisfaction = $validated['client_satisfaction'];
            $complaint->compensation_details = $validated['compensation_details'] ?? $complaint->compensation_details;
            
            // Set Timestamps
            $complaint->resolved_at = now();
            $complaint->resolved_by = Auth::id();

            $complaint->save();

            Log::info('Réclamation résolue', [
                'complaint_id' => $complaint->complaint_id,
                'resolved_by' => Auth::id(),
                'satisfaction' => $validated['client_satisfaction']
            ]);

            return $this->successResponse(
                $complaint->fresh(), 
                'Réclamation résolue avec succès', 
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur résolution réclamation', ['error' => $e->getMessage()]);
            return $this->errorResponse('Erreur serveur', 500);
        }
    }

    /**
     * @OA\Post(
     * path="/api/v1/call-center/complaints/{id}/close",
     * summary="Clôturer une réclamation",
     * description="Validation finale par un superviseur. La réclamation doit être 'resolue' avant d'être clôturée.",
     * tags={"Complaints"},
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=false,
     * @OA\JsonContent(
     * @OA\Property(property="closing_comment", type="string", example="Dossier validé. Client satisfait.")
     * )
     * ),
     * @OA\Response(response=200, description="Réclamation clôturée")
     * )
     */
    public function close(\App\Http\Requests\CloseComplaintRequest $request, $id)
    {
        try {
            $complaint = Complaint::findOrFail($id);

            // Business Rule: Must be 'resolue' first
            if ($complaint->status !== 'resolue') {
                return $this->errorResponse('La réclamation doit être résolue avant d\'être clôturée.', 400);
            }

            $validated = $request->validated();

            // Final Closure
            $complaint->status = 'cloture';
            $complaint->closing_comment = $validated['closing_comment'] ?? 'Clôturé par le superviseur.';
            $complaint->closed_at = now();
            $complaint->closed_by = Auth::id();

            $complaint->save();

            Log::info('Réclamation clôturée', [
                'complaint_id' => $complaint->complaint_id,
                'closed_by' => Auth::id()
            ]);

            return $this->successResponse(
                $complaint->fresh(), 
                'Réclamation clôturée définitivement', 
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur clôture réclamation', ['error' => $e->getMessage()]);
            return $this->errorResponse('Erreur serveur', 500);
        }
    }

    /**
     * @OA\Get(
     * path="/api/v1/call-center/complaints/{id}",
     * summary="Détails d'une réclamation",
     * description="Récupère les informations complètes d'une réclamation spécifique par son ID.",
     * tags={"Complaints"},
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID de la réclamation",
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Détails récupérés avec succès",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="data", type="object"),
     * @OA\Property(property="message", type="string", example="Détails de la réclamation récupérés")
     * )
     * ),
     * @OA\Response(response=404, description="Réclamation introuvable")
     * )
     */
    public function show($id)
    {
        try {
            // We load the client, assigned agent, and the creator to fill the sidebar context
            $complaint = Complaint::with(['client', 'assignedAgent', 'creator'])->findOrFail($id);

            // Logic for Meta data (similar to index) so the details page has status labels
            $data = $complaint->toArray();
            $deadline = $complaint->sla_deadline;
            $isClosed = in_array($complaint->status, ['resolue', 'cloture']);
            
            $data['is_overdue'] = !$isClosed && $deadline->isPast();
            $data['status_label'] = str_replace('_', ' ', ucfirst($complaint->status));
            $data['category_label'] = str_replace('_', ' ', ucfirst($complaint->category));

            return $this->successResponse($data, 'Détails de la réclamation récupérés');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Réclamation introuvable', 404);
        } catch (\Exception $e) {
            Log::error('Erreur show réclamation', ['error' => $e->getMessage()]);
            return $this->errorResponse('Erreur serveur', 500);
        }
    }

    /**
     * @OA\Put(
     * path="/api/v1/call-center/complaints/{id}",
     * summary="Mettre à jour l'analyse d'une réclamation",
     * description="Enregistre la cause racine, les actions menées et la solution proposée.",
     * tags={"Complaints"},
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="root_cause", type="string"),
     * @OA\Property(property="actions_taken", type="string"),
     * @OA\Property(property="proposed_solution", type="string")
     * )
     * ),
     * @OA\Response(response=200, description="Analyse enregistrée")
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $complaint = Complaint::findOrFail($id);

            // Update investigation data
            $complaint->root_cause = $request->input('root_cause');
            $complaint->actions_taken = $request->input('actions_taken');
            $complaint->proposed_solution = $request->input('proposed_solution');

            // Business Rule: Automatically move to 'en_analyse' status
            if ($complaint->status === 'ouverte') {
                $complaint->status = 'en_analyse';
            }

            $complaint->save();

            return $this->successResponse($complaint, 'Analyse mise à jour avec succès');

        } catch (\Exception $e) {
            Log::error('Erreur update réclamation', ['error' => $e->getMessage()]);
            return $this->errorResponse('Erreur serveur', 500);
        }
    }

    /**
     * @OA\Post(
     * path="/api/v1/call-center/complaints",
     * summary="Créer une réclamation",
     * description="Enregistre une nouvelle réclamation liée à un appel.",
     * tags={"Complaints"},
     * security={{"sanctum":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(ref="#/components/schemas/StoreComplaintRequest")
     * ),
     * @OA\Response(response=201, description="Réclamation créée")
     * )
     */
    public function store(StoreComplaintRequest $request)
    {
        try {
            $validated = $request->validated();

            // Create the record
            $complaint = new Complaint();
            
            // Generate a unique ID (e.g., REC-2026-0009)
            $count = Complaint::count() + 1;
            $complaint->complaint_id = 'REC-' . now()->format('Y') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            
            $complaint->call_id = $validated['call_id'];
            $complaint->client_id = $validated['client_id'] ?? null;
            $complaint->category = $validated['category'];
            $complaint->severity = $validated['severity'];
            $complaint->description = $validated['description'];
            $complaint->status = 'ouverte';
            
            // Set SLA deadline using your existing private helper
            $complaint->sla_deadline = $this->calculateSla($validated['severity']);
            
            $complaint->created_by = Auth::id();
            $complaint->save();

            return $this->successResponse($complaint, 'Réclamation enregistrée avec succès', 201);

        } catch (\Exception $e) {
            Log::error('Erreur création réclamation', ['error' => $e->getMessage()]);
            return $this->errorResponse('Erreur lors de la création', 500);
        }
    }
}