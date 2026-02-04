<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\ScheduledEmailReminder;
use App\Models\UserNotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Email Notification Preferences",
 *     description="API pour la gestion des préférences de notifications email"
 * )
 */
class EmailNotificationPreferenceController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/v1/users/{user}/email-preferences",
     *     tags={"Email Notification Preferences"},
     *     summary="Obtenir les préférences email d'un utilisateur",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Préférences email récupérées",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Email preferences retrieved"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="appointment_reminder", type="object",
     *                     @OA\Property(property="timing", type="array", @OA\Items(type="integer"), example={60, 1440}),
     *                     @OA\Property(property="email_enabled", type="boolean", example=true),
     *                     @OA\Property(property="is_active", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function show(Request $request, int $userId): JsonResponse
    {
        try {
            // Vérifier que l'utilisateur peut accéder à ces préférences
            if (auth()->id() !== $userId && !auth()->user()->hasAnyRole(['super_admin', 'admin'])) {
                return $this->errorResponse('Unauthorized to access these preferences', 403);
            }

            $appointmentPrefs = UserNotificationPreference::getUserPreferences($userId, 'appointment_reminder');

            $preferences = [
                'user_id' => $userId,
                'appointment_reminder' => $appointmentPrefs ? [
                    'timing' => $appointmentPrefs->timing,
                    'email_enabled' => $appointmentPrefs->email_enabled,
                    'is_active' => $appointmentPrefs->is_active,
                    'formatted_timing' => $appointmentPrefs->formatted_timing
                ] : [
                    'timing' => UserNotificationPreference::getDefaultTiming(),
                    'email_enabled' => true,
                    'is_active' => true,
                    'formatted_timing' => ['1 heure']
                ]
            ];

            return $this->successResponse($preferences, 'Email preferences retrieved');

        } catch (\Exception $e) {
            Log::error('Error fetching email preferences: ' . $e->getMessage());
            return $this->errorResponse('Error fetching email preferences', 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/v1/users/{user}/email-preferences",
     *     tags={"Email Notification Preferences"},
     *     summary="Mettre à jour les préférences email",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="appointment_reminder", type="object",
     *                 @OA\Property(property="timing", type="array", @OA\Items(type="integer"), example={15, 60, 1440}),
     *                 @OA\Property(property="email_enabled", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Préférences mises à jour avec succès"
     *     )
     * )
     */
    public function update(Request $request, int $userId): JsonResponse
    {
        try {
            // Vérifier que l'utilisateur peut modifier ces préférences
            if (auth()->id() !== $userId && !auth()->user()->hasAnyRole(['super_admin', 'admin'])) {
                return $this->errorResponse('Unauthorized to modify these preferences', 403);
            }

            $validated = $request->validate([
                'appointment_reminder' => 'required|array',
                'appointment_reminder.timing' => 'required|array|min:1|max:5',
                'appointment_reminder.timing.*' => 'integer|min:1|max:10080', // 1 minute à 1 semaine
                'appointment_reminder.email_enabled' => 'boolean'
            ]);

            $appointmentData = $validated['appointment_reminder'];

            // Valider les timings (pas de doublons, ordonnés)
            $timings = array_unique($appointmentData['timing']);
            sort($timings);

            $emailEnabled = $appointmentData['email_enabled'] ?? true;

            // Sauvegarder les préférences
            $preferences = UserNotificationPreference::setUserPreferences(
                $userId,
                'appointment_reminder',
                $timings,
                $emailEnabled
            );

            Log::info("Updated email notification preferences", [
                'user_id' => $userId,
                'timing' => $timings,
                'email_enabled' => $emailEnabled,
                'updated_by' => auth()->id()
            ]);

            return $this->successResponse([
                'preferences' => [
                    'user_id' => $userId,
                    'appointment_reminder' => [
                        'timing' => $preferences->timing,
                        'email_enabled' => $preferences->email_enabled,
                        'is_active' => $preferences->is_active,
                        'formatted_timing' => $preferences->formatted_timing
                    ]
                ]
            ], 'Email preferences updated successfully');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            Log::error('Error updating email preferences: ' . $e->getMessage());
            return $this->errorResponse('Error updating email preferences', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/v1/email-reminders/pending",
     *     tags={"Email Notification Preferences"},
     *     summary="Obtenir les rappels email en attente",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rappels en attente récupérés"
     *     )
     * )
     */
    public function getPendingReminders(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 20);
            $userId = auth()->id();

            $query = ScheduledEmailReminder::pending()
                ->with(['appointment.client', 'appointment.user'])
                ->orderBy('scheduled_for', 'asc');

            // Si ce n'est pas un admin, limiter aux rappels de l'utilisateur
            if (!auth()->user()->hasAnyRole(['super_admin', 'admin'])) {
                $query->where('user_id', $userId);
            }

            $reminders = $query->limit($limit)->get();

            return $this->successResponse([
                'reminders' => $reminders->map(function ($reminder) {
                    return [
                        'id' => $reminder->id,
                        'appointment_id' => $reminder->appointment_id,
                        'type' => $reminder->type,
                        'formatted_type' => $reminder->formatted_type,
                        'scheduled_for' => $reminder->scheduled_for,
                        'email_to' => $reminder->email_to,
                        'appointment' => [
                            'id' => $reminder->appointment->id,
                            'title' => $reminder->appointment->title,
                            'scheduled_at' => $reminder->appointment->scheduled_at,
                            'client_name' => $reminder->appointment->client->name ?? 'N/A',
                            'status' => $reminder->appointment->status
                        ]
                    ];
                }),
                'count' => $reminders->count()
            ], 'Pending email reminders retrieved');

        } catch (\Exception $e) {
            Log::error('Error fetching pending reminders: ' . $e->getMessage());
            return $this->errorResponse('Error fetching pending reminders', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/v1/email-reminders/sent",
     *     tags={"Email Notification Preferences"},
     *     summary="Obtenir l'historique des rappels envoyés",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="Nombre de jours d'historique",
     *         @OA\Schema(type="integer", default=30)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Historique des rappels envoyés"
     *     )
     * )
     */
    public function getSentReminders(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 50);
            $days = $request->get('days', 30);
            $userId = auth()->id();

            $query = ScheduledEmailReminder::sent()
                ->with(['appointment.client', 'appointment.user'])
                ->where('sent_at', '>=', now()->subDays($days))
                ->orderBy('sent_at', 'desc');

            // Si ce n'est pas un admin, limiter aux rappels de l'utilisateur
            if (!auth()->user()->hasAnyRole(['super_admin', 'admin'])) {
                $query->where('user_id', $userId);
            }

            $reminders = $query->limit($limit)->get();

            return $this->successResponse([
                'reminders' => $reminders->map(function ($reminder) {
                    return [
                        'id' => $reminder->id,
                        'appointment_id' => $reminder->appointment_id,
                        'type' => $reminder->type,
                        'formatted_type' => $reminder->formatted_type,
                        'scheduled_for' => $reminder->scheduled_for,
                        'sent_at' => $reminder->sent_at,
                        'email_to' => $reminder->email_to,
                        'appointment' => [
                            'id' => $reminder->appointment->id,
                            'title' => $reminder->appointment->title,
                            'scheduled_at' => $reminder->appointment->scheduled_at,
                            'client_name' => $reminder->appointment->client->name ?? 'N/A',
                            'status' => $reminder->appointment->status
                        ]
                    ];
                }),
                'count' => $reminders->count(),
                'period_days' => $days
            ], 'Sent email reminders retrieved');

        } catch (\Exception $e) {
            Log::error('Error fetching sent reminders: ' . $e->getMessage());
            return $this->errorResponse('Error fetching sent reminders', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/v1/email-reminders/statistics",
     *     tags={"Email Notification Preferences"},
     *     summary="Obtenir les statistiques des rappels email",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="Période en jours pour les statistiques",
     *         @OA\Schema(type="integer", default=30)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statistiques des rappels email"
     *     )
     * )
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);
            $userId = auth()->id();
            $isAdmin = auth()->user()->hasAnyRole(['super_admin', 'admin']);

            $query = ScheduledEmailReminder::where('created_at', '>=', now()->subDays($days));

            if (!$isAdmin) {
                $query->where('user_id', $userId);
            }

            $totalReminders = $query->count();
            $sentReminders = $query->clone()->where('status', ScheduledEmailReminder::STATUS_SENT)->count();
            $failedReminders = $query->clone()->where('status', ScheduledEmailReminder::STATUS_FAILED)->count();
            $pendingReminders = $query->clone()->where('status', ScheduledEmailReminder::STATUS_PENDING)->count();

            $statistics = [
                'period_days' => $days,
                'total_reminders' => $totalReminders,
                'sent_reminders' => $sentReminders,
                'failed_reminders' => $failedReminders,
                'pending_reminders' => $pendingReminders,
                'success_rate' => $totalReminders > 0 ? round(($sentReminders / $totalReminders) * 100, 2) : 0,
                'failure_rate' => $totalReminders > 0 ? round(($failedReminders / $totalReminders) * 100, 2) : 0
            ];

            return $this->successResponse($statistics, 'Email reminder statistics retrieved');

        } catch (\Exception $e) {
            Log::error('Error fetching email reminder statistics: ' . $e->getMessage());
            return $this->errorResponse('Error fetching statistics', 500);
        }
    }
}
