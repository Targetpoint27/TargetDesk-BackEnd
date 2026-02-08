<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * @OA\Tag(
 * name="Reports",
 * description="Reporting et statistiques Call Center (Epic 7)"
 * )
 */
class CallReportController extends BaseApiController
{
    /**
     * @OA\Get(
     * path="/api/v1/call-center/reports/daily",
     * summary="Consulter 'Mes appels du jour' (US-CC-040)",
     * description="Affiche l'activité quotidienne de l'agent : liste des appels, KPIs et graphiques.",
     * tags={"Reports"},
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="date",
     * in="query",
     * description="Date spécifique (Format YYYY-MM-DD). Défaut: Aujourd'hui.",
     * @OA\Schema(type="string", format="date", example="2026-02-07")
     * ),
     * @OA\Response(
     * response=200,
     * description="Rapport quotidien récupéré",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="data", type="object",
     * @OA\Property(property="date", type="string", example="2026-02-07"),
     * @OA\Property(property="summary", type="object",
     * @OA\Property(property="total_treated", type="integer", example=15),
     * @OA\Property(property="total_closed", type="integer", example=10),
     * @OA\Property(property="total_in_progress", type="integer", example=5),
     * @OA\Property(property="total_time_seconds", type="integer", example=3600),
     * @OA\Property(property="avg_time_per_call", type="string", example="00:04:00")
     * ),
     * @OA\Property(property="graphs", type="object",
     * @OA\Property(property="by_status", type="object"),
     * @OA\Property(property="by_type", type="object")
     * ),
     * @OA\Property(property="calls", type="array", @OA\Items(type="object"))
     * )
     * )
     * )
     * )
     */
    public function dailyActivity(Request $request)
    {
        try {
            $agentId = Auth::id();
            $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::today();
            
            // 1. Fetch Calls for the Agent on specific date
            // We look at calls created OR updated (worked on) by the agent on that date
            // For strict "Daily Activity", usually we look at 'updated_at' filtering
            $calls = Call::where('assigned_to', $agentId)
                ->whereDate('updated_at', $date) // Use updated_at to capture activity
                ->with(['client', 'department'])
                ->orderBy('updated_at', 'desc')
                ->get();

            // 2. Calculate KPIs (Summary)
            $totalTreated = $calls->count();
            $closed = $calls->whereIn('status', ['cloture', 'resolu'])->count();
            $inProgress = $calls->whereIn('status', ['en_cours', 'en_attente'])->count();
            
            // Calculate Times (assuming 'treatment_time_seconds' or 'call_duration_seconds' exists)
            // If you don't have treatment_time tracked yet, we default to 0
            $totalTimeSeconds = $calls->sum('treatment_time_seconds') + $calls->sum('call_duration_seconds');
            
            $avgTime = $totalTreated > 0 ? round($totalTimeSeconds / $totalTreated) : 0;

            // 3. Prepare Graph Data
            $byStatus = $calls->groupBy('status')->map->count();
            $byType = $calls->groupBy('type')->map->count();

            // 4. Format the List for display
            $formattedCalls = $calls->map(function($call) {
                return [
                    'id' => $call->id,
                    'time' => $call->updated_at->format('H:i'), // "Heure" column
                    'type' => $call->type,
                    'caller' => $call->client ? $call->client->name : ($call->caller_name ?? $call->phone_number),
                    'object' => $call->object,
                    'status' => $call->status,
                    'duration' => $call->call_duration_seconds ?? 0,
                ];
            });

            return $this->successResponse([
                'date' => $date->toDateString(),
                'summary' => [
                    'total_treated' => $totalTreated,
                    'total_closed' => $closed,
                    'total_in_progress' => $inProgress,
                    'total_time_seconds' => $totalTimeSeconds,
                    'avg_time_per_call' => gmdate("H:i:s", $avgTime) // Format 00:04:00
                ],
                'graphs' => [
                    'by_status' => $byStatus,
                    'by_type' => $byType
                ],
                'calls' => $formattedCalls
            ], 'Activité quotidienne récupérée');

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur lors de la récupération du rapport', 500);
        }
    }
}