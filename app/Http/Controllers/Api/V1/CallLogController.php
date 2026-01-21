<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Client;
use App\Models\CallLog;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

/**
 * @OA\Tag(name="Call Logs", description="Journal des appels client")
 */
class CallLogController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/v1/clients/{client}/calls",
     *     tags={"Call Logs"},
     *     summary="Liste des appels client",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="client", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string", enum={"incoming", "outgoing", "missed"})),
     *     @OA\Parameter(name="outcome", in="query", @OA\Schema(type="string", enum={"positive", "neutral", "negative", "no_answer"})),
     *     @OA\Parameter(name="follow_up_required", in="query", @OA\Schema(type="boolean")),
     *     @OA\Response(response=200, description="Appels récupérés")
     * )
     */
    public function index(Request $request, Client $client): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 20);

            $query = $client->callLogs()->with(['contact:id,first_name,last_name', 'user:id,name']);

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('outcome')) {
                $query->where('outcome', $request->outcome);
            }

            if ($request->has('follow_up_required')) {
                $query->needsFollowUp();
            }

            if ($request->has('date_from') && $request->has('date_to')) {
                $query->whereBetween('called_at', [$request->date_from, $request->date_to]);
            }

            $calls = $query->orderBy('called_at', 'desc')->paginate($perPage);

            $stats = [
                'total_calls' => $client->callLogs()->count(),
                'outgoing_calls' => $client->callLogs()->outgoing()->count(),
                'positive_calls' => $client->callLogs()->positive()->count(),
                'pending_follow_ups' => $client->callLogs()->needsFollowUp()->count(),
                'total_duration' => $client->callLogs()->sum('duration')
            ];

            return $this->successResponse([
                'calls' => $calls,
                'stats' => $stats
            ], 'Appels récupérés');

        } catch (\Exception $e) {
            Log::error('Error fetching client calls: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la récupération des appels', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/v1/clients/{client}/calls",
     *     tags={"Call Logs"},
     *     summary="Enregistrer un appel client",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="client", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="contact_id", type="integer"),
     *         @OA\Property(property="phone_number", type="string"),
     *         @OA\Property(property="type", type="string", enum={"incoming", "outgoing", "missed"}),
     *         @OA\Property(property="called_at", type="string", format="datetime"),
     *         @OA\Property(property="duration", type="integer"),
     *         @OA\Property(property="subject", type="string"),
     *         @OA\Property(property="summary", type="string"),
     *         @OA\Property(property="outcome", type="string", enum={"positive", "neutral", "negative", "no_answer"}),
     *         @OA\Property(property="follow_up_required", type="boolean"),
     *         @OA\Property(property="follow_up_date", type="string", format="date")
     *     )),
     *     @OA\Response(response=201, description="Appel enregistré")
     * )
     */
    public function store(Request $request, Client $client): JsonResponse
    {
        try {
            $validated = $request->validate([
                'contact_id' => 'nullable|exists:contacts,id',
                'phone_number' => 'required|string|max:20',
                'type' => 'required|in:incoming,outgoing,missed',
                'called_at' => 'nullable|date',
                'duration' => 'nullable|integer|min:0',
                'subject' => 'required|string|max:255',
                'summary' => 'nullable|string',
                'outcome' => 'nullable|in:positive,negative,neutral,appointment,callback',
                'follow_up_required' => 'boolean',
                'follow_up_date' => 'nullable|date|after:today'
            ]);

            if (isset($validated['contact_id']) && $validated['contact_id']) {
                $contact = Contact::find($validated['contact_id']);
                if (!$contact || ($contact->client_id !== $client->id && $contact->supplier_id === null)) {
                    return $this->errorResponse('Contact invalide pour ce client', 422);
                }
            }

            $callLog = $client->callLogs()->create([
                'user_id' => auth()->id(),
                'contact_id' => $validated['contact_id'] ?? null,
                'phone_number' => $validated['phone_number'],
                'type' => $validated['type'],
                'called_at' => isset($validated['called_at']) && $validated['called_at'] ? Carbon::parse($validated['called_at']) : now(),
                'duration' => $validated['duration'] ?? 0,
                'subject' => $validated['subject'],
                'summary' => $validated['summary'] ?? '',
                'outcome' => $validated['outcome'] ?? 'neutral',
                'follow_up_required' => $validated['follow_up_required'] ?? false,
                'follow_up_date' => isset($validated['follow_up_date']) && $validated['follow_up_date'] ? Carbon::parse($validated['follow_up_date']) : null,
            ]);

            $callLog->load(['client:id,name,client_id', 'contact:id,first_name,last_name', 'user:id,name']);

            return $this->successResponse($callLog, 'Appel enregistré', 201);

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            Log::error('Error creating call log: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de l\'enregistrement de l\'appel', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/v1/calls/{call}",
     *     tags={"Call Logs"},
     *     summary="Détails d'un appel",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="call", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Appel trouvé")
     * )
     */
    public function show(CallLog $call): JsonResponse
    {
        try {
            $call->load(['client:id,name,client_id', 'contact:id,first_name,last_name', 'user:id,name']);
            return $this->successResponse($call, 'Appel trouvé');

        } catch (\Exception $e) {
            Log::error('Error fetching call: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la récupération de l\'appel', 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/v1/calls/{call}",
     *     tags={"Call Logs"},
     *     summary="Modifier un appel",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="call", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Appel modifié")
     * )
     */
    public function update(Request $request, CallLog $call): JsonResponse
    {
        try {
            $validated = $request->validate([
                'contact_id' => 'sometimes|nullable|exists:contacts,id',
                'phone_number' => 'sometimes|required|string|max:20',
                'type' => 'sometimes|required|in:incoming,outgoing,missed',
                'called_at' => 'sometimes|nullable|date',
                'duration' => 'sometimes|nullable|integer|min:0',
                'subject' => 'sometimes|required|string|max:255',
                'summary' => 'sometimes|nullable|string',
                'outcome' => 'sometimes|nullable|in:positive,neutral,negative,no_answer',
                'follow_up_required' => 'sometimes|boolean',
                'follow_up_date' => 'sometimes|nullable|date|after:today'
            ]);

            if (isset($validated['called_at'])) {
                $validated['called_at'] = Carbon::parse($validated['called_at']);
            }

            if (isset($validated['follow_up_date'])) {
                $validated['follow_up_date'] = Carbon::parse($validated['follow_up_date']);
            }

            $call->update($validated);
            $call->load(['client:id,name,client_id', 'contact:id,first_name,last_name', 'user:id,name']);

            return $this->successResponse($call, 'Appel modifié');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            Log::error('Error updating call: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la modification de l\'appel', 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/v1/calls/{call}",
     *     tags={"Call Logs"},
     *     summary="Supprimer un appel",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="call", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Appel supprimé")
     * )
     */
    public function destroy(CallLog $call): JsonResponse
    {
        try {
            $call->interaction?->delete();
            $call->delete();

            return $this->successResponse(null, 'Appel supprimé', 204);

        } catch (\Exception $e) {
            Log::error('Error deleting call: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la suppression de l\'appel', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/v1/dashboard/calls/follow-ups",
     *     tags={"Call Logs"},
     *     summary="Appels nécessitant un suivi",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Suivis récupérés")
     * )
     */
    public function pendingFollowUps(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 20);

            $followUps = CallLog::with(['client:id,name,client_id', 'contact:id,first_name,last_name', 'user:id,name'])
                ->needsFollowUp()
                ->where(function ($query) {
                    $query->whereDate('follow_up_date', '<=', now())
                          ->orWhereNull('follow_up_date');
                })
                ->orderBy('follow_up_date', 'asc')
                ->orderBy('called_at', 'desc')
                ->paginate($perPage);

            return $this->successResponse($followUps, 'Suivis récupérés');

        } catch (\Exception $e) {
            Log::error('Error fetching follow-ups: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la récupération des suivis', 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/v1/calls/{call}/complete-follow-up",
     *     tags={"Call Logs"},
     *     summary="Marquer le suivi comme terminé",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="call", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Suivi terminé")
     * )
     */
    public function completeFollowUp(CallLog $call): JsonResponse
    {
        try {
            $call->update([
                'follow_up_required' => false,
                'follow_up_date' => null
            ]);

            return $this->successResponse([
                'follow_up_required' => false
            ], 'Suivi marqué comme terminé');

        } catch (\Exception $e) {
            Log::error('Error completing follow-up: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors du marquage du suivi', 500);
        }
    }
}
