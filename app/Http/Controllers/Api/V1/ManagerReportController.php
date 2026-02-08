<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Call;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * @OA\Tag(
 * name="Manager",
 * description="Reporting stratégique pour les managers (Epic 9)"
 * )
 */
class ManagerReportController extends BaseApiController
{
    /**
     * @OA\Get(
     * path="/api/v1/call-center/manager/dashboard",
     * summary="Consulter le dashboard département (US-CC-050)",
     * description="Affiche les indicateurs clés globaux, les tendances et les motifs d'appels.",
     * tags={"Manager"},
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="period", in="query", @OA\Schema(type="string", enum={"week", "month", "quarter", "year"}), description="Défaut: month"),
     * @OA\Response(response=200, description="Dashboard manager récupéré")
     * )
     */
    public function dashboard(Request $request)
    {
        try {
            $deptId = Auth::user()->department_id;
            $period = $request->input('period', 'month');
            
            // 1. Determine Date Range
            $endDate = now();
            $startDate = now()->startOfMonth();
            
            if ($period === 'week') $startDate = now()->startOfWeek();
            elseif ($period === 'quarter') $startDate = now()->startOfQuarter();
            elseif ($period === 'year') $startDate = now()->startOfYear();

            // 2. Base Query
            $query = Call::where('department_id', $deptId)
                         ->whereBetween('created_at', [$startDate, $endDate]);
            
            $calls = $query->get();
            $totalCalls = $calls->count();

            // 3. Calculate KPIs
            $missedCalls = $calls->where('status', 'a_rappeler')->count(); // Approx
            $resolvedCalls = $calls->where('status', 'resolue')->count();
            
            // Response Rate
            $responseRate = $totalCalls > 0 
                ? round((($totalCalls - $missedCalls) / $totalCalls) * 100, 1) 
                : 0;

            // Avg Treatment Time (only for calls that have duration)
            $avgTimeSeconds = $calls->whereNotNull('treatment_time_seconds')->avg('treatment_time_seconds');
            
            // Complaints in this period (linked to calls in this dept)
            $complaintCount = Complaint::whereBetween('created_at', [$startDate, $endDate])
                                     ->whereHas('call', fn($q) => $q->where('department_id', $deptId))
                                     ->count();

            // 4. Top 5 Motifs (Objects)
            $topMotifs = $calls->groupBy('object')
                               ->map(fn($group) => $group->count())
                               ->sortDesc()
                               ->take(5);

            // 5. Graphs: Volume Evolution (Group by Day)
            $evolution = $calls->groupBy(fn($c) => $c->created_at->format('Y-m-d'))
                               ->map(fn($group) => $group->count());

            // 6. Heatmap: Calls by Hour of Day
            $heatmap = $calls->groupBy(fn($c) => $c->created_at->format('H'))
                             ->map(fn($group) => $group->count())
                             ->sortKeys();

            return $this->successResponse([
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString()
                ],
                'kpis' => [
                    'total_volume' => $totalCalls,
                    'response_rate' => $responseRate . '%',
                    'avg_treatment_time' => $avgTimeSeconds ? gmdate("H:i:s", $avgTimeSeconds) : "00:00:00",
                    'complaint_count' => $complaintCount,
                    'resolution_rate' => $totalCalls > 0 ? round(($resolvedCalls / $totalCalls) * 100, 1) . '%' : '0%'
                ],
                'top_motifs' => $topMotifs,
                'graphs' => [
                    'evolution' => $evolution,
                    'heatmap_hours' => $heatmap,
                    'distribution_type' => $calls->groupBy('type')->map->count()
                ]
            ], 'Dashboard manager récupéré', 200);

        } catch (\Exception $e) {
            return $this->errorResponse('Erreur dashboard manager', 500);
        }
    }
}