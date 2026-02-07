<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreCallRequest;
use App\Http\Requests\UpdateCallRequest;
use App\Http\Requests\ChangeCallStatusRequest;
use App\Models\CallStatusHistory;
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
}