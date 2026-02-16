<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Client;
use App\Models\ClientDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Client Document Folders",
 *     description="API pour la gestion des dossiers de documents clients"
 * )
 */
class ClientDocumentFolderController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/clients/{client}/documents/folders",
     *     summary="Lister la structure des dossiers",
     *     tags={"Client Document Folders"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="client",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID du client"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Structure des dossiers",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Structure des dossiers récupérée"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="folders", type="array"),
     *                 @OA\Property(property="documents_by_folder", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Client $client): JsonResponse
    {
        $folderStructure = $client->getFolderStructure();
        $documentsByFolder = $client->getDocumentsByFolder();

        return $this->successResponse([
            'folders' => $folderStructure,
            'documents_by_folder' => $documentsByFolder
        ], 'Structure des dossiers récupérée');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/clients/{client}/documents/folders",
     *     summary="Créer un nouveau dossier",
     *     tags={"Client Document Folders"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="client",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID du client"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(property="folder_name", type="string", example="KYC", description="Nom du dossier"),
     *                 @OA\Property(property="parent_path", type="string", example="Documents", description="Chemin parent (optionnel)"),
     *                 @OA\Property(property="description", type="string", example="Dossier pour les documents KYC", description="Description")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Dossier créé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dossier créé avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request, Client $client): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'folder_name' => 'required|string|max:200|regex:/^[a-zA-Z0-9\s\-_]+$/',
            'parent_path' => 'nullable|string|max:400',
            'description' => 'nullable|string|max:1000'
        ], [
            'folder_name.required' => 'Le nom du dossier est requis',
            'folder_name.regex' => 'Le nom du dossier ne peut contenir que des lettres, chiffres, espaces, tirets et underscores'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Erreurs de validation', 422, $validator->errors());
        }

        $folderName = $request->input('folder_name');
        $parentPath = $request->input('parent_path');

        // Construire le chemin complet
        $folderPath = $parentPath ? $parentPath . '/' . $folderName : $folderName;

        // Calculer le niveau
        $folderLevel = $parentPath ? substr_count($parentPath, '/') + 1 : 0;

        // Vérifier si le dossier existe déjà
        $existingFolder = $client->documents()
            ->where('folder_path', $folderPath)
            ->first();

        if ($existingFolder) {
            return $this->errorResponse('Un dossier avec ce nom existe déjà à cet emplacement', 409);
        }

        // Le dossier est créé virtuellement, pas besoin de document placeholder
        // Il sera visible dès qu'un document y sera ajouté

        return $this->successResponse([
            'folder_path' => $folderPath,
            'folder_name' => $folderName,
            'folder_level' => $folderLevel,
            'description' => $request->input('description'),
            'created_at' => now()
        ], 'Dossier créé avec succès', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/clients/{client}/documents/folders/{folderPath}",
     *     summary="Lister les documents d'un dossier",
     *     tags={"Client Document Folders"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="client",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID du client"
     *     ),
     *     @OA\Parameter(
     *         name="folderPath",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Chemin du dossier (encodé en base64)"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Documents du dossier",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Documents du dossier récupérés"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="folder_info", type="object"),
     *                 @OA\Property(property="documents", type="array"),
     *                 @OA\Property(property="subfolders", type="array")
     *             )
     *         )
     *     )
     * )
     */
    public function show(Client $client, string $folderPath): JsonResponse
    {
        // Décoder le chemin du dossier (base64)
        $decodedPath = base64_decode($folderPath);

        if (!$decodedPath) {
            return $this->errorResponse('Chemin de dossier invalide', 400);
        }

        // Récupérer les documents du dossier (exact path match)
        $documents = $client->documents()
            ->active()
            ->where('folder_path', $decodedPath)
            ->where('title', '!=', '.folder_placeholder') // Exclure les placeholders
            ->with(['uploader:id,name'])
            ->orderBy('title')
            ->get();

        // Récupérer les sous-dossiers
        $subfolders = $client->documents()
            ->active()
            ->where('folder_path', 'LIKE', $decodedPath . '/%')
            ->where('folder_level', substr_count($decodedPath, '/') + 1)
            ->select('folder_path', 'folder_name', 'folder_level', 'created_at')
            ->distinct()
            ->get()
            ->map(function ($item) {
                return [
                    'path' => $item->folder_path,
                    'name' => $item->folder_name,
                    'level' => $item->folder_level,
                    'encoded_path' => base64_encode($item->folder_path),
                    'created_at' => $item->created_at
                ];
            });

        // Info du dossier courant
        $folderInfo = [
            'path' => $decodedPath,
            'name' => basename($decodedPath),
            'level' => substr_count($decodedPath, '/'),
            'parent_path' => dirname($decodedPath) !== '.' ? dirname($decodedPath) : null,
            'document_count' => $documents->count(),
            'subfolder_count' => $subfolders->count()
        ];

        return $this->successResponse([
            'folder_info' => $folderInfo,
            'documents' => $documents,
            'subfolders' => $subfolders
        ], 'Documents du dossier récupérés');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/clients/{client}/documents/{document}/folder",
     *     summary="Déplacer un document vers un autre dossier",
     *     tags={"Client Document Folders"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="client",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID du client"
     *     ),
     *     @OA\Parameter(
     *         name="document",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID du document"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(property="new_folder_path", type="string", example="KYC/Financier", description="Nouveau chemin de dossier")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document déplacé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Document déplacé avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/ClientDocument")
     *         )
     *     )
     * )
     */
    public function moveDocument(Request $request, Client $client, ClientDocument $document): JsonResponse
    {
        // Vérifier que le document appartient au client
        if ($document->client_id !== $client->id) {
            return $this->errorResponse('Document non trouvé', 404);
        }

        $validator = Validator::make($request->all(), [
            'new_folder_path' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Erreurs de validation', 422, $validator->errors());
        }

        $newFolderPath = $request->input('new_folder_path');
        $newFolderLevel = substr_count($newFolderPath, '/');
        $newFolderName = basename($newFolderPath);

        // Mettre à jour le document
        $document->update([
            'folder_path' => $newFolderPath,
            'folder_name' => $newFolderName,
            'folder_level' => $newFolderLevel
        ]);

        $document->load(['uploader:id,name']);

        return $this->successResponse($document, 'Document déplacé avec succès');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/clients/{client}/documents/folders/{folderPath}",
     *     summary="Supprimer un dossier",
     *     tags={"Client Document Folders"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="client",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID du client"
     *     ),
     *     @OA\Parameter(
     *         name="folderPath",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Chemin du dossier (encodé en base64)"
     *     ),
     *     @OA\Parameter(
     *         name="force",
     *         in="query",
     *         @OA\Schema(type="boolean"),
     *         description="Forcer la suppression même si des documents existent"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dossier supprimé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dossier supprimé avec succès")
     *         )
     *     )
     * )
     */
    public function destroy(Request $request, Client $client, string $folderPath): JsonResponse
    {
        // Décoder le chemin du dossier
        $decodedPath = base64_decode($folderPath);

        if (!$decodedPath) {
            return $this->errorResponse('Chemin de dossier invalide', 400);
        }

        // Vérifier s'il y a des documents dans le dossier
        $documentsCount = $client->documents()
            ->active()
            ->where('folder_path', $decodedPath)
            ->where('title', '!=', '.folder_placeholder')
            ->count();

        // Vérifier s'il y a des sous-dossiers
        $subfoldersCount = $client->documents()
            ->active()
            ->where('folder_path', 'LIKE', $decodedPath . '/%')
            ->count();

        if (($documentsCount > 0 || $subfoldersCount > 0) && !$request->boolean('force')) {
            return $this->errorResponse('Le dossier contient des documents ou des sous-dossiers. Utilisez force=true pour forcer la suppression.', 409);
        }

        // Supprimer tous les documents du dossier (si force=true)
        if ($request->boolean('force')) {
            $client->documents()
                ->where('folder_path', 'LIKE', $decodedPath . '%')
                ->delete();
        }

        // Supprimer le placeholder du dossier
        $client->documents()
            ->where('folder_path', $decodedPath)
            ->where('title', '.folder_placeholder')
            ->delete();

        return $this->successResponse(null, 'Dossier supprimé avec succès');
    }
}
