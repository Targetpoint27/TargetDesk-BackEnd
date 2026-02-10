<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TaskTag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TaskTagController extends Controller
{
    /**
     * Display a listing of tags
     */
    public function index(): JsonResponse
    {
        $tags = TaskTag::with('creator')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tags
        ]);
    }

    /**
     * Store a newly created tag
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:task_tags,name',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-F]{6}$/i'
        ]);

        try {
            $tag = TaskTag::create([
                'name' => $request->name,
                'color' => $request->color,
                'created_by' => Auth::id()
            ]);

            $tag->load('creator');

            return response()->json([
                'success' => true,
                'data' => $tag,
                'message' => 'Étiquette créée avec succès'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'étiquette: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified tag
     */
    public function destroy(TaskTag $tag): JsonResponse
    {
        try {
            $tag->delete();

            return response()->json([
                'success' => true,
                'message' => 'Étiquette supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'étiquette: ' . $e->getMessage()
            ], 500);
        }
    }
}
