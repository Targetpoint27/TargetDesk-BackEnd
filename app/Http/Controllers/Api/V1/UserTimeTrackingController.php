<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TaskTimeEntry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserTimeTrackingController extends Controller
{
    public function getUserTimeEntries(User $user, Request $request): JsonResponse
    {
        if ($user->id !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        $query = TaskTimeEntry::where('user_id', $user->id)
            ->with(['task', 'user']);

        if ($request->has('project_id')) {
            $query->whereHas('task', function($q) use ($request) {
                $q->where('project_id', $request->project_id);
            });
        }

        if ($request->has('date_from')) {
            $query->whereDate('start_time', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('start_time', '<=', $request->date_to);
        }

        $timeEntries = $query->orderBy('start_time', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $timeEntries
        ]);
    }

    public function getUserTimeSummary(User $user, Request $request): JsonResponse
    {
        if ($user->id !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        $query = TaskTimeEntry::where('user_id', $user->id)
            ->whereNotNull('hours');

        if ($request->has('date_from')) {
            $query->whereDate('start_time', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('start_time', '<=', $request->date_to);
        }

        $entries = $query->with(['task.project'])->get();

        $summary = [
            'total_hours' => $entries->sum('hours'),
            'total_entries' => $entries->count(),
            'by_project' => $entries->groupBy('task.project.name')->map(function($projectEntries) {
                return [
                    'hours' => $projectEntries->sum('hours'),
                    'entries_count' => $projectEntries->count()
                ];
            }),
            'by_date' => $entries->groupBy(function($entry) {
                return $entry->start_time->format('Y-m-d');
            })->map(function($dayEntries) {
                return [
                    'hours' => $dayEntries->sum('hours'),
                    'entries_count' => $dayEntries->count()
                ];
            })
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    public function exportUserTimeEntries(User $user, Request $request): JsonResponse
    {
        if ($user->id !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        // Pour une implémentation complète, ceci générerait un fichier Excel/PDF
        // Pour l'instant, retourner les données JSON
        $query = TaskTimeEntry::where('user_id', $user->id)
            ->with(['task.project']);

        if ($request->has('date_from')) {
            $query->whereDate('start_time', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('start_time', '<=', $request->date_to);
        }

        $entries = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Export généré avec succès',
            'data' => $entries,
            'export_info' => [
                'total_hours' => $entries->sum('hours'),
                'period' => [
                    'from' => $request->date_from,
                    'to' => $request->date_to
                ],
                'generated_at' => now()
            ]
        ]);
    }
}
