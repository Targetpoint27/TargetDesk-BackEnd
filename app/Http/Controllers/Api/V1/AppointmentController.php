<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Client;
use App\Models\Appointment;
use App\Models\AppointmentParticipant;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

/**
 * @OA\Tag(name="Appointments", description="Gestion des rendez-vous client")
 */
class AppointmentController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/v1/clients/{client}/appointments",
     *     tags={"Appointments"},
     *     summary="Liste des rendez-vous client",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="client", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"planned", "confirmed", "completed", "cancelled", "postponed"})),
     *     @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Rendez-vous récupérés")
     * )
     */
    public function index(Request $request, Client $client): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 20);

            $query = $client->appointments()->with(['user:id,name', 'participants.contact:id,first_name,last_name']);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('date_from') && $request->has('date_to')) {
                $query->whereBetween('scheduled_at', [
                    Carbon::parse($request->date_from)->startOfDay(),
                    Carbon::parse($request->date_to)->endOfDay()
                ]);
            } elseif ($request->has('date_from')) {
                $query->whereDate('scheduled_at', '>=', $request->date_from);
            } elseif ($request->has('date_to')) {
                $query->whereDate('scheduled_at', '<=', $request->date_to);
            }

            $appointments = $query->orderBy('scheduled_at', 'desc')->paginate($perPage);

            $stats = [
                'total_appointments' => $client->appointments()->count(),
                'upcoming' => $client->appointments()->where('scheduled_at', '>', now())->whereNotIn('status', ['cancelled', 'completed'])->count(),
                'completed' => $client->appointments()->where('status', 'completed')->count(),
                'cancelled' => $client->appointments()->where('status', 'cancelled')->count(),
                'postponed' => $client->appointments()->where('status', 'postponed')->count(),
                'this_month' => $client->appointments()->whereMonth('scheduled_at', now()->month)->count()
            ];

            return $this->successResponse([
                'appointments' => $appointments,
                'stats' => $stats
            ], 'Rendez-vous récupérés');

        } catch (\Exception $e) {
            Log::error('Error fetching client appointments: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la récupération des rendez-vous', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/v1/clients/{client}/appointments",
     *     tags={"Appointments"},
     *     summary="Planifier un rendez-vous",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="client", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="scheduled_at", type="string", format="datetime"),
     *         @OA\Property(property="duration", type="integer"),
     *         @OA\Property(property="location", type="string"),
     *         @OA\Property(property="type", type="string", enum={"commercial", "support", "demo", "negotiation", "closing", "other"}),
     *         @OA\Property(property="participants", type="array", @OA\Items(type="object"))
     *     )),
     *     @OA\Response(response=201, description="Rendez-vous planifié")
     * )
     */
    public function store(Request $request, Client $client): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'scheduled_at' => 'required|date|after:now',
                'duration' => 'required|integer|min:15|max:480',
                'location' => 'nullable|string|max:255',
                'meeting_url' => 'nullable|string|max:255',
                'type' => 'required|in:commercial,support,demo,negotiation,closing,other',
                'timezone' => 'nullable|string|max:50',
                'reminder_minutes' => 'nullable|integer|min:0|max:1440',
                'participants' => 'nullable|array',
                'participants.*.contact_id' => 'nullable|exists:contacts,id',
                'participants.*.email' => 'nullable|email|max:255',
                'participants.*.name' => 'required_without:participants.*.contact_id|string|max:255'
            ]);

            $appointment = $client->appointments()->create([
                'user_id' => auth()->id(),
                'organizer_id' => auth()->id(),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'scheduled_at' => Carbon::parse($validated['scheduled_at']),
                'duration' => $validated['duration'],
                'location' => $validated['location'] ?? null,
                'meeting_url' => $validated['meeting_url'] ?? null,
                'type' => $validated['type'],
                'status' => 'planned',
                'timezone' => $validated['timezone'] ?? 'Europe/Paris',
                'reminder_minutes' => $validated['reminder_minutes'] ?? 15
            ]);

            if (isset($validated['participants'])) {
                foreach ($validated['participants'] as $participantData) {
                    $participantInfo = [
                        'contact_id' => $participantData['contact_id'] ?? null,
                        'status' => 'invited'
                    ];

                    // Si c'est un contact existant, récupérer ses infos
                    if (!empty($participantData['contact_id'])) {
                        $contact = Contact::with('emails')->find($participantData['contact_id']);
                        if ($contact) {
                            $participantInfo['email'] = $contact->emails->first()->email ?? $contact->email ?? '';
                            $participantInfo['name'] = $contact->first_name . ' ' . $contact->last_name;
                            $participantInfo['type'] = 'contact';
                        } else {
                            continue; // Contact not found, skip
                        }
                    } else {
                        // Participant externe
                        $participantInfo['email'] = $participantData['email'] ?? '';
                        $participantInfo['name'] = $participantData['name'] ?? '';
                        $participantInfo['type'] = 'external';
                    }

                    $appointment->participants()->create($participantInfo);
                }
            }

            $appointment->load(['user:id,name', 'participants.contact:id,first_name,last_name']);

            return $this->successResponse($appointment, 'Rendez-vous planifié', 201);

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            Log::error('Error creating appointment: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la planification du rendez-vous', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/v1/appointments/{appointment}",
     *     tags={"Appointments"},
     *     summary="Détails d'un rendez-vous",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="appointment", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Rendez-vous trouvé")
     * )
     */
    public function show(Appointment $appointment): JsonResponse
    {
        try {
            $appointment->load(['client:id,name,client_id', 'user:id,name', 'participants.contact:id,first_name,last_name']);
            return $this->successResponse($appointment, 'Rendez-vous trouvé');

        } catch (\Exception $e) {
            Log::error('Error fetching appointment: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la récupération du rendez-vous', 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/v1/appointments/{appointment}",
     *     tags={"Appointments"},
     *     summary="Modifier un rendez-vous",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="appointment", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Rendez-vous modifié")
     * )
     */
    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|nullable|string',
                'scheduled_at' => 'sometimes|required|date',
                'duration' => 'sometimes|required|integer|min:15|max:480',
                'location' => 'sometimes|nullable|string|max:255',
                'meeting_url' => 'sometimes|nullable|string|max:255',
                'type' => 'sometimes|required|in:commercial,support,demo,negotiation,closing,other',
                'status' => 'sometimes|required|in:planned,confirmed,completed,cancelled,postponed',
                'completion_notes' => 'sometimes|nullable|string',
                'completion_outcome' => 'sometimes|nullable|in:positive,negative,neutral,follow_up',
                'timezone' => 'sometimes|nullable|string|max:50',
                'reminder_minutes' => 'sometimes|nullable|integer|min:0|max:1440'
            ]);

            if (isset($validated['scheduled_at'])) {
                $validated['scheduled_at'] = Carbon::parse($validated['scheduled_at']);
            }

            $appointment->update($validated);
            $appointment->load(['client:id,name,client_id', 'user:id,name', 'participants.contact:id,first_name,last_name']);

            return $this->successResponse($appointment, 'Rendez-vous modifié');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            Log::error('Error updating appointment: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la modification du rendez-vous', 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/v1/appointments/{appointment}",
     *     tags={"Appointments"},
     *     summary="Supprimer un rendez-vous",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="appointment", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Rendez-vous supprimé")
     * )
     */
    public function destroy(Appointment $appointment): JsonResponse
    {
        try {
            $appointment->participants()->delete();
            $appointment->interaction?->delete();
            $appointment->delete();

            return $this->successResponse(null, 'Rendez-vous supprimé', 204);

        } catch (\Exception $e) {
            Log::error('Error deleting appointment: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la suppression du rendez-vous', 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/v1/appointments/{appointment}/status",
     *     tags={"Appointments"},
     *     summary="Changer le statut d'un rendez-vous",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="appointment", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="status", type="string", enum={"planned", "confirmed", "completed", "cancelled", "postponed"}),
     *         @OA\Property(property="notes", type="string")
     *     )),
     *     @OA\Response(response=200, description="Statut modifié")
     * )
     */
    public function updateStatus(Request $request, Appointment $appointment): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:planned,confirmed,completed,cancelled,postponed',
                'completion_notes' => 'nullable|string',
                'completion_outcome' => 'nullable|in:positive,negative,neutral,follow_up'
            ]);

            $updateData = ['status' => $validated['status']];
            if (isset($validated['completion_notes'])) {
                $updateData['completion_notes'] = $validated['completion_notes'];
            }
            if (isset($validated['completion_outcome'])) {
                $updateData['completion_outcome'] = $validated['completion_outcome'];
            }

            $appointment->update($updateData);

            return $this->successResponse([
                'status' => $appointment->status
            ], 'Statut du rendez-vous modifié');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            Log::error('Error updating appointment status: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la modification du statut', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/v1/dashboard/appointments/today",
     *     tags={"Appointments"},
     *     summary="Rendez-vous du jour",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Rendez-vous d'aujourd'hui")
     * )
     */
    public function today(): JsonResponse
    {
        try {
            $appointments = Appointment::with(['client:id,name,client_id', 'user:id,name', 'participants.contact:id,first_name,last_name'])
                ->whereDate('scheduled_at', today())
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->orderBy('scheduled_at', 'asc')
                ->get();

            return $this->successResponse($appointments, 'Rendez-vous d\'aujourd\'hui');

        } catch (\Exception $e) {
            Log::error('Error fetching today\'s appointments: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la récupération des rendez-vous', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/v1/dashboard/appointments/upcoming",
     *     tags={"Appointments"},
     *     summary="Prochains rendez-vous",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="days", in="query", @OA\Schema(type="integer", default=7)),
     *     @OA\Response(response=200, description="Prochains rendez-vous")
     * )
     */
    public function upcoming(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 7);
            $limit = $request->get('limit', 20);

            $appointments = Appointment::with(['client:id,name,client_id', 'user:id,name', 'participants.contact:id,first_name,last_name'])
                ->where('scheduled_at', '>', now())
                ->where('scheduled_at', '<=', now()->addDays($days))
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->orderBy('scheduled_at', 'asc')
                ->limit($limit)
                ->get();

            return $this->successResponse($appointments, 'Prochains rendez-vous');

        } catch (\Exception $e) {
            Log::error('Error fetching upcoming appointments: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la récupération des rendez-vous', 500);
        }
    }
}
