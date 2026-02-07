<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreCallRequest;
use App\Http\Requests\UpdateCallRequest;
use App\Http\Requests\ChangeCallStatusRequest;
use App\Models\CallStatusHistory;
use App\Http\Requests\CloseCallRequest;
use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Calls",
 *     description="Gestion des appels call center"
 * )
 */
class CallController extends BaseApiController
{
    /**
     * @OA\Post(
     *     path="/api/v1/call-center/calls",
     *     summary="Créer un appel (entrant ou sortant)",
     *     description="Enregistre un nouvel appel entrant ou sortant avec génération automatique d'un identifiant unique",
     *     tags={"Calls"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "phone_number", "department_id", "object", "summary"},
     *             @OA\Property(property="type", type="string", enum={"entrant", "sortant"}, example="entrant", description="Type d'appel"),
     *             @OA\Property(property="phone_number", type="string", example="0612345678", description="Numéro de téléphone"),
     *             @OA\Property(property="caller_name", type="string", example="Jean Dupont", description="Nom de l'appelant (optionnel)"),
     *             @OA\Property(property="department_id", type="integer", example=1, description="ID du département cible"),
     *             @OA\Property(property="object", type="string", example="Problème technique", description="Objet/motif de l'appel"),
     *             @OA\Property(property="summary", type="string", example="Le client rencontre une erreur...", description="Résumé détaillé de l'appel"),
     *             @OA\Property(property="urgency", type="string", enum={"normal", "urgent", "critique"}, example="urgent", description="Niveau d'urgence (optionnel, défaut: normal)"),
     *             @OA\Property(property="client_id", type="integer", example=1, description="ID du client lié (optionnel)"),
     *             @OA\Property(property="contact_id", type="integer", example=1, description="ID du contact lié (optionnel)"),
     *             @OA\Property(property="related_to_type", type="string", enum={"prospect", "client", "project", "command"}, example="client", description="Type d'entité liée (optionnel)"),
     *             @OA\Property(property="related_to_id", type="integer", example=1, description="ID de l'entité liée (optionnel)"),
     *             @OA\Property(property="outbound_reason", type="string", enum={"rappel_client", "prospection", "suivi_commande", "enquete_satisfaction", "relance_paiement"}, example="rappel_client", description="Motif de l'appel sortant (requis si type=sortant)"),
     *             @OA\Property(property="call_result", type="string", enum={"contacte", "messagerie", "pas_de_reponse", "numero_errone", "refuse"}, example="contacte", description="Résultat de l'appel sortant (optionnel)"),
     *             @OA\Property(property="call_duration_seconds", type="integer", example=180, description="Durée de l'appel en secondes (optionnel)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Appel créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Appel créé avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="call_id", type="string", example="CALL-2026-0001"),
     *                 @OA\Property(property="type", type="string", example="entrant"),
     *                 @OA\Property(property="phone_number", type="string", example="0612345678"),
     *                 @OA\Property(property="caller_name", type="string", example="Jean Dupont"),
     *                 @OA\Property(property="department_id", type="integer", example=1),
     *                 @OA\Property(property="object", type="string", example="Problème technique"),
     *                 @OA\Property(property="summary", type="string", example="Le client rencontre une erreur..."),
     *                 @OA\Property(property="urgency", type="string", example="urgent"),
     *                 @OA\Property(property="status", type="string", example="a_traiter"),
     *                 @OA\Property(property="assigned_to", type="integer", example=1),
     *                 @OA\Property(property="created_by", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2026-02-07T13:42:43.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2026-02-07T13:42:43.000000Z"),
     *                 @OA\Property(
     *                     property="department",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Support Technique"),
     *                     @OA\Property(property="code", type="string", example="SUP")
     *                 ),
     *                 @OA\Property(
     *                     property="assigned_agent",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Chi Samuel Apeng"),
     *                     @OA\Property(property="email", type="string", example="samuel@targetpoint.fr")
     *                 ),
     *                 @OA\Property(
     *                     property="creator",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Chi Samuel Apeng"),
     *                     @OA\Property(property="email", type="string", example="samuel@targetpoint.fr")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation errors"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="phone_number", type="array", @OA\Items(type="string", example="Le numéro de téléphone est obligatoire")),
     *                 @OA\Property(property="department_id", type="array", @OA\Items(type="string", example="Le département cible est obligatoire"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur lors de la création de l'appel")
     *         )
     *     )
     * )
     */
    public function store(StoreCallRequest $request)
    {
        try {
            $callData = $request->validated();
            
            $callData['call_id'] = Call::generateCallId();
            $callData['created_by'] = Auth::id();
            $callData['assigned_to'] = Auth::id();
            $callData['status'] = 'a_traiter';
            
            if (!isset($callData['urgency'])) {
                $callData['urgency'] = 'normal';
            }

            $call = Call::create($callData);

            $call->load(['department', 'client', 'contact', 'assignedAgent', 'creator']);

            Log::info('Appel créé avec succès', [
                'call_id' => $call->call_id,
                'type' => $call->type,
                'created_by' => Auth::id(),
                'user_name' => Auth::user()->name,
            ]);

            return $this->successResponse(
                $call,
                'Appel créé avec succès',
                201
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de l\'appel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la création de l\'appel',
                500
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/call-center/calls/{id}",
     *     summary="Consulter les détails d'un appel",
     *     description="Récupère toutes les informations détaillées d'un appel spécifique",
     *     tags={"Calls"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'appel",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de l'appel récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Appel trouvé"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="call_id", type="string", example="CALL-2026-0001"),
     *                 @OA\Property(property="type", type="string", example="entrant"),
     *                 @OA\Property(property="phone_number", type="string", example="0612345678"),
     *                 @OA\Property(property="status", type="string", example="a_traiter"),
     *                 @OA\Property(property="urgency", type="string", example="urgent"),
     *                 @OA\Property(property="time_elapsed", type="string", example="Il y a 2 heures"),
     *                 @OA\Property(
     *                     property="department",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string")
     *                 ),
     *                 @OA\Property(
     *                     property="notes",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="note", type="string"),
     *                         @OA\Property(property="is_important", type="boolean"),
     *                         @OA\Property(property="created_at", type="string")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Appel non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Appel non trouvé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $call = Call::with([
                'department',
                'client',
                'contact',
                'assignedAgent',
                'creator',
                'closer',
                'notes.creator'
            ])->find($id);

            if (!$call) {
                return $this->errorResponse('Appel non trouvé', 404);
            }

            $callData = $call->toArray();
            
            $callData['time_elapsed'] = $this->calculateTimeElapsed($call->created_at);

            Log::info('Consultation des détails d\'un appel', [
                'call_id' => $call->call_id,
                'viewed_by' => Auth::id(),
                'user_name' => Auth::user()->name,
            ]);

            return $this->successResponse(
                $callData,
                'Appel trouvé',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de l\'appel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la récupération de l\'appel',
                500
            );
        }
    }

    private function calculateTimeElapsed($createdAt)
    {
        $now = now();
        $diff = $createdAt->diff($now);

        if ($diff->d > 0) {
            return $diff->d === 1 ? 'Il y a 1 jour' : "Il y a {$diff->d} jours";
        } elseif ($diff->h > 0) {
            return $diff->h === 1 ? 'Il y a 1 heure' : "Il y a {$diff->h} heures";
        } elseif ($diff->i > 0) {
            return $diff->i === 1 ? 'Il y a 1 minute' : "Il y a {$diff->i} minutes";
        } else {
            return "Il y a quelques secondes";
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/call-center/calls/{id}",
     *     summary="Modifier un appel",
     *     description="Met à jour les informations d'un appel existant",
     *     tags={"Calls"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'appel",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="phone_number", type="string", example="0623456789"),
     *             @OA\Property(property="caller_name", type="string", example="Jean Dupont Modifié"),
     *             @OA\Property(property="object", type="string", example="Problème résolu"),
     *             @OA\Property(property="summary", type="string", example="Le problème a été corrigé"),
     *             @OA\Property(property="urgency", type="string", enum={"normal", "urgent", "critique"}, example="normal"),
     *             @OA\Property(property="department_id", type="integer", example=2),
     *             @OA\Property(property="assigned_to", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Appel mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Appel mis à jour avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Appel non trouvé"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */
    public function update(UpdateCallRequest $request, $id)
    {
        try {
            $call = Call::find($id);

            if (!$call) {
                return $this->errorResponse('Appel non trouvé', 404);
            }

            $oldData = $call->toArray();
            
            $call->update($request->validated());

            $call->load(['department', 'client', 'contact', 'assignedAgent', 'creator', 'closer']);

            $changes = array_diff_assoc($request->validated(), $oldData);

            Log::info('Appel modifié avec succès', [
                'call_id' => $call->call_id,
                'modified_by' => Auth::id(),
                'user_name' => Auth::user()->name,
                'changes' => $changes,
            ]);

            return $this->successResponse(
                $call,
                'Appel mis à jour avec succès',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la modification de l\'appel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la modification de l\'appel',
                500
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/call-center/calls/{id}/status",
     *     summary="Changer le statut d'un appel",
     *     description="Met à jour le statut d'un appel et enregistre l'historique",
     *     tags={"Calls"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'appel",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"a_traiter", "en_cours", "en_attente", "a_rappeler", "resolu", "cloture", "annule"},
     *                 example="en_cours"
     *             ),
     *             @OA\Property(property="comment", type="string", example="Agent commence le traitement", description="Commentaire optionnel")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statut changé avec succès"
     *     ),
     *     @OA\Response(response=404, description="Appel non trouvé"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function changeStatus(ChangeCallStatusRequest $request, $id)
    {
        try {
            $call = Call::find($id);

            if (!$call) {
                return $this->errorResponse('Appel non trouvé', 404);
            }

            $oldStatus = $call->status;
            $newStatus = $request->status;

            if ($oldStatus === $newStatus) {
                return $this->errorResponse('Le statut est déjà "' . $newStatus . '"', 400);
            }

            $call->status = $newStatus;
            $call->save();

            CallStatusHistory::create([
                'call_id' => $call->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'comment' => $request->comment,
                'changed_by' => Auth::id(),
                'created_at' => now(),
            ]);

            $call->load(['department', 'client', 'contact', 'assignedAgent', 'creator']);

            Log::info('Statut de l\'appel changé', [
                'call_id' => $call->call_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by' => Auth::id(),
                'user_name' => Auth::user()->name,
                'comment' => $request->comment,
            ]);

            return $this->successResponse(
                $call,
                'Statut changé avec succès',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors du changement de statut', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Erreur lors du changement de statut',
                500
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/call-center/calls/{id}/close",
     *     summary="Clôturer un appel",
     *     description="Marque un appel comme clôturé avec résumé de résolution",
     *     tags={"Calls"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"resolution_summary", "final_result"},
     *             @OA\Property(property="resolution_summary", type="string", example="Problème résolu par réinitialisation du mot de passe. Client peut maintenant se connecter."),
     *             @OA\Property(
     *                 property="final_result",
     *                 type="string",
     *                 enum={"resolu_satisfait", "resolu_insatisfait", "transfere", "non_resolu"},
     *                 example="resolu_satisfait"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Appel clôturé avec succès"),
     *     @OA\Response(response=404, description="Appel non trouvé"),
     *     @OA\Response(response=400, description="Appel déjà clôturé")
     * )
     */
    public function close(CloseCallRequest $request, $id)
    {
        try {
            $call = Call::find($id);

            if (!$call) {
                return $this->errorResponse('Appel non trouvé', 404);
            }

            if ($call->closed_at) {
                return $this->errorResponse('Cet appel est déjà clôturé', 400);
            }

            $closedAt = now();
            $treatmentTimeSeconds = $call->created_at->diffInSeconds($closedAt);

            $call->update([
                'status' => 'cloture',
                'resolution_summary' => $request->resolution_summary,
                'final_result' => $request->final_result,
                'closed_at' => $closedAt,
                'closed_by' => Auth::id(),
                'treatment_time_seconds' => $treatmentTimeSeconds,
            ]);

            $call->load(['department', 'client', 'contact', 'assignedAgent', 'creator', 'closer']);

            Log::info('Appel clôturé', [
                'call_id' => $call->call_id,
                'final_result' => $call->final_result,
                'treatment_time_seconds' => $treatmentTimeSeconds,
                'closed_by' => Auth::id(),
                'user_name' => Auth::user()->name,
            ]);

            return $this->successResponse(
                $call,
                'Appel clôturé avec succès',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la clôture de l\'appel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la clôture de l\'appel',
                500
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/call-center/calls/search",
     *     summary="Rechercher des appels",
     *     description="Recherche globale dans les appels par ID, téléphone, nom ou résumé",
     *     tags={"Calls"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=true,
     *         description="Terme de recherche",
     *         @OA\Schema(type="string", example="Jean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Résultats de recherche",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Résultats de recherche"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="call_id", type="string"),
     *                     @OA\Property(property="phone_number", type="string"),
     *                     @OA\Property(property="caller_name", type="string"),
     *                     @OA\Property(property="object", type="string"),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="urgency", type="string"),
     *                     @OA\Property(property="created_at", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Terme de recherche requis"
     *     )
     * )
     */
    public function search(Request $request)
    {
        try {
            $searchTerm = $request->query('q');

            if (!$searchTerm || trim($searchTerm) === '') {
                return $this->errorResponse('Le terme de recherche est obligatoire', 422);
            }

            $searchTerm = trim($searchTerm);

            $calls = Call::where(function($query) use ($searchTerm) {
                $query->where('call_id', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('phone_number', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('caller_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('summary', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('object', 'LIKE', "%{$searchTerm}%");
            })
            ->with(['department', 'assignedAgent', 'creator'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

            Log::info('Recherche d\'appels effectuée', [
                'search_term' => $searchTerm,
                'results_count' => $calls->count(),
                'searched_by' => Auth::id(),
                'user_name' => Auth::user()->name,
            ]);

            return $this->successResponse(
                $calls,
                $calls->count() . ' résultat(s) trouvé(s)',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la recherche d\'appels', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la recherche',
                500
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/call-center/calls",
     *     summary="Lister et filtrer les appels",
     *     description="Récupère la liste des appels avec filtres optionnels",
     *     tags={"Calls"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="filter[type]",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum={"entrant", "sortant"}),
     *         example="entrant"
     *     ),
     *     @OA\Parameter(
     *         name="filter[status]",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         example="en_cours"
     *     ),
     *     @OA\Parameter(
     *         name="filter[department_id]",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer"),
     *         example=1
     *     ),
     *     @OA\Parameter(
     *         name="filter[urgency]",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum={"normal", "urgent", "critique"}),
     *         example="urgent"
     *     ),
     *     @OA\Parameter(
     *         name="filter[assigned_to]",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string"),
     *         example="me",
     *         description="ID d'agent, 'me' pour mes appels, 'unassigned' pour non assignés"
     *     ),
     *     @OA\Parameter(
     *         name="filter[period]",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum={"today", "week", "month", "custom"}),
     *         example="today"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des appels filtrés",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Liste des appels récupérée"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=5),
     *                 @OA\Property(property="filters_applied", type="object"),
     *                 @OA\Property(property="calls", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = Call::query()->with(['department', 'assignedAgent', 'creator']);

            $filtersApplied = [];

            // Filter by type
            if ($request->has('filter.type')) {
                $query->where('type', $request->input('filter.type'));
                $filtersApplied['type'] = $request->input('filter.type');
            }

            // Filter by status
            if ($request->has('filter.status')) {
                $query->where('status', $request->input('filter.status'));
                $filtersApplied['status'] = $request->input('filter.status');
            }

            // Filter by department
            if ($request->has('filter.department_id')) {
                $query->where('department_id', $request->input('filter.department_id'));
                $filtersApplied['department_id'] = $request->input('filter.department_id');
            }

            // Filter by urgency
            if ($request->has('filter.urgency')) {
                $query->where('urgency', $request->input('filter.urgency'));
                $filtersApplied['urgency'] = $request->input('filter.urgency');
            }

            // Filter by assignment
            if ($request->has('filter.assigned_to')) {
                $assignedTo = $request->input('filter.assigned_to');
                
                if ($assignedTo === 'me') {
                    $query->where('assigned_to', Auth::id());
                    $filtersApplied['assigned_to'] = 'me';
                } elseif ($assignedTo === 'unassigned') {
                    $query->whereNull('assigned_to');
                    $filtersApplied['assigned_to'] = 'unassigned';
                } else {
                    $query->where('assigned_to', $assignedTo);
                    $filtersApplied['assigned_to'] = $assignedTo;
                }
            }

            // Filter by period
            if ($request->has('filter.period')) {
                $period = $request->input('filter.period');
                
                switch ($period) {
                    case 'today':
                        $query->whereDate('created_at', today());
                        $filtersApplied['period'] = 'today';
                        break;
                    
                    case 'week':
                        $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                        $filtersApplied['period'] = 'week';
                        break;
                    
                    case 'month':
                        $query->whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year);
                        $filtersApplied['period'] = 'month';
                        break;
                    
                    case 'custom':
                        if ($request->has('filter.date_from')) {
                            $query->whereDate('created_at', '>=', $request->input('filter.date_from'));
                            $filtersApplied['date_from'] = $request->input('filter.date_from');
                        }
                        if ($request->has('filter.date_to')) {
                            $query->whereDate('created_at', '<=', $request->input('filter.date_to'));
                            $filtersApplied['date_to'] = $request->input('filter.date_to');
                        }
                        $filtersApplied['period'] = 'custom';
                        break;
                }
            }

            $calls = $query->orderBy('created_at', 'desc')->get();

            $response = [
                'total' => $calls->count(),
                'filters_applied' => $filtersApplied,
                'calls' => $calls,
            ];

            Log::info('Liste des appels filtrés', [
                'filters' => $filtersApplied,
                'total_results' => $calls->count(),
                'requested_by' => Auth::id(),
            ]);

            return $this->successResponse(
                $response,
                'Liste des appels récupérée',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des appels', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la récupération des appels',
                500
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/call-center/calls/my-queue",
     *     summary="Consulter ma file d'appels",
     *     description="Récupère tous les appels assignés à l'agent connecté avec statut 'à traiter' ou 'en cours'",
     *     tags={"Calls"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="File personnelle récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="File personnelle récupérée"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=5),
     *                 @OA\Property(property="urgent_count", type="integer", example=2),
     *                 @OA\Property(property="calls", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     )
     * )
     */
    public function myQueue(Request $request)
    {
        try {
            $calls = Call::where('assigned_to', Auth::id())
                        ->whereIn('status', ['a_traiter', 'en_cours'])
                        ->with(['department', 'client', 'contact', 'creator'])
                        ->orderByRaw("FIELD(urgency, 'critique', 'urgent', 'normal')")
                        ->orderBy('created_at', 'asc')
                        ->get();

            $urgentCount = $calls->whereIn('urgency', ['urgent', 'critique'])->count();

            $callsWithTimeElapsed = $calls->map(function ($call) {
                $callArray = $call->toArray();
                $callArray['time_elapsed'] = $this->calculateTimeElapsed($call->created_at);
                return $callArray;
            });

            $response = [
                'total' => $calls->count(),
                'urgent_count' => $urgentCount,
                'calls' => $callsWithTimeElapsed,
            ];

            Log::info('File personnelle consultée', [
                'agent_id' => Auth::id(),
                'total_calls' => $calls->count(),
                'urgent_calls' => $urgentCount,
            ]);

            return $this->successResponse(
                $response,
                'File personnelle récupérée',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de la file personnelle', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la récupération de la file personnelle',
                500
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/call-center/calls/department-queue",
     *     summary="Consulter la file du département",
     *     description="Récupère tous les appels du département de l'agent connecté",
     *     tags={"Calls"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="File du département récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="File du département récupérée"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=10),
     *                 @OA\Property(property="unassigned_count", type="integer", example=3),
     *                 @OA\Property(property="my_calls_count", type="integer", example=2),
     *                 @OA\Property(property="others_calls_count", type="integer", example=5),
     *                 @OA\Property(property="calls", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Agent non assigné à un département"
     *     )
     * )
     */
    public function departmentQueue(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user->department_id) {
                return $this->errorResponse(
                    'Vous n\'êtes pas assigné à un département',
                    404
                );
            }

            $calls = Call::where('department_id', $user->department_id)
                        ->whereIn('status', ['a_traiter', 'en_cours'])
                        ->with(['department', 'client', 'contact', 'assignedAgent', 'creator'])
                        ->orderByRaw("FIELD(urgency, 'critique', 'urgent', 'normal')")
                        ->orderBy('created_at', 'asc')
                        ->get();

            $unassignedCount = $calls->where('assigned_to', null)->count();
            $myCallsCount = $calls->where('assigned_to', $user->id)->count();
            $othersCallsCount = $calls->where('assigned_to', '!=', null)
                                    ->where('assigned_to', '!=', $user->id)
                                    ->count();

            $callsWithTimeElapsed = $calls->map(function ($call) use ($user) {
                $callArray = $call->toArray();
                $callArray['time_elapsed'] = $this->calculateTimeElapsed($call->created_at);
                $callArray['is_mine'] = $call->assigned_to === $user->id;
                $callArray['is_unassigned'] = $call->assigned_to === null;
                return $callArray;
            });

            $response = [
                'total' => $calls->count(),
                'unassigned_count' => $unassignedCount,
                'my_calls_count' => $myCallsCount,
                'others_calls_count' => $othersCallsCount,
                'calls' => $callsWithTimeElapsed,
            ];

            Log::info('File du département consultée', [
                'agent_id' => $user->id,
                'department_id' => $user->department_id,
                'total_calls' => $calls->count(),
                'unassigned' => $unassignedCount,
            ]);

            return $this->successResponse(
                $response,
                'File du département récupérée',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de la file du département', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la récupération de la file du département',
                500
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/call-center/calls/{id}/assign-to-me",
     *     summary="S'auto-assigner un appel",
     *     description="Assigne un appel non assigné à l'agent connecté et change le statut en 'en_cours'",
     *     tags={"Calls"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'appel",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Appel assigné avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Appel assigné avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Appel non trouvé"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Appel déjà assigné"
     *     )
     * )
     */
    public function assignToMe($id)
    {
        try {
            $call = Call::find($id);

            if (!$call) {
                return $this->errorResponse('Appel non trouvé', 404);
            }

            if ($call->assigned_to !== null) {
                return $this->errorResponse(
                    'Cet appel est déjà assigné à ' . ($call->assignedAgent ? $call->assignedAgent->name : 'un autre agent'),
                    400
                );
            }

            $call->update([
                'assigned_to' => Auth::id(),
                'status' => 'en_cours',
            ]);

            $call->load(['department', 'client', 'contact', 'assignedAgent', 'creator']);

            Log::info('Appel auto-assigné', [
                'call_id' => $call->call_id,
                'assigned_to' => Auth::id(),
                'user_name' => Auth::user()->name,
                'previous_status' => $call->getOriginal('status'),
                'new_status' => 'en_cours',
            ]);

            return $this->successResponse(
                $call,
                'Appel assigné avec succès',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'auto-assignation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Erreur lors de l\'assignation',
                500
            );
        }
    }
}