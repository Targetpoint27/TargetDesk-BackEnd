<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\StoreCallNoteRequest;
use App\Models\Call;
use App\Models\CallNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CallNoteController extends BaseApiController
{
    /**
     * @OA\Post(
     *     path="/api/v1/call-center/calls/{id}/notes",
     *     summary="Ajouter une note à un appel",
     *     description="Crée une nouvelle note pour un appel spécifique",
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
     *             required={"note"},
     *             @OA\Property(property="note", type="string", example="Client a demandé un rappel demain matin à 10h"),
     *             @OA\Property(property="is_important", type="boolean", example=true, description="Marquer comme importante (optionnel)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Note créée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Note ajoutée avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="call_id", type="integer", example=1),
     *                 @OA\Property(property="note", type="string", example="Client a demandé un rappel demain matin à 10h"),
     *                 @OA\Property(property="is_important", type="boolean", example=true),
     *                 @OA\Property(property="created_by", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="creator",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="email", type="string")
     *                 )
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
    public function store(StoreCallNoteRequest $request, $callId)
    {
        try {
            $call = Call::find($callId);

            if (!$call) {
                return $this->errorResponse('Appel non trouvé', 404);
            }

            $noteData = $request->validated();
            $noteData['call_id'] = $callId;
            $noteData['created_by'] = Auth::id();
            $noteData['is_important'] = $request->is_important ?? false;

            $note = CallNote::create($noteData);

            $note->load('creator');

            Log::info('Note ajoutée à l\'appel', [
                'call_id' => $call->call_id,
                'note_id' => $note->id,
                'is_important' => $note->is_important,
                'created_by' => Auth::id(),
                'user_name' => Auth::user()->name,
            ]);

            return $this->successResponse(
                $note,
                'Note ajoutée avec succès',
                201
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'ajout de la note', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Erreur lors de l\'ajout de la note',
                500
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/call-center/calls/{id}/notes",
     *     summary="Lister les notes d'un appel",
     *     description="Récupère toutes les notes d'un appel dans l'ordre chronologique",
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
     *         description="Liste des notes récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Notes récupérées avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="note", type="string"),
     *                     @OA\Property(property="is_important", type="boolean"),
     *                     @OA\Property(property="created_at", type="string"),
     *                     @OA\Property(
     *                         property="creator",
     *                         type="object",
     *                         @OA\Property(property="name", type="string")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Appel non trouvé"
     *     )
     * )
     */
    public function index($callId)
    {
        try {
            $call = Call::find($callId);

            if (!$call) {
                return $this->errorResponse('Appel non trouvé', 404);
            }

            $notes = CallNote::where('call_id', $callId)
                            ->with('creator')
                            ->orderBy('created_at', 'asc')
                            ->get();

            return $this->successResponse(
                $notes,
                'Notes récupérées avec succès',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des notes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la récupération des notes',
                500
            );
        }
    }
}