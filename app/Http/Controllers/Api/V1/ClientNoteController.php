<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Client;
use App\Models\ClientNote;
use App\Models\NoteAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(name="Client Notes", description="Gestion des notes client")
 */
class ClientNoteController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/v1/clients/{client}/notes",
     *     tags={"Client Notes"},
     *     summary="Liste des notes client",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="client", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string", enum={"normal", "important", "private"})),
     *     @OA\Parameter(name="pinned_only", in="query", @OA\Schema(type="boolean")),
     *     @OA\Response(response=200, description="Notes récupérées")
     * )
     */
    public function index(Request $request, Client $client): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 20);

            $query = $client->notes()->with(['user:id,name', 'attachments']);

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->boolean('pinned_only')) {
                $query->pinned();
            }

            $notes = $query->orderBy('is_pinned', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->paginate($perPage);

            return $this->successResponse($notes, 'Notes récupérées');

        } catch (\Exception $e) {
            Log::error('Error fetching client notes: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la récupération des notes', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/v1/clients/{client}/notes",
     *     tags={"Client Notes"},
     *     summary="Créer une note client",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="client", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="content", type="string"),
     *         @OA\Property(property="type", type="string", enum={"normal", "important", "private"}),
     *         @OA\Property(property="is_pinned", type="boolean")
     *     )),
     *     @OA\Response(response=201, description="Note créée")
     * )
     */
    public function store(Request $request, Client $client): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'type' => 'in:normal,important,private',
                'is_pinned' => 'boolean'
            ]);

            $note = $client->notes()->create([
                'user_id' => auth()->id(),
                'title' => $validated['title'],
                'content' => $validated['content'],
                'type' => $validated['type'] ?? 'normal',
                'is_pinned' => $validated['is_pinned'] ?? false,
                'attachments_count' => 0
            ]);

            $note->load(['user:id,name', 'attachments']);

            return $this->successResponse($note, 'Note créée', 201);

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            Log::error('Error creating client note: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la création de la note', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/v1/notes/{note}",
     *     tags={"Client Notes"},
     *     summary="Détails d'une note",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="note", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Note trouvée")
     * )
     */
    public function show(ClientNote $note): JsonResponse
    {
        try {
            $note->load(['client:id,name,client_id', 'user:id,name', 'attachments']);
            return $this->successResponse($note, 'Note trouvée');

        } catch (\Exception $e) {
            Log::error('Error fetching note: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la récupération de la note', 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/v1/notes/{note}",
     *     tags={"Client Notes"},
     *     summary="Modifier une note",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="note", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Note modifiée")
     * )
     */
    public function update(Request $request, ClientNote $note): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'content' => 'sometimes|required|string',
                'type' => 'sometimes|in:normal,important,private',
                'is_pinned' => 'sometimes|boolean'
            ]);

            $note->update($validated);
            $note->load(['client:id,name,client_id', 'user:id,name', 'attachments']);

            return $this->successResponse($note, 'Note modifiée');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            Log::error('Error updating note: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la modification de la note', 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/v1/notes/{note}",
     *     tags={"Client Notes"},
     *     summary="Supprimer une note",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="note", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Note supprimée")
     * )
     */
    public function destroy(ClientNote $note): JsonResponse
    {
        try {
            foreach ($note->attachments as $attachment) {
                Storage::delete($attachment->path);
                $attachment->delete();
            }

            $note->interaction?->delete();
            $note->delete();

            return $this->successResponse(null, 'Note supprimée', 204);

        } catch (\Exception $e) {
            Log::error('Error deleting note: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la suppression de la note', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/v1/notes/{note}/pin",
     *     tags={"Client Notes"},
     *     summary="Épingler/désépingler une note",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="note", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Note épinglée/désépinglée")
     * )
     */
    public function togglePin(ClientNote $note): JsonResponse
    {
        try {
            $note->update(['is_pinned' => !$note->is_pinned]);

            return $this->successResponse([
                'is_pinned' => $note->is_pinned
            ], $note->is_pinned ? 'Note épinglée' : 'Note désépinglée');

        } catch (\Exception $e) {
            Log::error('Error toggling note pin: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de l\'épinglage de la note', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/v1/notes/{note}/attachments",
     *     tags={"Client Notes"},
     *     summary="Ajouter un fichier à une note",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="note", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=201, description="Fichier ajouté")
     * )
     */
    public function addAttachment(Request $request, ClientNote $note): JsonResponse
    {
        try {
            $validated = $request->validate([
                'file' => 'required|file|max:10240'
            ]);

            $file = $validated['file'];
            $path = $file->store('note_attachments/' . $note->id, 'public');

            $attachment = $note->attachments()->create([
                'filename' => $file->hashName(),
                'original_filename' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);

            $note->increment('attachments_count');

            return $this->successResponse($attachment, 'Fichier ajouté', 201);

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            Log::error('Error adding attachment: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de l\'ajout du fichier', 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/v1/notes/attachments/{attachment}",
     *     tags={"Client Notes"},
     *     summary="Supprimer un fichier joint",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="attachment", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Fichier supprimé")
     * )
     */
    public function removeAttachment(NoteAttachment $attachment): JsonResponse
    {
        try {
            Storage::delete($attachment->path);
            $note = $attachment->note;
            $attachment->delete();
            $note->decrement('attachments_count');

            return $this->successResponse(null, 'Fichier supprimé', 204);

        } catch (\Exception $e) {
            Log::error('Error removing attachment: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de la suppression du fichier', 500);
        }
    }
}
