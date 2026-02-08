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
     * @OA\Post(
     * path="/api/v1/call-center/complaints",
     * summary="Créer une réclamation",
     * description="Enregistre une nouvelle réclamation liée à un appel avec calcul automatique du SLA.",
     * tags={"Complaints"},
     * security={{"sanctum":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"call_id", "category", "severity", "description"},
     * @OA\Property(property="call_id", type="integer", example=1),
     * @OA\Property(property="client_id", type="integer", example=5, description="Optionnel, sinon pris de l'appel"),
     * @OA\Property(property="category", type="string", enum={"produit_defectueux", "service_insatisfaisant", "livraison_retard", "facturation_erronee", "comportement_personnel", "autre"}),
     * @OA\Property(property="severity", type="string", enum={"faible", "moyen", "eleve", "critique"}),
     * @OA\Property(property="description", type="string", example="Le client a reçu le mauvais produit et le livreur était impoli.")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Réclamation créée avec succès",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="data", type="object",
     * @OA\Property(property="complaint_id", type="string", example="REC-2026-0001"),
     * @OA\Property(property="sla_deadline", type="string", format="date-time"),
     * @OA\Property(property="status", type="string", example="ouverte")
     * )
     * )
     * )
     * )
     */
    public function store(StoreComplaintRequest $request)
    {
        try {
            $validated = $request->validated();
            
            // 1. Get the Call to link data
            $call = Call::findOrFail($validated['call_id']);
            
            // 2. Determine Client (Use passed ID, or fallback to Call's client)
            $clientId = $validated['client_id'] ?? $call->client_id;

            // 3. Calculate SLA Deadline based on Severity
            $slaDeadline = $this->calculateSla($validated['severity']);

            // 4. Create the Complaint
            $complaint = Complaint::create([
                'complaint_id' => Complaint::generateComplaintId(),
                'call_id' => $call->id,
                'client_id' => $clientId,
                'category' => $validated['category'],
                'severity' => $validated['severity'],
                'description' => $validated['description'],
                'status' => 'ouverte',
                'sla_deadline' => $slaDeadline,
                'created_by' => Auth::id(),
                // Assign to creator initially? Or leave unassigned for a manager to dispatch?
                // Let's leave unassigned for now (US-CC-024 usually handles dispatch)
                'assigned_to' => null 
            ]);

            Log::info('Réclamation créée', [
                'complaint_id' => $complaint->complaint_id,
                'severity' => $validated['severity'],
                'sla' => $slaDeadline
            ]);

            return $this->successResponse(
                $complaint,
                'Réclamation enregistrée avec succès. SLA calculé.',
                201
            );

        } catch (\Exception $e) {
            Log::error('Erreur création réclamation', ['error' => $e->getMessage()]);
            return $this->errorResponse('Erreur serveur lors de la création', 500);
        }
    }

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
     * @OA\Put(
     * path="/api/v1/call-center/complaints/{id}",
     * summary="Traiter une réclamation",
     * description="Met à jour les informations de traitement, le statut et l'assignation.",
     * tags={"Complaints"},
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="status", type="string", enum={"en_analyse", "en_attente_client"}),
     * @OA\Property(property="assigned_to", type="integer", example=1),
     * @OA\Property(property="root_cause", type="string", example="Erreur de picking à l'entrepôt"),
     * @OA\Property(property="actions_taken", type="string", example="Contacté l'entrepôt pour vérification"),
     * @OA\Property(property="proposed_solution", type="string", example="Envoi d'un nouveau produit en express")
     * )
     * ),
     * @OA\Response(response=200, description="Réclamation mise à jour")
     * )
     */
    public function update(\App\Http\Requests\UpdateComplaintRequest $request, $id)
    {
        try {
            $complaint = Complaint::findOrFail($id);
            $validated = $request->validated();
            
            // Capture old state for audit
            $oldStatus = $complaint->status;
            
            // Update fields
            $complaint->fill($validated);
            
            // Logic: If I update it, and it's unassigned, assign it to me?
            // Optional, but good UX. For now, we trust the 'assigned_to' input.
            
            if ($complaint->isDirty()) {
                $complaint->save();
                
                // AUDIT LOG (Essential for US-CC-025 "Historique complet")
                // We use the existing CallStatusHistory or create a new ComplaintHistory table?
                // For simplicity, we'll log to the standard Laravel Log, but in a real app,
                // you might want a 'complaint_history' table similar to 'call_status_history'.
                
                Log::info('Réclamation mise à jour', [
                    'complaint_id' => $complaint->complaint_id,
                    'updated_by' => Auth::id(),
                    'changes' => $complaint->getChanges()
                ]);
            }

            return $this->successResponse(
                $complaint->fresh(['assignedAgent']), 
                'Traitement mis à jour avec succès', 
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur mise à jour réclamation', ['error' => $e->getMessage()]);
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
}