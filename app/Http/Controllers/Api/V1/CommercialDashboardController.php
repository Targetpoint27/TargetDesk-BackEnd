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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Commercial Dashboard",
 *     description="API endpoints for commercial dashboard analytics and metrics (Optimized for Production)"
 * )
 */
class CommercialDashboardController extends Controller
{
    /**
     * Get commercial dashboard overview with optimizations
     */
    public function overview(Request $request)
    {
        try {
            $period = $request->get('period', 'month');
            $dates = $this->calculatePeriodDates($period, $request);

            // Cache key for this specific request
            $cacheKey = "dashboard_overview_{$period}_" . md5($dates['start'] . $dates['end']);

            $metrics = Cache::remember($cacheKey, 300, function () use ($dates) { // 5 min cache
                // Optimized single query for multiple metrics
                $clientCounts = DB::select("
                    SELECT
                        COUNT(*) as total_clients,
                        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_clients,
                        SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_clients_period
                    FROM clients
                ", [$dates['start'], $dates['end']]);

                $supplierCount = DB::select("
                    SELECT COUNT(*) as total_suppliers
                    FROM suppliers
                    WHERE is_active = 1
                ");

                // Prospects = clients without any interactions
                $prospectCount = DB::select("
                    SELECT COUNT(DISTINCT c.id) as total_prospects
                    FROM clients c
                    WHERE c.is_active = 1
                    AND NOT EXISTS (SELECT 1 FROM call_logs cl WHERE cl.client_id = c.id)
                    AND NOT EXISTS (SELECT 1 FROM appointments a WHERE a.client_id = c.id)
                    AND NOT EXISTS (SELECT 1 FROM client_notes cn WHERE cn.client_id = c.id)
                ");

                return [
                    'total_active_clients' => (int) $clientCounts[0]->active_clients,
                    'total_prospects' => (int) $prospectCount[0]->total_prospects,
                    'new_clients_this_period' => (int) $clientCounts[0]->new_clients_period,
                    'total_suppliers' => (int) $supplierCount[0]->total_suppliers
                ];
            });

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
            Log::error('Dashboard overview error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données du tableau de bord',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Get detailed commercial statistics with caching
     */
    public function stats(Request $request)
    {
        try {
            $period = $request->get('period', 'month');
            $dates = $this->calculatePeriodDates($period, $request);

            $cacheKey = "dashboard_stats_{$period}_" . md5($dates['start'] . $dates['end']);

            $stats = Cache::remember($cacheKey, 600, function () { // 10 min cache
                // Optimized single query for client statistics
                $clientStats = DB::select("
                    SELECT
                        COUNT(*) as total_clients,
                        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_clients,
                        SUM(CASE WHEN type = 'entreprise' THEN 1 ELSE 0 END) as entreprise_clients,
                        SUM(CASE WHEN type = 'particulier' THEN 1 ELSE 0 END) as particulier_clients
                    FROM clients
                ");

                $total = (int) $clientStats[0]->total_clients;
                $active = (int) $clientStats[0]->active_clients;
                $entreprise = (int) $clientStats[0]->entreprise_clients;
                $particulier = (int) $clientStats[0]->particulier_clients;

                // Client distribution by status
                $clientsByStatus = [
                    [
                        'status' => 'active',
                        'count' => $active,
                        'percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0
                    ],
                    [
                        'status' => 'inactive',
                        'count' => $total - $active,
                        'percentage' => $total > 0 ? round((($total - $active) / $total) * 100, 2) : 0
                    ]
                ];

                // Client distribution by type
                $clientsByType = [
                    [
                        'type' => 'particulier',
                        'count' => $particulier,
                        'percentage' => $total > 0 ? round(($particulier / $total) * 100, 2) : 0
                    ],
                    [
                        'type' => 'entreprise',
                        'count' => $entreprise,
                        'percentage' => $total > 0 ? round(($entreprise / $total) * 100, 2) : 0
                    ]
                ];

                // Client distribution by sector (optimized)
                $sectorStats = DB::select("
                    SELECT
                        sector,
                        COUNT(*) as count
                    FROM clients
                    WHERE sector IS NOT NULL AND sector != ''
                    GROUP BY sector
                    ORDER BY count DESC
                    LIMIT 10
                ");

                $clientsBySector = collect($sectorStats)->map(function ($sector) use ($total) {
                    return [
                        'sector' => $sector->sector,
                        'count' => (int) $sector->count,
                        'percentage' => $total > 0 ? round(($sector->count / $total) * 100, 2) : 0
                    ];
                })->toArray();

                return [
                    'clients_by_status' => $clientsByStatus,
                    'clients_by_type' => $clientsByType,
                    'clients_by_sector' => $clientsBySector
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Commercial stats retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard stats error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Get clients evolution with optimizations
     */
    public function clientsEvolution(Request $request)
    {
        try {
            $period = $request->get('period', 'month');
            $dates = $this->calculatePeriodDates($period, $request);

            $cacheKey = "dashboard_evolution_{$period}_" . md5($dates['start'] . $dates['end']);

            $evolution = Cache::remember($cacheKey, 900, function () use ($dates) { // 15 min cache
                // Optimized query for client evolution
                $evolutionData = DB::select("
                    SELECT
                        DATE(created_at) as date,
                        COUNT(*) as new_clients,
                        SUM(CASE WHEN type = 'entreprise' THEN 1 ELSE 0 END) as new_entreprises,
                        SUM(CASE WHEN type = 'particulier' THEN 1 ELSE 0 END) as new_particuliers
                    FROM clients
                    WHERE created_at BETWEEN ? AND ?
                    GROUP BY DATE(created_at)
                    ORDER BY date
                ", [$dates['start'], $dates['end']]);

                return collect($evolutionData)->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'new_clients' => (int) $item->new_clients,
                        'new_entreprises' => (int) $item->new_entreprises,
                        'new_particuliers' => (int) $item->new_particuliers
                    ];
                })->toArray();
            });

            return response()->json([
                'success' => true,
                'message' => 'Client evolution retrieved successfully',
                'data' => [
                    'evolution' => $evolution,
                    'period_info' => [
                        'period' => $period,
                        'start_date' => $dates['start']->format('Y-m-d'),
                        'end_date' => $dates['end']->format('Y-m-d')
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard evolution error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'évolution',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Get recent interactions with better performance
     */
    public function recentInteractions(Request $request)
    {
        try {
            $limit = min($request->get('limit', 10), 50); // Max 50 for performance

            $cacheKey = "dashboard_interactions_recent_{$limit}";

            $interactions = Cache::remember($cacheKey, 120, function () use ($limit) { // 2 min cache
                // Optimized union query for all interactions
                $interactions = DB::select("
                    (SELECT
                        cl.id,
                        'call' as type,
                        cl.client_id,
                        c.name as client_name,
                        cl.subject,
                        LEFT(cl.summary, 100) as summary,
                        cl.created_at,
                        u.name as created_by
                    FROM call_logs cl
                    JOIN clients c ON c.id = cl.client_id
                    LEFT JOIN users u ON u.id = cl.user_id
                    ORDER BY cl.created_at DESC
                    LIMIT {$limit})

                    UNION ALL

                    (SELECT
                        a.id,
                        'appointment' as type,
                        a.client_id,
                        c.name as client_name,
                        a.title as subject,
                        LEFT(a.description, 100) as summary,
                        a.created_at,
                        u.name as created_by
                    FROM appointments a
                    JOIN clients c ON c.id = a.client_id
                    LEFT JOIN users u ON u.id = a.user_id
                    ORDER BY a.created_at DESC
                    LIMIT {$limit})

                    UNION ALL

                    (SELECT
                        cn.id,
                        'note' as type,
                        cn.client_id,
                        c.name as client_name,
                        cn.title as subject,
                        LEFT(cn.content, 100) as summary,
                        cn.created_at,
                        u.name as created_by
                    FROM client_notes cn
                    JOIN clients c ON c.id = cn.client_id
                    LEFT JOIN users u ON u.id = cn.user_id
                    ORDER BY cn.created_at DESC
                    LIMIT {$limit})

                    ORDER BY created_at DESC
                    LIMIT {$limit}
                ");

                return collect($interactions)->map(function ($interaction) {
                    return [
                        'id' => (int) $interaction->id,
                        'type' => $interaction->type,
                        'client_id' => (int) $interaction->client_id,
                        'client_name' => $interaction->client_name,
                        'subject' => $interaction->subject,
                        'summary' => $interaction->summary ? $interaction->summary . '...' : null,
                        'created_at' => $interaction->created_at,
                        'created_by' => $interaction->created_by ?? 'Unknown'
                    ];
                })->toArray();
            });

            return response()->json([
                'success' => true,
                'message' => 'Recent interactions retrieved successfully',
                'data' => [
                    'interactions' => $interactions,
                    'total_found' => count($interactions)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard interactions error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des interactions',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * MOST OPTIMIZED: Get inactive clients with high performance query
     */
    public function inactiveClients(Request $request)
    {
        try {
            $days = min($request->get('days', 30), 180); // Max 180 days for performance
            $limit = min($request->get('limit', 20), 50); // Max 50 for performance
            $cutoffDate = Carbon::now()->subDays($days)->format('Y-m-d H:i:s');

            $cacheKey = "dashboard_inactive_{$days}_{$limit}";

            $result = Cache::remember($cacheKey, 1800, function () use ($cutoffDate, $limit, $days) { // 30 min cache
                // SUPER OPTIMIZED: Single query with LEFT JOINs instead of whereDoesntHave
                $inactiveClients = DB::select("
                    SELECT
                        c.id,
                        c.client_id,
                        c.name,
                        c.email,
                        c.phone,
                        c.type,
                        c.created_at,
                        MAX(COALESCE(cl.created_at, a.created_at, cn.created_at)) as last_interaction_date,
                        DATEDIFF(NOW(), COALESCE(MAX(COALESCE(cl.created_at, a.created_at, cn.created_at)), c.created_at)) as days_since_interaction
                    FROM clients c
                    LEFT JOIN call_logs cl ON cl.client_id = c.id AND cl.created_at >= ?
                    LEFT JOIN appointments a ON a.client_id = c.id AND a.created_at >= ?
                    LEFT JOIN client_notes cn ON cn.client_id = c.id AND cn.created_at >= ?
                    WHERE c.is_active = 1
                    GROUP BY c.id, c.client_id, c.name, c.email, c.phone, c.type, c.created_at
                    HAVING MAX(COALESCE(cl.created_at, a.created_at, cn.created_at)) IS NULL
                    ORDER BY c.created_at ASC
                    LIMIT ?
                ", [$cutoffDate, $cutoffDate, $cutoffDate, $limit]);

                $totalInactive = DB::select("
                    SELECT COUNT(DISTINCT c.id) as total
                    FROM clients c
                    LEFT JOIN call_logs cl ON cl.client_id = c.id AND cl.created_at >= ?
                    LEFT JOIN appointments a ON a.client_id = c.id AND a.created_at >= ?
                    LEFT JOIN client_notes cn ON cn.client_id = c.id AND cn.created_at >= ?
                    WHERE c.is_active = 1
                    GROUP BY c.id
                    HAVING MAX(COALESCE(cl.created_at, a.created_at, cn.created_at)) IS NULL
                ", [$cutoffDate, $cutoffDate, $cutoffDate]);

                $clientsData = collect($inactiveClients)->map(function ($client) {
                    return [
                        'id' => (int) $client->id,
                        'client_id' => $client->client_id,
                        'name' => $client->name,
                        'email' => $client->email,
                        'phone' => $client->phone,
                        'type' => $client->type,
                        'last_interaction_date' => $client->last_interaction_date,
                        'days_since_interaction' => (int) $client->days_since_interaction,
                        'created_at' => $client->created_at
                    ];
                })->toArray();

                return [
                    'clients' => $clientsData,
                    'total_inactive' => count($totalInactive),
                    'showing' => count($clientsData)
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Inactive clients retrieved successfully',
                'data' => array_merge($result, [
                    'days_threshold' => (string) $days
                ])
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard inactive clients error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'days' => $request->get('days', 30),
                'limit' => $request->get('limit', 20)
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des clients inactifs',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur'
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
            case 'quarter':
                return [
                    'start' => $now->copy()->firstOfQuarter(),
                    'end' => $now->copy()->lastOfQuarter()
                ];

            case 'year':
                return [
                    'start' => $now->copy()->firstOfYear(),
                    'end' => $now->copy()->lastOfYear()
                ];

            case 'custom':
                $start = $request->has('start_date')
                    ? Carbon::parse($request->start_date)
                    : $now->copy()->firstOfMonth();
                $end = $request->has('end_date')
                    ? Carbon::parse($request->end_date)
                    : $now->copy()->lastOfMonth();
                return ['start' => $start, 'end' => $end];

            case 'month':
            default:
                return [
                    'start' => $now->copy()->firstOfMonth(),
                    'end' => $now->copy()->lastOfMonth()
                ];
        }
    }
}