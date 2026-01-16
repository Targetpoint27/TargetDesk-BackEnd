<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClientController extends BaseApiController
{
    /**
     * @OA\Post(
     *     path="/v1/clients",
     *     tags={"Clients"},
     *     summary="Create a new client",
     *     description="Create a new client record with automatic ID generation",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","type","email"},
     *             @OA\Property(property="name", type="string", example="Entreprise ACME"),
     *             @OA\Property(property="type", type="string", enum={"particulier","entreprise"}, example="entreprise"),
     *             @OA\Property(property="email", type="string", format="email", example="contact@acme.com"),
     *             @OA\Property(property="phone", type="string", example="0123456789"),
     *             @OA\Property(property="address", type="string", example="123 Rue de la Paix, 75001 Paris"),
     *             @OA\Property(property="siret", type="string", example="12345678901234"),
     *             @OA\Property(property="sector", type="string", example="Technologie"),
     *             @OA\Property(property="website", type="string", example="https://acme.com"),
     *             @OA\Property(property="notes", type="string", example="Notes importantes")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Client created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Client créé avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="client_id", type="string", example="CLI-ABC123XYZ4"),
     *                 @OA\Property(property="name", type="string", example="Entreprise ACME"),
     *                 @OA\Property(property="type", type="string", example="entreprise"),
     *                 @OA\Property(property="email", type="string", example="contact@acme.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreurs de validation"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:particulier,entreprise',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'siret' => 'nullable|string|size:14|unique:clients,siret',
            'sector' => 'nullable|string|max:100',
            'website' => 'nullable|url',
            'notes' => 'nullable|string'
        ], [
            'name.required' => 'Le nom/raison sociale est requis',
            'type.required' => 'Le type est requis',
            'type.in' => 'Le type doit être "particulier" ou "entreprise"',
            'email.required' => 'L\'email principal est requis',
            'email.email' => 'L\'email doit être valide',
            'email.unique' => 'Cet email est déjà utilisé',
            'siret.size' => 'Le SIRET doit contenir exactement 14 caractères',
            'siret.unique' => 'Ce SIRET est déjà utilisé',
            'website.url' => 'Le site web doit être une URL valide'
        ]);

        $validated['created_by'] = auth()->id();

        $client = Client::create($validated);

        // Log d'audit
        Log::info('Client créé', [
            'client_id' => $client->client_id,
            'name' => $client->name,
            'created_by' => auth()->id(),
            'created_by_name' => auth()->user()->name
        ]);

        return $this->successResponse([
            'id' => $client->id,
            'client_id' => $client->client_id,
            'name' => $client->name,
            'type' => $client->type,
            'email' => $client->email,
            'phone' => $client->phone,
            'address' => $client->address,
            'siret' => $client->siret,
            'sector' => $client->sector,
            'website' => $client->website,
            'notes' => $client->notes,
            'is_active' => $client->is_active,
            'created_at' => $client->created_at
        ], 'Client créé avec succès', 201);
    }

    /**
     * @OA\Get(
     *     path="/v1/clients",
     *     tags={"Clients"},
     *     summary="List all clients",
     *     description="Get a paginated list of all clients",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page (max 100)",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Clients retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Clients récupérés avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="clients", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="pagination", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 100);

        $clients = Client::with([
                'creator:id,name',
                'categories' => function ($query) {
                    $query->select('categories.id', 'categories.name', 'categories.color', 'categories.type')
                          ->orderBy('categories.type')
                          ->orderBy('categories.name');
                }
            ])
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Enrichir chaque client avec des informations calculées
        $clients->each(function ($client) {
            $client->categories_count = $client->categories->count();
            $client->categories_summary = $client->categories->groupBy('type')->map(function ($group, $type) {
                return [
                    'type' => $type,
                    'count' => $group->count(),
                    'categories' => $group->map(function ($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'color' => $category->color
                        ];
                    })->values()
                ];
            })->values();
        });

        return $this->successResponse([
            'clients' => $clients->items(),
            'pagination' => [
                'current_page' => $clients->currentPage(),
                'total_pages' => $clients->lastPage(),
                'total_items' => $clients->total(),
                'per_page' => $clients->perPage()
            ]
        ], 'Clients récupérés avec succès');
    }

    /**
     * @OA\Get(
     *     path="/v1/clients/{id}",
     *     tags={"Clients"},
     *     summary="Get client details",
     *     description="Get detailed information about a specific client",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Client ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Client trouvé"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Client non trouvé")
     *         )
     *     )
     * )
     */
    public function show($id): JsonResponse
    {
        $client = Client::with('creator:id,name')->where('is_active', true)->find($id);

        if (!$client) {
            return $this->errorResponse('Client non trouvé', 404);
        }

        return $this->successResponse($client, 'Client trouvé');
    }

    /**
     * @OA\Put(
     *     path="/v1/clients/{id}",
     *     tags={"Clients"},
     *     summary="Update client",
     *     description="Update an existing client record",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Client ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Entreprise ACME Modifiée"),
     *             @OA\Property(property="type", type="string", enum={"particulier","entreprise"}),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="siret", type="string"),
     *             @OA\Property(property="sector", type="string"),
     *             @OA\Property(property="website", type="string"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client not found"
     *     )
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        $client = Client::find($id);

        if (!$client) {
            return $this->errorResponse('Client non trouvé', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:particulier,entreprise',
            'email' => 'sometimes|required|email|unique:clients,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'siret' => 'nullable|string|size:14|unique:clients,siret,' . $id,
            'sector' => 'nullable|string|max:100',
            'website' => 'nullable|url',
            'notes' => 'nullable|string'
        ]);

        $client->update($validated);

        // Log d'audit
        Log::info('Client modifié', [
            'client_id' => $client->client_id,
            'name' => $client->name,
            'updated_by' => auth()->id(),
            'updated_by_name' => auth()->user()->name,
            'changes' => $validated
        ]);

        return $this->successResponse($client->fresh(), 'Client mis à jour avec succès');
    }

    /**
     * @OA\Delete(
     *     path="/v1/clients/{id}",
     *     tags={"Clients"},
     *     summary="Delete client",
     *     description="Soft delete a client record (set as inactive)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Client ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Client supprimé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Client non trouvé")
     *         )
     *     )
     * )
     */
    public function destroy($id): JsonResponse
    {
        $client = Client::find($id);

        if (!$client) {
            return $this->errorResponse('Client non trouvé', 404);
        }

        // Soft delete - désactivation plutôt que suppression physique
        $client->update(['is_active' => false]);

        // Log d'audit
        Log::info('Client supprimé (désactivé)', [
            'client_id' => $client->client_id,
            'name' => $client->name,
            'deleted_by' => auth()->id(),
            'deleted_by_name' => auth()->user()->name
        ]);

        return $this->successResponse(null, 'Client supprimé avec succès');
    }
}