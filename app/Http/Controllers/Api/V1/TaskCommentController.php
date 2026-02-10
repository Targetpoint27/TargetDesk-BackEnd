<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TaskComment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaskCommentController extends Controller
{
    public function update(Request $request, TaskComment $comment): JsonResponse
    {
        $request->validate([
            'content' => 'required|string'
        ]);

        if ($comment->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez modifier que vos propres commentaires'
            ], 403);
        }

        $comment->update([
            'content' => $request->content,
            'is_edited' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Commentaire modifié avec succès',
            'data' => $comment->load('user')
        ]);
    }

    public function destroy(TaskComment $comment): JsonResponse
    {
        if ($comment->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez supprimer que vos propres commentaires'
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Commentaire supprimé avec succès'
        ]);
    }
}
