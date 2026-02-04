<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Supplier Documents",
 *     description="API pour la gestion des documents suppliers"
 * )
 */
class SupplierDocumentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/suppliers/{supplier}/documents",
     *     summary="Lister les documents d'un supplier",
     *     tags={"Supplier Documents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="supplier",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID du supplier"
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         @OA\Schema(type="string", enum={"contrat", "devis", "facture", "autre"}),
     *         description="Filtrer par catégorie"
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         @OA\Schema(type="string", enum={"name", "date", "type", "category", "size"}),
     *         description="Trier par"
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         @OA\Schema(type="string", enum={"asc", "desc"}),
     *         description="Ordre de tri"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des documents du supplier",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Documents récupérés avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="documents", type="array", @OA\Items(ref="#/components/schemas/SupplierDocument")),
     *                 @OA\Property(property="statistics", type="object",
     *                     @OA\Property(property="total_documents", type="integer", example=15),
     *                     @OA\Property(property="total_size", type="integer", example=52428800),
     *                     @OA\Property(property="formatted_total_size", type="string", example="50 MB"),
     *                     @OA\Property(property="by_category", type="object",
     *                         @OA\Property(property="contrat", type="integer", example=5),
     *                         @OA\Property(property="devis", type="integer", example=3),
     *                         @OA\Property(property="facture", type="integer", example=4),
     *                         @OA\Property(property="autre", type="integer", example=3)
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request, Supplier $supplier): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category' => 'nullable|string|in:contrat,devis,facture,autre',
            'sort' => 'nullable|string|in:name,date,type,category,size',
            'order' => 'nullable|string|in:asc,desc',
            'latest_only' => 'nullable|string|in:true,false,1,0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = $supplier->documents()->active()->with(['uploader:id,name']);

        // Filtrage par catégorie
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        // Option pour ne récupérer que les dernières versions
        if ($request->boolean('latest_only', true)) {
            $query->latestVersions();
        }

        // Tri
        $sortField = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');

        switch ($sortField) {
            case 'name':
                $query->orderBy('title', $sortOrder);
                break;
            case 'date':
                $query->orderBy('created_at', $sortOrder);
                break;
            case 'type':
                $query->orderBy('mime_type', $sortOrder);
                break;
            case 'category':
                $query->orderBy('category', $sortOrder);
                break;
            case 'size':
                $query->orderBy('file_size', $sortOrder);
                break;
            default:
                $query->orderBy('created_at', $sortOrder);
        }

        $documents = $query->get();

        // Statistiques
        $allDocuments = $supplier->documents()->active()->get();
        $totalSize = $allDocuments->sum('file_size');
        $byCategory = $allDocuments->groupBy('category')->map->count();

        $statistics = [
            'total_documents' => $allDocuments->count(),
            'total_size' => $totalSize,
            'formatted_total_size' => $this->formatBytes($totalSize),
            'by_category' => $byCategory->toArray()
        ];

        return response()->json([
            'success' => true,
            'message' => 'Documents récupérés avec succès',
            'data' => [
                'documents' => $documents,
                'statistics' => $statistics
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/suppliers/{supplier}/documents",
     *     summary="Uploader un document pour un supplier",
     *     tags={"Supplier Documents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="supplier",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID du supplier"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="file", type="string", format="binary", description="Fichier à uploader"),
     *                 @OA\Property(property="title", type="string", description="Titre du document"),
     *                 @OA\Property(property="description", type="string", description="Description du document"),
     *                 @OA\Property(property="category", type="string", enum={"contrat", "devis", "facture", "autre"}, description="Catégorie du document")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Document uploadé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Document uploadé avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/SupplierDocument")
     *         )
     *     )
     * )
     */
    public function store(Request $request, Supplier $supplier): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => [
                'required',
                'file',
                'max:' . (SupplierDocument::getMaxFileSize() / 1024), // en KB
            ],
            'title' => 'required|string|min:2|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|in:contrat,devis,facture,autre'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('file');

        // Vérification du type MIME
        if (!in_array($file->getMimeType(), SupplierDocument::getAllowedMimeTypes())) {
            return response()->json([
                'success' => false,
                'message' => 'Type de fichier non autorisé',
                'errors' => ['file' => ['Le type de fichier n\'est pas autorisé']]
            ], 422);
        }

        // Vérification de l'extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, SupplierDocument::getAllowedExtensions())) {
            return response()->json([
                'success' => false,
                'message' => 'Extension de fichier non autorisée',
                'errors' => ['file' => ['L\'extension de fichier n\'est pas autorisée']]
            ], 422);
        }

        // Gestion du versioning - chercher par titre et catégorie
        $baseKey = Str::slug($request->title);
        $existingDoc = $supplier->documents()
            ->where('title', $request->title)
            ->where('category', $request->category)
            ->where('is_active', true)
            ->orderBy('version', 'desc')
            ->first();

        if ($existingDoc) {
            $documentKey = $existingDoc->document_key;
        } else {
            $documentKey = $baseKey . '_' . Str::random(8);
        }

        $version = $existingDoc ? $existingDoc->version + 1 : 1;

        // Si c'est une nouvelle version, désactiver l'ancienne
        if ($existingDoc) {
            $existingDoc->update(['is_active' => false]);
        }

        // Génération du chemin de fichier
        $fileName = $this->generateFileName($supplier->id, $documentKey, $version, $extension);
        $filePath = "documents/suppliers/{$supplier->id}/{$fileName}";

        // Upload du fichier
        $storedPath = $file->storeAs("documents/suppliers/{$supplier->id}", $fileName, 'local');

        if (!$storedPath) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload du fichier'
            ], 500);
        }

        // Création de l'enregistrement
        $document = SupplierDocument::create([
            'supplier_id' => $supplier->id,
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_extension' => $extension,
            'version' => $version,
            'document_key' => $documentKey,
            'uploaded_by' => auth()->id(),
            'metadata' => [
                'original_size' => $file->getSize(),
                'upload_ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]
        ]);

        $document->load(['uploader:id,name', 'supplier:id,name,supplier_id']);

        return response()->json([
            'success' => true,
            'message' => $existingDoc ?
                "Nouvelle version du document uploadée avec succès (v{$version})" :
                'Document uploadé avec succès',
            'data' => $document
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/suppliers/{supplier}/documents/{document}",
     *     summary="Afficher les détails d'un document supplier",
     *     tags={"Supplier Documents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="supplier",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID du supplier"
     *     ),
     *     @OA\Parameter(
     *         name="document",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID du document"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du document",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Document trouvé"),
     *             @OA\Property(property="data", ref="#/components/schemas/SupplierDocument")
     *         )
     *     )
     * )
     */
    public function show(Supplier $supplier, SupplierDocument $document): JsonResponse
    {
        if ($document->supplier_id !== $supplier->id) {
            return response()->json([
                'success' => false,
                'message' => 'Document non trouvé'
            ], 404);
        }

        $document->load(['uploader:id,name', 'supplier:id,name,supplier_id']);
        $document->markAsAccessed();

        return response()->json([
            'success' => true,
            'message' => 'Document trouvé',
            'data' => $document
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/suppliers/{supplier}/documents/{document}",
     *     summary="Mettre à jour les métadonnées d'un document supplier",
     *     tags={"Supplier Documents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="supplier",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID du supplier"
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
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", description="Titre du document"),
     *             @OA\Property(property="description", type="string", description="Description du document"),
     *             @OA\Property(property="category", type="string", enum={"contrat", "devis", "facture", "autre"}, description="Catégorie du document")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document mis à jour",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Document mis à jour avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/SupplierDocument")
     *         )
     *     )
     * )
     */
    public function update(Request $request, Supplier $supplier, SupplierDocument $document): JsonResponse
    {
        if ($document->supplier_id !== $supplier->id) {
            return response()->json([
                'success' => false,
                'message' => 'Document non trouvé'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|min:2|max:255',
            'description' => 'sometimes|nullable|string|max:1000',
            'category' => 'sometimes|required|string|in:contrat,devis,facture,autre'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $document->update($validator->validated());
        $document->load(['uploader:id,name', 'supplier:id,name,supplier_id']);

        return response()->json([
            'success' => true,
            'message' => 'Document mis à jour avec succès',
            'data' => $document
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/suppliers/{supplier}/documents/{document}",
     *     summary="Supprimer un document supplier",
     *     tags={"Supplier Documents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="supplier",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID du supplier"
     *     ),
     *     @OA\Parameter(
     *         name="document",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID du document"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document supprimé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Document supprimé avec succès")
     *         )
     *     )
     * )
     */
    public function destroy(Supplier $supplier, SupplierDocument $document): JsonResponse
    {
        if ($document->supplier_id !== $supplier->id) {
            return response()->json([
                'success' => false,
                'message' => 'Document non trouvé'
            ], 404);
        }

        // Suppression du fichier physique
        if ($document->exists()) {
            Storage::delete($document->file_path);
        }

        // Suppression de l'enregistrement
        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document supprimé avec succès'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/suppliers/{supplier}/documents/{document}/download",
     *     summary="Télécharger un document supplier",
     *     tags={"Supplier Documents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="supplier",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID du supplier"
     *     ),
     *     @OA\Parameter(
     *         name="document",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID du document"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fichier à télécharger",
     *         @OA\MediaType(
     *             mediaType="application/octet-stream"
     *         )
     *     )
     * )
     */
    public function download(Supplier $supplier, SupplierDocument $document)
    {
        if ($document->supplier_id !== $supplier->id) {
            return response()->json([
                'success' => false,
                'message' => 'Document non trouvé'
            ], 404);
        }

        if (!$document->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier non trouvé sur le serveur'
            ], 404);
        }

        $document->markAsAccessed();

        return Storage::download(
            $document->file_path,
            $document->original_name,
            [
                'Content-Type' => $document->mime_type,
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Expose-Headers' => 'Content-Disposition'
            ]
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/suppliers/{supplier}/documents/{document}/preview",
     *     summary="Prévisualiser un document supplier",
     *     tags={"Supplier Documents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="supplier",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID du supplier"
     *     ),
     *     @OA\Parameter(
     *         name="document",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID du document"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Aperçu du fichier",
     *         @OA\MediaType(
     *             mediaType="application/pdf"
     *         )
     *     )
     * )
     */
    public function preview(Supplier $supplier, SupplierDocument $document)
    {
        if ($document->supplier_id !== $supplier->id) {
            return response()->json([
                'success' => false,
                'message' => 'Document non trouvé'
            ], 404);
        }

        if (!$document->can_preview) {
            return response()->json([
                'success' => false,
                'message' => 'Aperçu non disponible pour ce type de fichier'
            ], 422);
        }

        if (!$document->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier non trouvé sur le serveur'
            ], 404);
        }

        $document->markAsAccessed();

        $filePath = Storage::path($document->file_path);

        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier physique non trouvé'
            ], 404);
        }

        return response()->file($filePath, [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline; filename="' . $document->original_name . '"',
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'public, max-age=3600'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/suppliers/{supplier}/documents/{document}/versions",
     *     summary="Lister les versions d'un document supplier",
     *     tags={"Supplier Documents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="supplier",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID du supplier"
     *     ),
     *     @OA\Parameter(
     *         name="document",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID du document"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Versions du document",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Versions récupérées avec succès"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/SupplierDocument"))
     *         )
     *     )
     * )
     */
    public function versions(Supplier $supplier, SupplierDocument $document): JsonResponse
    {
        if ($document->supplier_id !== $supplier->id) {
            return response()->json([
                'success' => false,
                'message' => 'Document non trouvé'
            ], 404);
        }

        $versions = SupplierDocument::versionsOf($document->document_key)
            ->with(['uploader:id,name'])
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Versions récupérées avec succès',
            'data' => $versions
        ]);
    }

    // Méthodes utilitaires privées
    private function generateDocumentKey(string $title, string $originalName): string
    {
        $baseKey = Str::slug($title ?: pathinfo($originalName, PATHINFO_FILENAME));
        return $baseKey . '_' . Str::random(8);
    }

    private function generateFileName(int $supplierId, string $documentKey, int $version, string $extension): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        return "{$documentKey}_v{$version}_{$timestamp}.{$extension}";
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}