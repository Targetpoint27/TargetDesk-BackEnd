<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CallLog;
use App\Models\Appointment;
use App\Models\ClientNote;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Personal Dashboard",
 *     description="API endpoints for personal dashboard for individual users"
 * )
 */
class PersonalDashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/dashboard/personal/overview",
     *     summary="Get personal dashboard overview",
     *     description="Returns personal metrics for the authenticated user including their clients, appointments, and tasks",
     *     operationId="getPersonalOverview",
     *     tags={"Personal Dashboard"},
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
     *         description="Personal dashboard overview data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Personal dashboard overview retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="metrics",
     *                     type="object",
     *                     @OA\Property(property="my_active_clients", type="integer", example=25),
     *                     @OA\Property(property="my_prospects", type="integer", example=8),
     *                     @OA\Property(property="my_appointments_upcoming", type="integer", example=5),
     *                     @OA\Property(property="my_overdue_tasks", type="integer", example=3),
     *                     @OA\Property(property="my_interactions_this_period", type="integer", example=15)
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
            $userId = auth()->id();
            $period = $request->get('period', 'month');
            $dates = $this->calculatePeriodDates($period, $request);

            $metrics = [
                'my_active_clients' => Client::where('created_by', $userId)
                    ->where('is_active', true)
                    ->count(),
                'my_prospects' => Client::where('created_by', $userId)
                    ->where('is_active', true)
                    ->whereDoesntHave('callLogs')
                    ->whereDoesntHave('appointments')
                    ->count(),
                'my_appointments_upcoming' => Appointment::where('user_id', $userId)
                    ->where('scheduled_at', '>', Carbon::now())
                    ->where('status', '!=', 'completed')
                    ->count(),
                'my_overdue_tasks' => Appointment::where('user_id', $userId)
                    ->where('scheduled_at', '<', Carbon::now())
                    ->where('status', 'scheduled')
                    ->count(),
                'my_interactions_this_period' => $this->getMyInteractionsCount($userId, $dates)
            ];

            return response()->json([
                'success' => true,
                'message' => 'Personal dashboard overview retrieved successfully',
                'data' => [
                    'metrics' => $metrics,
                    'period_info' => [
                        'period' => $period,
                        'start_date' => $dates['start']->format('Y-m-d'),
                        'end_date' => $dates['end']->format('Y-m-d')
                    ],
                    'user_id' => $userId
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving personal dashboard overview',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dashboard/personal/portfolio",
     *     summary="Get personal portfolio statistics",
     *     description="Returns portfolio evolution and performance metrics for the authenticated user",
     *     operationId="getPersonalPortfolio",
     *     tags={"Personal Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Time period for portfolio analysis",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"month", "quarter", "year"},
     *             default="month"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Personal portfolio data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Portfolio data retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="portfolio_evolution",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="date", type="string", example="2026-01-01"),
     *                         @OA\Property(property="clients_count", type="integer", example=20),
     *                         @OA\Property(property="new_clients", type="integer", example=2),
     *                         @OA\Property(property="interactions_count", type="integer", example=15)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="performance_metrics",
     *                     type="object",
     *                     @OA\Property(property="avg_interactions_per_client", type="number", example=2.5),
     *                     @OA\Property(property="most_active_day", type="string", example="Tuesday"),
     *                     @OA\Property(property="conversion_rate", type="number", example=15.5)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function portfolio(Request $request)
    {
        try {
            $userId = auth()->id();
            $period = $request->get('period', 'month');

            // Calculate date range based on period
            switch ($period) {
                case 'month':
                    $startDate = Carbon::now()->startOfMonth()->subMonths(11);
                    $endDate = Carbon::now()->endOfMonth();
                    $format = 'Y-m';
                    break;
                case 'quarter':
                    $startDate = Carbon::now()->startOfQuarter()->subQuarters(3);
                    $endDate = Carbon::now()->endOfQuarter();
                    $format = 'Y-m';
                    break;
                case 'year':
                    $startDate = Carbon::now()->startOfYear()->subYears(4);
                    $endDate = Carbon::now()->endOfYear();
                    $format = 'Y';
                    break;
                default:
                    $startDate = Carbon::now()->startOfMonth()->subMonths(11);
                    $endDate = Carbon::now()->endOfMonth();
                    $format = 'Y-m';
            }

            // Portfolio evolution data
            $portfolioEvolution = [];
            $current = $startDate->copy();

            while ($current <= $endDate) {
                $periodStart = $current->copy()->startOfMonth();
                $periodEnd = $current->copy()->endOfMonth();

                $clientsCount = Client::where('created_by', $userId)
                    ->where('created_at', '<=', $periodEnd)
                    ->count();

                $newClients = Client::where('created_by', $userId)
                    ->whereBetween('created_at', [$periodStart, $periodEnd])
                    ->count();

                $interactionsCount = $this->getMyInteractionsCount($userId, [
                    'start' => $periodStart,
                    'end' => $periodEnd
                ]);

                $portfolioEvolution[] = [
                    'date' => $current->format('Y-m-d'),
                    'period' => $current->format($format),
                    'clients_count' => $clientsCount,
                    'new_clients' => $newClients,
                    'interactions_count' => $interactionsCount
                ];

                if ($period === 'year') {
                    $current->addYear();
                } else {
                    $current->addMonth();
                }
            }

            // Performance metrics
            $totalClients = Client::where('created_by', $userId)->count();
            $totalInteractions = $this->getMyInteractionsCount($userId, [
                'start' => $startDate,
                'end' => $endDate
            ]);

            $avgInteractionsPerClient = $totalClients > 0 ? round($totalInteractions / $totalClients, 1) : 0;

            // Most active day of week
            $dayInteractions = CallLog::where('user_id', $userId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select(DB::raw('DAYNAME(created_at) as day_name, COUNT(*) as count'))
                ->groupBy('day_name')
                ->orderByDesc('count')
                ->first();

            $mostActiveDay = $dayInteractions ? $dayInteractions->day_name : 'N/A';

            // Conversion rate (clients with interactions vs total clients)
            $clientsWithInteractions = Client::where('created_by', $userId)
                ->where(function ($query) {
                    $query->whereHas('callLogs')
                        ->orWhereHas('appointments')
                        ->orWhereHas('notes');
                })
                ->count();

            $conversionRate = $totalClients > 0 ? round(($clientsWithInteractions / $totalClients) * 100, 1) : 0;

            return response()->json([
                'success' => true,
                'message' => 'Portfolio data retrieved successfully',
                'data' => [
                    'portfolio_evolution' => $portfolioEvolution,
                    'performance_metrics' => [
                        'avg_interactions_per_client' => $avgInteractionsPerClient,
                        'most_active_day' => $mostActiveDay,
                        'conversion_rate' => $conversionRate,
                        'total_clients' => $totalClients,
                        'clients_with_interactions' => $clientsWithInteractions
                    ],
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
                'message' => 'Error retrieving portfolio data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dashboard/personal/tasks/today",
     *     summary="Get today's tasks and appointments",
     *     description="Returns tasks, appointments and follow-ups for today for the authenticated user",
     *     operationId="getTodaysTasks",
     *     tags={"Personal Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Today's tasks and appointments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Today's tasks retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="appointments_today",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="client_name", type="string", example="Entreprise ACME"),
     *                         @OA\Property(property="subject", type="string", example="Product demo"),
     *                         @OA\Property(property="scheduled_date", type="string", example="2026-01-26T14:00:00Z"),
     *                         @OA\Property(property="status", type="string", example="scheduled"),
     *                         @OA\Property(property="priority", type="string", example="high")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="follow_ups_due",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="client_name", type="string", example="Tech Solutions"),
     *                         @OA\Property(property="subject", type="string", example="Follow up on proposal"),
     *                         @OA\Property(property="follow_up_date", type="string", example="2026-01-26T09:00:00Z"),
     *                         @OA\Property(property="days_overdue", type="integer", example=2)
     *                     )
     *                 ),
     *                 @OA\Property(property="summary", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function tasksToday(Request $request)
    {
        try {
            $userId = auth()->id();
            $today = Carbon::today();
            $now = Carbon::now();

            // Today's appointments
            $appointmentsToday = Appointment::where('user_id', $userId)
                ->whereDate('scheduled_at', $today)
                ->with('client')
                ->orderBy('scheduled_at')
                ->get()
                ->map(function ($appointment) {
                    return [
                        'id' => $appointment->id,
                        'client_id' => $appointment->client_id,
                        'client_name' => $appointment->client->name,
                        'subject' => $appointment->subject,
                        'description' => $appointment->description,
                        'scheduled_at' => $appointment->scheduled_at,
                        'status' => $appointment->status,
                        'priority' => $appointment->priority ?? 'normal',
                        'location' => $appointment->location,
                        'type' => $appointment->type ?? 'meeting'
                    ];
                });

            // Overdue follow-ups
            $followUpsDue = CallLog::where('user_id', $userId)
                ->whereNotNull('follow_up_date')
                ->where('follow_up_date', '<=', $now)
                ->with('client')
                ->orderBy('follow_up_date')
                ->get()
                ->map(function ($call) use ($now) {
                    $daysOverdue = Carbon::parse($call->follow_up_date)->diffInDays($now);
                    return [
                        'id' => $call->id,
                        'client_id' => $call->client_id,
                        'client_name' => $call->client->name,
                        'subject' => $call->subject,
                        'summary' => $call->summary,
                        'follow_up_date' => $call->follow_up_date,
                        'days_overdue' => $daysOverdue,
                        'priority' => 'high' // Follow-ups are always high priority
                    ];
                });

            // Summary
            $summary = [
                'total_appointments_today' => $appointmentsToday->count(),
                'total_follow_ups_due' => $followUpsDue->count(),
                'urgent_tasks' => $appointmentsToday->where('priority', 'high')->count() + $followUpsDue->count(),
                'completion_rate' => $this->calculateTodayCompletionRate($userId, $today)
            ];

            return response()->json([
                'success' => true,
                'message' => "Today's tasks retrieved successfully",
                'data' => [
                    'appointments_today' => $appointmentsToday,
                    'follow_ups_due' => $followUpsDue,
                    'summary' => $summary,
                    'date' => $today->format('Y-m-d')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error retrieving today's tasks",
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dashboard/personal/appointments/upcoming",
     *     summary="Get upcoming appointments",
     *     description="Returns upcoming appointments for the authenticated user within the next specified days",
     *     operationId="getUpcomingAppointments",
     *     tags={"Personal Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="Number of days to look ahead",
     *         required=false,
     *         @OA\Schema(type="integer", default=7, maximum=30)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Maximum number of appointments to return",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, maximum=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Upcoming appointments",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Upcoming appointments retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="appointments",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="client_name", type="string", example="Entreprise ACME"),
     *                         @OA\Property(property="subject", type="string", example="Product demo"),
     *                         @OA\Property(property="scheduled_date", type="string", example="2026-01-28T14:00:00Z"),
     *                         @OA\Property(property="status", type="string", example="scheduled"),
     *                         @OA\Property(property="days_until", type="integer", example=2)
     *                     )
     *                 ),
     *                 @OA\Property(property="total_upcoming", type="integer", example=5)
     *             )
     *         )
     *     )
     * )
     */
    public function upcomingAppointments(Request $request)
    {
        try {
            $userId = auth()->id();
            $days = min($request->get('days', 7), 30);
            $limit = min($request->get('limit', 10), 50);

            $startDate = Carbon::now();
            $endDate = Carbon::now()->addDays($days);

            $upcomingAppointments = Appointment::where('user_id', $userId)
                ->whereBetween('scheduled_at', [$startDate, $endDate])
                ->where('status', '!=', 'completed')
                ->with('client')
                ->orderBy('scheduled_at')
                ->limit($limit)
                ->get()
                ->map(function ($appointment) use ($startDate) {
                    $daysUntil = Carbon::parse($appointment->scheduled_at)->diffInDays($startDate);
                    return [
                        'id' => $appointment->id,
                        'client_id' => $appointment->client_id,
                        'client_name' => $appointment->client->name,
                        'subject' => $appointment->subject,
                        'description' => $appointment->description,
                        'scheduled_at' => $appointment->scheduled_at,
                        'status' => $appointment->status,
                        'priority' => $appointment->priority ?? 'normal',
                        'location' => $appointment->location,
                        'type' => $appointment->type ?? 'meeting',
                        'days_until' => $daysUntil
                    ];
                });

            $totalUpcoming = Appointment::where('user_id', $userId)
                ->whereBetween('scheduled_at', [$startDate, $endDate])
                ->where('status', '!=', 'completed')
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Upcoming appointments retrieved successfully',
                'data' => [
                    'appointments' => $upcomingAppointments,
                    'total_upcoming' => $totalUpcoming,
                    'showing' => $upcomingAppointments->count(),
                    'date_range' => [
                        'start' => $startDate->format('Y-m-d'),
                        'end' => $endDate->format('Y-m-d')
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving upcoming appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dashboard/personal/interactions/recent",
     *     summary="Get recent personal interactions",
     *     description="Returns recent interactions created by the authenticated user",
     *     operationId="getPersonalRecentInteractions",
     *     tags={"Personal Dashboard"},
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
     *         description="Recent personal interactions",
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
     *                         @OA\Property(property="created_at", type="string", example="2026-01-26T10:30:00Z")
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
            $userId = auth()->id();
            $limit = min($request->get('limit', 10), 50);

            // Get recent call logs
            $calls = CallLog::where('user_id', $userId)
                ->with('client')
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
                        'duration' => $call->duration
                    ];
                });

            // Get recent appointments
            $appointments = Appointment::where('user_id', $userId)
                ->with('client')
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
                        'scheduled_date' => $appointment->scheduled_date
                    ];
                });

            // Get recent notes
            $notes = ClientNote::where('user_id', $userId)
                ->with('client')
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
                        'is_pinned' => $note->is_pinned
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
                    'total_found' => $allInteractions->count(),
                    'user_id' => $userId
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
     * Helper method to count interactions for a user in a period
     */
    private function getMyInteractionsCount($userId, $dates)
    {
        $callsCount = CallLog::where('user_id', $userId)
            ->whereBetween('created_at', [$dates['start'], $dates['end']])
            ->count();

        $appointmentsCount = Appointment::where('user_id', $userId)
            ->whereBetween('created_at', [$dates['start'], $dates['end']])
            ->count();

        $notesCount = ClientNote::where('user_id', $userId)
            ->whereBetween('created_at', [$dates['start'], $dates['end']])
            ->count();

        return $callsCount + $appointmentsCount + $notesCount;
    }

    /**
     * Calculate today's completion rate
     */
    private function calculateTodayCompletionRate($userId, $today)
    {
        $totalAppointments = Appointment::where('user_id', $userId)
            ->whereDate('scheduled_at', $today)
            ->count();

        $completedAppointments = Appointment::where('user_id', $userId)
            ->whereDate('scheduled_at', $today)
            ->where('status', 'completed')
            ->count();

        if ($totalAppointments === 0) {
            return 0;
        }

        return round(($completedAppointments / $totalAppointments) * 100, 1);
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
