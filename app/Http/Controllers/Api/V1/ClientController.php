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

        // Build query with search and filters
        $query = Client::with([
            'creator:id,name',
            'categories' => function ($query) {
                $query->select('categories.id', 'categories.name', 'categories.color', 'categories.type')
                      ->orderBy('categories.type')
                      ->orderBy('categories.name');
            }
        ])->where('is_active', true);

        // Store applied filters for response
        $appliedFilters = [];

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->get('search');
            $appliedFilters['search'] = $searchTerm;

            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('phone', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('siret', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('address', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('client_id', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $appliedFilters['type'] = $request->get('type');
            $query->where('type', $request->get('type'));
        }

        // Filter by sector
        if ($request->filled('sector')) {
            $appliedFilters['sector'] = $request->get('sector');
            $query->where('sector', 'LIKE', "%{$request->get('sector')}%");
        }

        // Filter by creation date range
        if ($request->filled('created_from')) {
            $appliedFilters['created_from'] = $request->get('created_from');
            $query->whereDate('created_at', '>=', $request->get('created_from'));
        }

        if ($request->filled('created_to')) {
            $appliedFilters['created_to'] = $request->get('created_to');
            $query->whereDate('created_at', '<=', $request->get('created_to'));
        }

        // Filter by update date range
        if ($request->filled('updated_from')) {
            $appliedFilters['updated_from'] = $request->get('updated_from');
            $query->whereDate('updated_at', '>=', $request->get('updated_from'));
        }

        if ($request->filled('updated_to')) {
            $appliedFilters['updated_to'] = $request->get('updated_to');
            $query->whereDate('updated_at', '<=', $request->get('updated_to'));
        }

        // Get total count without filters for statistics
        $totalWithoutFilters = Client::where('is_active', true)->count();

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');

        $allowedSortFields = ['name', 'created_at', 'updated_at', 'email', 'type', 'sector'];
        if (in_array($sortBy, $allowedSortFields)) {
            $appliedFilters['sort_by'] = $sortBy;
            $appliedFilters['sort_order'] = $sortOrder;
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc');
        }

        // Execute query with pagination
        $clients = $query->paginate($perPage);

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
            ],
            'filters_applied' => $appliedFilters,
            'total_without_filters' => $totalWithoutFilters
        ], 'Clients récupérés avec succès');
    }

    /**
     * @OA\Get(
     *     path="/v1/clients/search",
     *     tags={"Clients"},
     *     summary="Quick search clients",
     *     description="Search clients for autocomplete functionality",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=true,
     *         description="Search term (minimum 2 characters)",
     *         @OA\Schema(type="string", example="acme")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Maximum number of results",
     *         @OA\Schema(type="integer", minimum=1, maximum=50, default=10)
     *     ),
     *     @OA\Parameter(
     *         name="fuzzy",
     *         in="query",
     *         description="Enable fuzzy search for typos",
     *         @OA\Schema(type="boolean", default=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Résultats de recherche"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="results", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="client_id", type="string", example="CLI-ABC123XYZ4"),
     *                     @OA\Property(property="name", type="string", example="Entreprise ACME"),
     *                     @OA\Property(property="email", type="string", example="contact@acme.com"),
     *                     @OA\Property(property="type", type="string", example="entreprise"),
     *                     @OA\Property(property="highlighted_field", type="string", example="name"),
     *                     @OA\Property(property="match_score", type="number", example=0.95)
     *                 )),
     *                 @OA\Property(property="query", type="string", example="acme"),
     *                 @OA\Property(property="total_found", type="integer", example=5)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Search term too short",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le terme de recherche doit contenir au moins 2 caractères")
     *         )
     *     )
     * )
     */
    public function search(Request $request): JsonResponse
    {
        $searchTerm = $request->get('q', '');
        $limit = min($request->get('limit', 10), 50);
        $fuzzy = $request->boolean('fuzzy', true);

        // Validation
        if (strlen(trim($searchTerm)) < 2) {
            return $this->errorResponse('Le terme de recherche doit contenir au moins 2 caractères', 422);
        }

        $searchTerm = trim($searchTerm);

        // Build search query
        $query = Client::select(['id', 'client_id', 'name', 'email', 'phone', 'type', 'address', 'siret'])
            ->where('is_active', true);

        $results = [];

        // Exact matches first
        $exactMatches = (clone $query)->where(function ($q) use ($searchTerm) {
            $q->where('name', 'LIKE', "{$searchTerm}%")
              ->orWhere('email', 'LIKE', "{$searchTerm}%")
              ->orWhere('client_id', 'LIKE', "{$searchTerm}%");
        })->limit($limit)->get();

        foreach ($exactMatches as $client) {
            $results[] = $this->formatSearchResult($client, $searchTerm, 1.0);
        }

        // If we need more results, do partial matches
        if (count($results) < $limit) {
            $remainingLimit = $limit - count($results);
            $partialMatches = (clone $query)->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('phone', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('siret', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('address', 'LIKE', "%{$searchTerm}%");
            })->whereNotIn('id', $exactMatches->pluck('id'))
              ->limit($remainingLimit)->get();

            foreach ($partialMatches as $client) {
                $results[] = $this->formatSearchResult($client, $searchTerm, 0.8);
            }
        }

        // Fuzzy search for typos if enabled and still need results
        if ($fuzzy && count($results) < $limit) {
            $fuzzyResults = $this->fuzzySearch($searchTerm, $limit - count($results),
                array_column($results, 'id'));
            $results = array_merge($results, $fuzzyResults);
        }

        return $this->successResponse([
            'results' => $results,
            'query' => $searchTerm,
            'total_found' => count($results)
        ], 'Résultats de recherche');
    }

    /**
     * Format search result with highlighting and scoring
     */
    private function formatSearchResult($client, $searchTerm, $score): array
    {
        $highlightedField = null;

        // Determine which field matched
        if (stripos($client->name, $searchTerm) !== false) {
            $highlightedField = 'name';
        } elseif (stripos($client->email, $searchTerm) !== false) {
            $highlightedField = 'email';
        } elseif (stripos($client->client_id, $searchTerm) !== false) {
            $highlightedField = 'client_id';
        } elseif (stripos($client->phone, $searchTerm) !== false) {
            $highlightedField = 'phone';
        } elseif (stripos($client->siret, $searchTerm) !== false) {
            $highlightedField = 'siret';
        } elseif (stripos($client->address, $searchTerm) !== false) {
            $highlightedField = 'address';
        }

        return [
            'id' => $client->id,
            'client_id' => $client->client_id,
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'type' => $client->type,
            'address' => $client->address,
            'highlighted_field' => $highlightedField,
            'match_score' => $score
        ];
    }

    /**
     * Fuzzy search for handling typos
     */
    private function fuzzySearch($searchTerm, $limit, $excludeIds): array
    {
        // Simple fuzzy search implementation using SOUNDEX for phonetic matching
        $query = Client::select(['id', 'client_id', 'name', 'email', 'phone', 'type', 'address', 'siret'])
            ->where('is_active', true)
            ->whereNotIn('id', $excludeIds);

        $fuzzyMatches = $query->whereRaw('SOUNDEX(name) = SOUNDEX(?)', [$searchTerm])
            ->limit($limit)
            ->get();

        $results = [];
        foreach ($fuzzyMatches as $client) {
            $results[] = $this->formatSearchResult($client, $searchTerm, 0.6);
        }

        return $results;
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
        $client = Client::with(['creator:id,name', 'categories'])
                        ->where('is_active', true)
                        ->find($id);

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
            'notes' => 'nullable|string',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id'
        ]);

        // Séparer les catégories du reste des données
        $categories = $validated['category_ids'] ?? null;
        unset($validated['category_ids']);

        $client->update($validated);

        // Mettre à jour les catégories si fournies
        if ($categories !== null) {
            $categoryData = [];
            foreach ($categories as $categoryId) {
                $categoryData[$categoryId] = [
                    'assigned_by' => auth()->id(),
                    'assigned_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
            $client->categories()->sync($categoryData);
        }

        // Recharger le client avec les catégories
        $client = $client->fresh(['creator:id,name', 'categories']);

        // Log d'audit
        Log::info('Client modifié', [
            'client_id' => $client->client_id,
            'name' => $client->name,
            'updated_by' => auth()->id(),
            'updated_by_name' => auth()->user()->name,
            'changes' => $validated,
            'categories_updated' => $categories !== null
        ]);

        return $this->successResponse($client, 'Client mis à jour avec succès');
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