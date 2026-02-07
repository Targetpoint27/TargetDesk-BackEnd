<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreCallRequest;
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
     *     path="/api/v1/calls",
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
}