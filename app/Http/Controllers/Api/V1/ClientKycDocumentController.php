<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientKycDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ClientKycDocumentController extends Controller
{
    /**
     * Display a listing of KYC documents for a client
     */
    public function index(Client $client): JsonResponse
    {
        $documents = $client->kycDocuments()
            ->with('uploader:id,name')
            ->orderBy('document_type')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $documents,
            'message' => 'Documents KYC récupérés avec succès'
        ]);
    }

    /**
     * Store a newly uploaded document
     */
    public function store(Request $request, Client $client): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'document_type' => [
                'required',
                'string',
                'in:' . implode(',', array_keys(ClientKycDocument::DOCUMENT_TYPES))
            ],
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB max
                'mimes:pdf,doc,docx,jpg,jpeg,png,gif,webp'
            ]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $documentType = $request->input('document_type');

            // Check if document already exists
            $existingDocument = $client->getKycDocument($documentType);
            if ($existingDocument) {
                // Delete old file
                Storage::delete($existingDocument->file_path);
                // Delete record
                $existingDocument->delete();
            }

            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = 'kyc_' . $client->id . '_' . $documentType . '_' . time() . '.' . $extension;

            // Store file
            $path = $file->storeAs('client_kyc_documents', $filename, 'public');

            // Create database record
            $document = ClientKycDocument::create([
                'client_id' => $client->id,
                'document_type' => $documentType,
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'can_preview' => $this->canPreviewMimeType($file->getMimeType()),
                'uploaded_at' => Carbon::now(),
                'uploaded_by' => Auth::id()
            ]);

            $document->load('uploader:id,name');

            return response()->json([
                'success' => true,
                'data' => $document,
                'message' => 'Document KYC téléchargé avec succès'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified document
     */
    public function show(Client $client, ClientKycDocument $document): JsonResponse
    {
        if ($document->client_id !== $client->id) {
            return response()->json([
                'success' => false,
                'message' => 'Document non trouvé'
            ], 404);
        }

        $document->load('uploader:id,name');

        return response()->json([
            'success' => true,
            'data' => $document,
            'message' => 'Document récupéré avec succès'
        ]);
    }

    /**
     * Remove the specified document
     */
    public function destroy(Client $client, ClientKycDocument $document): JsonResponse
    {
        if ($document->client_id !== $client->id) {
            return response()->json([
                'success' => false,
                'message' => 'Document non trouvé'
            ], 404);
        }

        try {
            // Delete file from storage
            Storage::delete($document->file_path);

            // Delete database record
            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Document supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download the specified document
     */
    public function download(Client $client, ClientKycDocument $document)
    {
        if ($document->client_id !== $client->id) {
            return response()->json([
                'success' => false,
                'message' => 'Document non trouvé'
            ], 404);
        }

        if (!Storage::exists($document->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier non trouvé sur le serveur'
            ], 404);
        }

        return Storage::download($document->file_path, $document->original_name);
    }

    /**
     * Preview the specified document
     */
    public function preview(Client $client, ClientKycDocument $document)
    {
        if ($document->client_id !== $client->id) {
            return response()->json([
                'success' => false,
                'message' => 'Document non trouvé'
            ], 404);
        }

        if (!$document->can_preview) {
            return response()->json([
                'success' => false,
                'message' => 'Ce document ne peut pas être prévisualisé'
            ], 422);
        }

        if (!Storage::exists($document->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier non trouvé sur le serveur'
            ], 404);
        }

        return response()->file(Storage::path($document->file_path));
    }

    /**
     * Get available document types
     */
    public function getDocumentTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ClientKycDocument::DOCUMENT_TYPES,
            'message' => 'Types de documents disponibles'
        ]);
    }

    /**
     * Check if a mime type can be previewed
     */
    private function canPreviewMimeType(string $mimeType): bool
    {
        $previewableMimes = [
            'application/pdf',
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp'
        ];

        return in_array($mimeType, $previewableMimes);
    }
}
