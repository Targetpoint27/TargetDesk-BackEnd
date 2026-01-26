<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Client;
use App\Models\ClientInteraction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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
     *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string", enum={"note", "call", "appointment", "email", "opportunity", "modification"})),
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

            // Add detail links to each timeline entry
            $timeline->getCollection()->transform(function ($interaction) {
                $interaction->detail_link = $this->getDetailLink($interaction);
                return $interaction;
            });

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
                'appointments_count' => $recentInteractions->where('type', 'appointment')->count(),
                'emails_count' => $recentInteractions->where('type', 'email')->count(),
                'opportunities_count' => $recentInteractions->where('type', 'opportunity')->count(),
                'modifications_count' => $recentInteractions->where('type', 'modification')->count()
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

    private function getDetailLink($interaction): ?string
    {
        $baseUrl = config('app.url') . '/api/v1';

        return match($interaction->type) {
            'note' => "{$baseUrl}/notes/{$interaction->reference_id}",
            'call' => "{$baseUrl}/calls/{$interaction->reference_id}",
            'appointment' => "{$baseUrl}/appointments/{$interaction->reference_id}",
            'email' => "{$baseUrl}/emails/{$interaction->reference_id}",
            'opportunity' => "{$baseUrl}/opportunities/{$interaction->reference_id}",
            'modification' => null, // Audit logs don't have detail endpoints
            default => null
        };
    }

    /**
     * @OA\Get(
     *     path="/v1/clients/{client}/timeline/export",
     *     tags={"Client Timeline"},
     *     summary="Exporter timeline en CSV/Excel",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="client", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="format", in="query", @OA\Schema(type="string", enum={"csv", "xlsx"}, default="csv")),
     *     @OA\Parameter(name="download", in="query", @OA\Schema(type="boolean", default=false)),
     *     @OA\Response(response=200, description="Export généré")
     * )
     */
    public function export(Request $request, Client $client)
    {
        try {
            $validated = $request->validate([
                'format' => 'nullable|in:csv,xlsx',
                'download' => 'nullable|string'
            ]);

            $format = $validated['format'] ?? 'csv';
            $directDownload = filter_var($validated['download'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $interactions = ClientInteraction::with(['user:id,name'])
                ->where('client_id', $client->id)
                ->visibleBy(auth()->id())
                ->orderBy('occurred_at', 'desc')
                ->get();

            if ($interactions->isEmpty()) {
                return $this->successResponse([
                    'message' => 'Aucune interaction à exporter pour ce client',
                    'records_count' => 0,
                    'client_id' => $client->id,
                    'client_name' => $client->name
                ], 'Timeline vide');
            }

            $exportData = $interactions->map(function ($interaction) {
                return [
                    'Date' => $interaction->occurred_at->format('Y-m-d H:i:s'),
                    'Type' => ucfirst($interaction->type),
                    'Titre' => $interaction->title,
                    'Résumé' => $interaction->summary,
                    'Utilisateur' => $interaction->user->name,
                    'Importance' => $interaction->importance_level,
                    'Confidentialité' => $interaction->privacy_level,
                    'Lien détail' => $this->getDetailLink($interaction)
                ];
            });

            $filename = "timeline_client_{$client->id}_" . now()->format('Y-m-d_H-i-s') . ".{$format}";

            // Si téléchargement direct demandé, retourner le fichier
            if ($directDownload) {
                return $this->generateFileDownload($exportData, $filename, $format);
            }

            // Export metadata
            $exportMetadata = [
                'exported_at' => now()->format('Y-m-d H:i:s'),
                'exported_by' => auth()->user()->name,
                'client_info' => [
                    'id' => $client->id,
                    'client_id' => $client->client_id,
                    'name' => $client->name
                ],
                'date_range' => [
                    'from' => $interactions->min('occurred_at')?->format('Y-m-d H:i:s'),
                    'to' => $interactions->max('occurred_at')?->format('Y-m-d H:i:s')
                ],
                'types_included' => $interactions->groupBy('type')->keys()->toArray(),
                'total_by_type' => $interactions->groupBy('type')->map->count()->toArray()
            ];

            // For demonstration, return JSON with export info
            // In production, you would use maatwebsite/excel to generate actual files
            return $this->successResponse([
                'filename' => $filename,
                'format' => $format,
                'records_count' => $exportData->count(),
                'download_url' => config('app.url') . "/exports/{$filename}",
                'metadata' => $exportMetadata,
                'preview_data' => $exportData->take(5), // Show first 5 records as preview
                'columns' => array_keys($exportData->first() ?? [])
            ], 'Export préparé');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            Log::error('Error exporting timeline: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de l\'export', 500);
        }
    }

    private function generateFileDownload($data, $filename, $format)
    {
        if ($format === 'csv') {
            return $this->generateCSVDownload($data, $filename);
        } else {
            // Pour Excel, on simule avec du CSV pour l'instant
            // En production, utiliser maatwebsite/excel
            return $this->generateCSVDownload($data, $filename);
        }
    }

    private function generateCSVDownload($data, $filename)
    {
        $csv = "Date;Type;Titre;Résumé;Utilisateur;Importance;Confidentialité;Lien détail\n";

        foreach ($data as $row) {
            $csv .= '"' . implode('";"', [
                $row['Date'],
                $row['Type'],
                str_replace('"', '""', $row['Titre']),
                str_replace('"', '""', $row['Résumé']),
                $row['Utilisateur'],
                $row['Importance'],
                $row['Confidentialité'],
                $row['Lien détail']
            ]) . '"' . "\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=utf-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->header('Access-Control-Expose-Headers', 'Content-Disposition');
    }
}
