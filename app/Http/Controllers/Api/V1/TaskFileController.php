<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskFile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaskFileController extends Controller
{
    public function index(Task $task): JsonResponse
    {
        $files = $task->files()->with('user')->get();

        return response()->json([
            'success' => true,
            'data' => $files
        ]);
    }

    public function store(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240',
            'description' => 'nullable|string|max:255'
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $fileName = time() . '_' . Str::random(10) . '.' . $extension;

        $path = $file->storeAs('task-files', $fileName, 'public');

        $taskFile = TaskFile::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'name' => $originalName,
            'file_name' => $fileName,
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
            'description' => $request->description
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fichier uploadé avec succès',
            'data' => $taskFile->load('user')
        ], 201);
    }

    public function download(TaskFile $file)
    {
        if (!Storage::disk('public')->exists($file->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier non trouvé'
            ], 404);
        }

        return Storage::disk('public')->download($file->file_path, $file->name);
    }

    public function destroy(TaskFile $file): JsonResponse
    {
        if ($file->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez supprimer que vos propres fichiers'
            ], 403);
        }

        if (Storage::disk('public')->exists($file->file_path)) {
            Storage::disk('public')->delete($file->file_path);
        }

        $file->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fichier supprimé avec succès'
        ]);
    }
}
