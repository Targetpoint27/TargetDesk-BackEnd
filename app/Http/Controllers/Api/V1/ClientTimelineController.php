<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Client;
use App\Models\ClientInteraction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(name="Client Timeline", description="Historique unifié des interactions client")
 */
class ClientTimelineController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/v1/clients/{client}/timeline",
     *     tags={"Client Timeline"},
     *     summary="Timeline chronologique client",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="client", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string", enum={"note", "call", "appointment"})),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=20)),
     *     @OA\Response(response=200, description="Timeline récupérée")
     * )
     */
    public function index(Request $request, Client $client): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 20);

            $query = ClientInteraction::with(['user:id,name'])
                ->where('client_id', $client->id)
                ->visibleBy(auth()->id());

            if ($request->has('type')) $query->ofType($request->type);
            if ($request->has('user_id')) $query->where('user_id', $request->user_id);
            if ($request->has('date_from') && $request->has('date_to')) {
                $query->betweenDates($request->date_from, $request->date_to);
            }

            $timeline = $query->orderBy('occurred_at', 'desc')->paginate($perPage);
            $stats = $this->getTimelineStats($client->id);

            return $this->successResponse([
                'timeline' => $timeline,
                'stats' => $stats
            ], 'Timeline récupérée');

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur timeline', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/v1/dashboard/interactions",
     *     tags={"Client Timeline"},
     *     summary="Dashboard interactions",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Dashboard récupéré")
     * )
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 7);
            $limit = $request->get('limit', 50);
            $startDate = now()->subDays($days);

            $recentInteractions = ClientInteraction::with(['client:id,name,client_id', 'user:id,name'])
                ->visibleBy(auth()->id())
                ->where('occurred_at', '>=', $startDate)
                ->orderBy('occurred_at', 'desc')
                ->limit($limit)
                ->get();

            $stats = [
                'total_interactions' => $recentInteractions->count(),
                'notes_count' => $recentInteractions->where('type', 'note')->count(),
                'calls_count' => $recentInteractions->where('type', 'call')->count(),
                'appointments_count' => $recentInteractions->where('type', 'appointment')->count()
            ];

            return $this->successResponse([
                'recent_interactions' => $recentInteractions,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur dashboard', 500);
        }
    }

    private function getTimelineStats(int $clientId): array
    {
        $interactions = ClientInteraction::where('client_id', $clientId)
            ->visibleBy(auth()->id())->get();

        return [
            'total_interactions' => $interactions->count(),
            'by_type' => $interactions->groupBy('type')->map->count(),
            'by_month' => $interactions->groupBy(fn($item) => $item->occurred_at->format('Y-m'))->map->count()->take(12)
        ];
    }
}
