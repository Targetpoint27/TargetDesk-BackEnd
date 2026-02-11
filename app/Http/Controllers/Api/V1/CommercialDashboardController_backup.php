<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\Category;
use App\Models\CallLog;
use App\Models\Appointment;
use App\Models\ClientNote;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Commercial Dashboard",
 *     description="API endpoints for commercial dashboard analytics and metrics"
 * )
 */
class CommercialDashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/dashboard/commercial/overview",
     *     summary="Get commercial dashboard overview",
     *     description="Returns key metrics for commercial dashboard including total clients, prospects, and monthly stats",
     *     operationId="getCommercialOverview",
     *     tags={"Commercial Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Time period for analysis (month, quarter, year, custom)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"month", "quarter", "year", "custom"},
     *             default="month"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date for custom period (Y-m-d format)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date for custom period (Y-m-d format)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Commercial dashboard overview data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dashboard overview retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="metrics",
     *                     type="object",
     *                     @OA\Property(property="total_active_clients", type="integer", example=150),
     *                     @OA\Property(property="total_prospects", type="integer", example=45),
     *                     @OA\Property(property="new_clients_this_period", type="integer", example=12),
     *                     @OA\Property(property="total_suppliers", type="integer", example=25)
     *                 ),
     *                 @OA\Property(
     *                     property="period_info",
     *                     type="object",
     *                     @OA\Property(property="period", type="string", example="month"),
     *                     @OA\Property(property="start_date", type="string", example="2026-01-01"),
     *                     @OA\Property(property="end_date", type="string", example="2026-01-31")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function overview(Request $request)
    {
        try {
            $period = $request->get('period', 'month');
            $dates = $this->calculatePeriodDates($period, $request);

            $metrics = [
                'total_active_clients' => Client::where('is_active', true)->count(),
                'total_prospects' => Client::where('is_active', true)
                    ->whereDoesntHave('callLogs')
                    ->whereDoesntHave('appointments')
                    ->count(),
                'new_clients_this_period' => Client::whereBetween('created_at', [$dates['start'], $dates['end']])->count(),
                'total_suppliers' => Supplier::where('is_active', true)->count()
            ];

            return response()->json([
                'success' => true,
                'message' => 'Dashboard overview retrieved successfully',
                'data' => [
                    'metrics' => $metrics,
                    'period_info' => [
                        'period' => $period,
                        'start_date' => $dates['start']->format('Y-m-d'),
                        'end_date' => $dates['end']->format('Y-m-d')
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard overview',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dashboard/commercial/stats",
     *     summary="Get detailed commercial statistics",
     *     description="Returns detailed statistics including client distribution by status, category, and sector",
     *     operationId="getCommercialStats",
     *     tags={"Commercial Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Time period for analysis",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"month", "quarter", "year", "custom"},
     *             default="month"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detailed commercial statistics",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Commercial stats retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="clients_by_status",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="status", type="string", example="active"),
     *                         @OA\Property(property="count", type="integer", example=120),
     *                         @OA\Property(property="percentage", type="number", example=80.5)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="clients_by_type",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="type", type="string", example="entreprise"),
     *                         @OA\Property(property="count", type="integer", example=85),
     *                         @OA\Property(property="percentage", type="number", example=60.7)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="clients_by_sector",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="sector", type="string", example="Technology"),
     *                         @OA\Property(property="count", type="integer", example=25),
     *                         @OA\Property(property="percentage", type="number", example=17.8)
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function stats(Request $request)
    {
        try {
            $period = $request->get('period', 'month');
            $dates = $this->calculatePeriodDates($period, $request);

            // Client distribution by status
            $totalClients = Client::count();
            $activeClients = Client::where('is_active', true)->count();
            $inactiveClients = $totalClients - $activeClients;

            $clientsByStatus = [
                [
                    'status' => 'active',
                    'count' => $activeClients,
                    'percentage' => $totalClients > 0 ? round(($activeClients / $totalClients) * 100, 1) : 0
                ],
                [
                    'status' => 'inactive',
                    'count' => $inactiveClients,
                    'percentage' => $totalClients > 0 ? round(($inactiveClients / $totalClients) * 100, 1) : 0
                ]
            ];

            // Client distribution by type
            $clientsByType = Client::select('type', DB::raw('COUNT(*) as count'))
                ->groupBy('type')
                ->get()
                ->map(function ($item) use ($totalClients) {
                    return [
                        'type' => $item->type,
                        'count' => $item->count,
                        'percentage' => $totalClients > 0 ? round(($item->count / $totalClients) * 100, 1) : 0
                    ];
                });

            // Client distribution by sector
            $clientsBySector = Client::select('sector', DB::raw('COUNT(*) as count'))
                ->whereNotNull('sector')
                ->where('sector', '!=', '')
                ->groupBy('sector')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->map(function ($item) use ($totalClients) {
                    return [
                        'sector' => $item->sector,
                        'count' => $item->count,
                        'percentage' => $totalClients > 0 ? round(($item->count / $totalClients) * 100, 1) : 0
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Commercial stats retrieved successfully',
                'data' => [
                    'clients_by_status' => $clientsByStatus,
                    'clients_by_type' => $clientsByType,
                    'clients_by_sector' => $clientsBySector
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving commercial stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dashboard/commercial/clients-evolution",
     *     summary="Get client evolution over time",
     *     description="Returns client count evolution data for charts and graphs",
     *     operationId="getClientsEvolution",
     *     tags={"Commercial Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Time period for evolution analysis",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"month", "quarter", "year"},
     *             default="month"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client evolution data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Client evolution data retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="evolution_data",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="date", type="string", example="2026-01-01"),
     *                         @OA\Property(property="total_clients", type="integer", example=145),
     *                         @OA\Property(property="new_clients", type="integer", example=5),
     *                         @OA\Property(property="active_clients", type="integer", example=140)
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function clientsEvolution(Request $request)
    {
        try {
            $period = $request->get('period', 'month');
            $evolutionData = [];

            switch ($period) {
                case 'month':
                    $startDate = Carbon::now()->startOfMonth()->subMonths(11);
                    $endDate = Carbon::now()->endOfMonth();
                    $groupBy = '%Y-%m';
                    $format = 'Y-m';
                    break;
                case 'quarter':
                    $startDate = Carbon::now()->startOfQuarter()->subQuarters(3);
                    $endDate = Carbon::now()->endOfQuarter();
                    $groupBy = '%Y-%m';
                    $format = 'Y-m';
                    break;
                case 'year':
                    $startDate = Carbon::now()->startOfYear()->subYears(4);
                    $endDate = Carbon::now()->endOfYear();
                    $groupBy = '%Y';
                    $format = 'Y';
                    break;
                default:
                    $startDate = Carbon::now()->startOfMonth()->subMonths(11);
                    $endDate = Carbon::now()->endOfMonth();
                    $groupBy = '%Y-%m';
                    $format = 'Y-m';
            }

            // Get cumulative client count and new clients per period
            $clients = Client::select(
                DB::raw("DATE_FORMAT(created_at, '{$groupBy}') as period"),
                DB::raw('COUNT(*) as new_clients')
            )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            $current = $startDate->copy();
            $totalClients = Client::where('created_at', '<', $startDate)->count();

            while ($current <= $endDate) {
                $periodStr = $current->format($format);
                $newClients = $clients->where('period', $periodStr)->first();
                $newClientsCount = $newClients ? $newClients->new_clients : 0;
                $totalClients += $newClientsCount;

                $activeClientsCount = Client::where('created_at', '<=', $current->copy()->endOfMonth())
                    ->where('is_active', true)
                    ->count();

                $evolutionData[] = [
                    'date' => $current->format('Y-m-d'),
                    'period' => $periodStr,
                    'total_clients' => $totalClients,
                    'new_clients' => $newClientsCount,
                    'active_clients' => $activeClientsCount
                ];

                if ($period === 'year') {
                    $current->addYear();
                } else {
                    $current->addMonth();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Client evolution data retrieved successfully',
                'data' => [
                    'evolution_data' => $evolutionData,
                    'period' => $period,
                    'date_range' => [
                        'start' => $startDate->format('Y-m-d'),
                        'end' => $endDate->format('Y-m-d')
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving client evolution data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dashboard/commercial/interactions/recent",
     *     summary="Get recent interactions",
     *     description="Returns recent interactions across all clients including calls, appointments, and notes",
     *     operationId="getRecentInteractions",
     *     tags={"Commercial Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Maximum number of interactions to return",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, maximum=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Recent interactions data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Recent interactions retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="interactions",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="type", type="string", example="call"),
     *                         @OA\Property(property="client_name", type="string", example="Entreprise ACME"),
     *                         @OA\Property(property="subject", type="string", example="Follow-up call"),
     *                         @OA\Property(property="created_at", type="string", example="2026-01-26T10:30:00Z"),
     *                         @OA\Property(property="created_by", type="string", example="John Doe")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function recentInteractions(Request $request)
    {
        try {
            $limit = min($request->get('limit', 10), 50);
            $interactions = [];

            // Get recent call logs
            $calls = CallLog::with(['client', 'creator'])
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($call) {
                    return [
                        'id' => $call->id,
                        'type' => 'call',
                        'client_id' => $call->client_id,
                        'client_name' => $call->client->name,
                        'subject' => $call->subject,
                        'summary' => $call->summary ? substr($call->summary, 0, 100) . '...' : null,
                        'created_at' => $call->created_at,
                        'created_by' => $call->creator->name ?? 'Unknown'
                    ];
                });

            // Get recent appointments
            $appointments = Appointment::with(['client', 'creator'])
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($appointment) {
                    return [
                        'id' => $appointment->id,
                        'type' => 'appointment',
                        'client_id' => $appointment->client_id,
                        'client_name' => $appointment->client->name,
                        'subject' => $appointment->subject,
                        'summary' => $appointment->description ? substr($appointment->description, 0, 100) . '...' : null,
                        'created_at' => $appointment->created_at,
                        'created_by' => $appointment->creator->name ?? 'Unknown'
                    ];
                });

            // Get recent notes
            $notes = ClientNote::with(['client', 'creator'])
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($note) {
                    return [
                        'id' => $note->id,
                        'type' => 'note',
                        'client_id' => $note->client_id,
                        'client_name' => $note->client->name,
                        'subject' => $note->title,
                        'summary' => $note->content ? substr(strip_tags($note->content), 0, 100) . '...' : null,
                        'created_at' => $note->created_at,
                        'created_by' => $note->creator->name ?? 'Unknown'
                    ];
                });

            // Merge and sort all interactions
            $allInteractions = $calls->concat($appointments)->concat($notes)
                ->sortByDesc('created_at')
                ->take($limit)
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Recent interactions retrieved successfully',
                'data' => [
                    'interactions' => $allInteractions,
                    'total_found' => $allInteractions->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving recent interactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dashboard/commercial/clients/inactive",
     *     summary="Get clients without recent interactions",
     *     description="Returns clients who haven't had any interactions for a specified number of days",
     *     operationId="getInactiveClients",
     *     tags={"Commercial Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="Number of days without interaction",
     *         required=false,
     *         @OA\Schema(type="integer", default=30)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Maximum number of clients to return",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Inactive clients data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Inactive clients retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="clients",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="client_id", type="string", example="CLI-E76E2DC4GQ"),
     *                         @OA\Property(property="name", type="string", example="Entreprise ACME"),
     *                         @OA\Property(property="email", type="string", example="contact@acme.com"),
     *                         @OA\Property(property="last_interaction_date", type="string", example="2025-12-15T14:30:00Z"),
     *                         @OA\Property(property="days_since_interaction", type="integer", example=42)
     *                     )
     *                 ),
     *                 @OA\Property(property="total_inactive", type="integer", example=15),
     *                 @OA\Property(property="days_threshold", type="integer", example=30)
     *             )
     *         )
     *     )
     * )
     */
    public function inactiveClients(Request $request)
    {
        try {
            $days = $request->get('days', 30);
            $limit = min($request->get('limit', 20), 100);
            $cutoffDate = Carbon::now()->subDays($days);

            $inactiveClients = Client::where('is_active', true)
                ->whereDoesntHave('callLogs', function ($query) use ($cutoffDate) {
                    $query->where('created_at', '>=', $cutoffDate);
                })
                ->whereDoesntHave('appointments', function ($query) use ($cutoffDate) {
                    $query->where('created_at', '>=', $cutoffDate);
                })
                ->whereDoesntHave('notes', function ($query) use ($cutoffDate) {
                    $query->where('created_at', '>=', $cutoffDate);
                })
                ->orderBy('created_at', 'asc')
                ->limit($limit)
                ->get();

            $clientsData = $inactiveClients->map(function ($client) use ($days) {
                $lastInteractionDate = null;
                $daysSinceInteraction = null;

                // Find the most recent interaction by querying relationships directly
                $lastCall = $client->callLogs()->latest()->first();
                $lastAppointment = $client->appointments()->latest()->first();
                $lastNote = $client->notes()->latest()->first();

                $interactions = collect([$lastCall, $lastAppointment, $lastNote])
                    ->filter()
                    ->sortByDesc('created_at')
                    ->first();

                if ($interactions) {
                    $lastInteractionDate = $interactions->created_at;
                    $daysSinceInteraction = Carbon::parse($lastInteractionDate)->diffInDays(Carbon::now());
                } else {
                    $daysSinceInteraction = Carbon::parse($client->created_at)->diffInDays(Carbon::now());
                }

                return [
                    'id' => $client->id,
                    'client_id' => $client->client_id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'type' => $client->type,
                    'last_interaction_date' => $lastInteractionDate,
                    'days_since_interaction' => $daysSinceInteraction,
                    'created_at' => $client->created_at
                ];
            });

            $totalInactive = Client::where('is_active', true)
                ->whereDoesntHave('callLogs', function ($query) use ($cutoffDate) {
                    $query->where('created_at', '>=', $cutoffDate);
                })
                ->whereDoesntHave('appointments', function ($query) use ($cutoffDate) {
                    $query->where('created_at', '>=', $cutoffDate);
                })
                ->whereDoesntHave('notes', function ($query) use ($cutoffDate) {
                    $query->where('created_at', '>=', $cutoffDate);
                })
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Inactive clients retrieved successfully',
                'data' => [
                    'clients' => $clientsData,
                    'total_inactive' => $totalInactive,
                    'days_threshold' => $days,
                    'showing' => $clientsData->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving inactive clients',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate period dates based on request parameters
     */
    private function calculatePeriodDates($period, $request)
    {
        $now = Carbon::now();

        switch ($period) {
            case 'month':
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth()
                ];
            case 'quarter':
                return [
                    'start' => $now->copy()->startOfQuarter(),
                    'end' => $now->copy()->endOfQuarter()
                ];
            case 'year':
                return [
                    'start' => $now->copy()->startOfYear(),
                    'end' => $now->copy()->endOfYear()
                ];
            case 'custom':
                $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : $now->copy()->startOfMonth();
                $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : $now->copy()->endOfMonth();
                return [
                    'start' => $startDate->startOfDay(),
                    'end' => $endDate->endOfDay()
                ];
            default:
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth()
                ];
        }
    }
}
