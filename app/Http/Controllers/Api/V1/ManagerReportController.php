<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Call;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * @OA\Tag(
 * name="Manager Reporting",
 * description="Strategic reporting and analytics for department managers (Epic 9)"
 * )
 */
class ManagerReportController extends BaseApiController
{
    /**
     * Helper to get date ranges for current and previous periods
     */
    private function getPeriodDates($period)
    {
        $end = now();
        $start = now()->startOfMonth();

        if ($period === 'week') $start = now()->startOfWeek();
        elseif ($period === 'quarter') $start = now()->startOfQuarter();
        elseif ($period === 'year') $start = now()->startOfYear();

        $durationInDays = $start->diffInDays($end);
        $prevEnd = $start->copy()->subSecond();
        $prevStart = $prevEnd->copy()->subDays($durationInDays);

        return [$start, $end, $prevStart, $prevEnd];
    }

    /**
     * @OA\Get(
     * path="/api/v1/call-center/manager/dashboard",
     * summary="Consulter le dashboard stratégique (US-CC-050)",
     * description="Récupère les KPIs principaux avec une comparaison automatique par rapport à la période précédente.",
     * tags={"Manager Reporting"},
     * security={{"sanctum":{}}},
     * @OA\Parameter(
     * name="period",
     * in="query",
     * required=false,
     * description="Période d'analyse",
     * @OA\Schema(type="string", enum={"week", "month", "quarter", "year"}, default="month")
     * ),
     * @OA\Response(
     * response=200,
     * description="Dashboard récupéré avec succès",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="data", type="object",
     * @OA\Property(property="kpis", type="object",
     * @OA\Property(property="total_volume", type="object",
     * @OA\Property(property="current", type="integer"),
     * @OA\Property(property="previous", type="integer"),
     * @OA\Property(property="evolution", type="number", format="float")
     * )
     * )
     * )
     * )
     * )
     * )
     */
    public function dashboard(Request $request)
    {
        try {
            $deptId = Auth::user()->department_id;
            $period = $request->input('period', 'month');
            [$start, $end, $prevStart, $prevEnd] = $this->getPeriodDates($period);

            $calls = Call::where('department_id', $deptId)->whereBetween('created_at', [$start, $end])->get();
            $total = $calls->count();
            
            $prevTotal = Call::where('department_id', $deptId)->whereBetween('created_at', [$prevStart, $prevEnd])->count();

            $resolved = $calls->where('status', 'resolue')->count();
            $missed = $calls->where('status', 'a_rappeler')->count();

            return $this->successResponse([
                'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
                'kpis' => [
                    'total_volume' => [
                        'current' => $total,
                        'previous' => $prevTotal,
                        'evolution' => $prevTotal > 0 ? round((($total - $prevTotal) / $prevTotal) * 100, 1) : 0
                    ],
                    'response_rate' => $total > 0 ? round((($total - $missed) / $total) * 100, 1) . '%' : '0%',
                    'resolution_rate' => $total > 0 ? round(($resolved / $total) * 100, 1) . '%' : '0%'
                ],
                'graphs' => [
                    'evolution' => $calls->groupBy(fn($c) => $c->created_at->format('Y-m-d'))->map->count(),
                    'distribution_type' => $calls->groupBy('type')->map->count()
                ]
            ], 'Dashboard manager récupéré');
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur dashboard: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     * path="/api/v1/call-center/manager/reports/performance",
     * summary="Performance par équipe et agent (US-CC-050)",
     * description="Analyse de la charge de travail et de la performance individuelle des agents du département.",
     * tags={"Manager Reporting"},
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="period", in="query", @OA\Schema(type="string", enum={"week", "month", "quarter", "year"})),
     * @OA\Response(
     * response=200,
     * description="Statistiques de performance récupérées"
     * )
     * )
     */
    public function performanceReport(Request $request)
    {
        try {
            $deptId = Auth::user()->department_id;
            $period = $request->input('period', 'month');
            [$start, $end] = $this->getPeriodDates($period);

            $performance = User::where('department_id', $deptId)
                ->withCount(['assignedCalls as total_calls' => function($q) use ($start, $end) {
                    $q->whereBetween('created_at', [$start, $end]);
                }])
                ->get()
                ->map(fn($user) => [
                    'agent_id' => $user->id,
                    'name' => $user->name,
                    'calls_count' => $user->total_calls,
                    'load_status' => $user->total_calls > 20 ? 'rouge' : ($user->total_calls > 10 ? 'orange' : 'vert')
                ]);

            return $this->successResponse($performance, 'Rapport de performance récupéré');
        } catch (\Exception $e) {
            return $this->errorResponse('Erreur performance', 500);
        }
    }

    /**
     * @OA\Get(
     * path="/api/v1/call-center/manager/reports/heatmap",
     * summary="Heatmap des heures d'affluence (US-CC-050)",
     * description="Identifie les pics d'appels par heure pour optimiser la planification des ressources.",
     * tags={"Manager Reporting"},
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="period", in="query", @OA\Schema(type="string", enum={"week", "month", "quarter", "year"})),
     * @OA\Response(
     * response=200,
     * description="Heatmap horaire générée"
     * )
     * )
     */
    public function heatmapReport(Request $request)
    {
        $deptId = Auth::user()->department_id;
        $period = $request->input('period', 'month');
        [$start, $end] = $this->getPeriodDates($period);

        $hours = Call::where('department_id', $deptId)
            ->whereBetween('created_at', [$start, $end])
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return $this->successResponse($hours, 'Heatmap récupérée');
    }

    /**
     * @OA\Get(
     * path="/api/v1/call-center/manager/reports/motifs",
     * summary="Top Motifs et Satisfaction Client (US-CC-050)",
     * description="Analyse des motifs d'appels les plus fréquents et distribution de la satisfaction client via les réclamations.",
     * tags={"Manager Reporting"},
     * security={{"sanctum":{}}},
     * @OA\Parameter(name="period", in="query", @OA\Schema(type="string", enum={"week", "month", "quarter", "year"})),
     * @OA\Response(
     * response=200,
     * description="Analyse des motifs et satisfaction récupérée"
     * )
     * )
     */
    public function motifsReport(Request $request)
    {
        $deptId = Auth::user()->department_id;
        $period = $request->input('period', 'month');
        [$start, $end] = $this->getPeriodDates($period);

        $motifs = Call::where('department_id', $deptId)
            ->whereBetween('created_at', [$start, $end])
            ->select('object', DB::raw('count(*) as count'))
            ->groupBy('object')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $satisfaction = Complaint::whereHas('call', fn($q) => $q->where('department_id', $deptId))
            ->whereBetween('created_at', [$start, $end])
            ->select('client_satisfaction', DB::raw('count(*) as count'))
            ->groupBy('client_satisfaction')
            ->get();

        return $this->successResponse([
            'top_motifs' => $motifs,
            'satisfaction_stats' => $satisfaction
        ], 'Analyse des motifs récupérée');
    }
}