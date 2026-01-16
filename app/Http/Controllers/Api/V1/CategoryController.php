<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Client;
use App\Http\Requests\Api\StoreCategoryRequest;
use App\Http\Requests\Api\UpdateCategoryRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Categories",
 *     description="Gestion des catégories pour la segmentation des clients"
 * )
 */
class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/v1/categories",
     *     tags={"Categories"},
     *     summary="List all categories",
     *     description="Get all categories with optional filtering by type, hierarchical structure",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by category type",
     *         @OA\Schema(type="string", enum={"secteur", "taille", "priorite", "origine", "personnalisee"})
     *     ),
     *     @OA\Parameter(
     *         name="hierarchical",
     *         in="query",
     *         description="Return hierarchical structure (true) or flat list (false)",
     *         @OA\Schema(type="boolean", default=false)
     *     ),
     *     @OA\Parameter(
     *         name="include_children",
     *         in="query",
     *         description="Include children categories in response",
     *         @OA\Schema(type="boolean", default=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Categories retrieved successfully"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Category::with(['creator:id,name'])->active();

            // Filtrer par type si spécifié
            if ($request->has('type')) {
                $query->byType($request->type);
            }

            // Structure hiérarchique ou liste plate
            $hierarchical = $request->boolean('hierarchical', false);
            $includeChildren = $request->boolean('include_children', true);

            if ($hierarchical) {
                // Retourner seulement les catégories racines avec leurs enfants
                $categories = $query->root()
                                   ->with($includeChildren ? 'allChildren.creator:id,name' : [])
                                   ->orderBy('name')
                                   ->get();
            } else {
                // Liste plate avec informations du parent
                $categories = $query->with(['parent:id,name'])
                                   ->orderBy('name')
                                   ->get();
            }

            // Enrichir avec des informations calculées
            $categories->each(function ($category) {
                $category->append(['full_path', 'formatted_type']);
                $category->clients_count = $category->clients()->count();
                $category->children_count = $category->children()->count();
                $category->depth_level = $category->getDepthLevel();
            });

            Log::info('Catégories récupérées', [
                'count' => $categories->count(),
                'type_filter' => $request->type,
                'hierarchical' => $hierarchical,
                'requested_by' => auth()->id()
            ]);

            return $this->successResponse($categories, 'Catégories récupérées avec succès');

        } catch (\Exception $e) {
            Log::error('Erreur récupération catégories', [
                'error' => $e->getMessage(),
                'requested_by' => auth()->id()
            ]);

            return $this->errorResponse('Erreur lors de la récupération des catégories', 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/v1/categories",
     *     tags={"Categories"},
     *     summary="Create a new category",
     *     description="Create a new category with optional parent for hierarchical structure",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "type"},
     *             @OA\Property(property="name", type="string", example="Industrie Automobile"),
     *             @OA\Property(property="description", type="string", example="Secteur de l'industrie automobile"),
     *             @OA\Property(property="parent_id", type="integer", example=null),
     *             @OA\Property(property="color", type="string", example="#FF6B6B"),
     *             @OA\Property(property="type", type="string", enum={"secteur", "taille", "priorite", "origine", "personnalisee"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Category created successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     )
     * )
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            // Validation de la hiérarchie si parent spécifié
            if (isset($validated['parent_id'])) {
                $parent = Category::find($validated['parent_id']);
                if (!$parent || !$parent->is_active) {
                    return $this->errorResponse('Catégorie parent non trouvée ou inactive', 404);
                }
            }

            $validated['created_by'] = auth()->id();

            $category = Category::create($validated);

            // Charger les relations pour la réponse
            $category->load(['parent:id,name', 'creator:id,name']);
            $category->append(['full_path', 'formatted_type']);

            Log::info('Catégorie créée', [
                'category_id' => $category->id,
                'name' => $category->name,
                'type' => $category->type,
                'parent_id' => $category->parent_id,
                'created_by' => auth()->id(),
                'created_by_name' => auth()->user()->name
            ]);

            DB::commit();

            return $this->successResponse($category, 'Catégorie créée avec succès', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création catégorie', [
                'error' => $e->getMessage(),
                'data' => $validated,
                'created_by' => auth()->id()
            ]);

            return $this->errorResponse('Erreur lors de la création de la catégorie', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/v1/categories/{id}",
     *     tags={"Categories"},
     *     summary="Get category details",
     *     description="Get detailed information about a specific category including clients count and hierarchy",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Category ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category details retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found"
     *     )
     * )
     */
    public function show($id): JsonResponse
    {
        $category = Category::with([
            'parent:id,name',
            'children:id,name,parent_id,color,type,is_active',
            'creator:id,name'
        ])->active()->find($id);

        if (!$category) {
            return $this->errorResponse('Catégorie non trouvée', 404);
        }

        // Enrichir avec des informations calculées
        $category->append(['full_path', 'formatted_type']);
        $category->clients_count = $category->clients()->count();
        $category->children_count = $category->children()->count();
        $category->depth_level = $category->getDepthLevel();

        // Ajouter les clients récents
        $category->recent_clients = $category->clients()
                                            ->with('creator:id,name')
                                            ->latest('client_categories.created_at')
                                            ->limit(5)
                                            ->get();

        return $this->successResponse($category, 'Catégorie trouvée');
    }

    /**
     * @OA\Put(
     *     path="/v1/categories/{id}",
     *     tags={"Categories"},
     *     summary="Update category",
     *     description="Update an existing category. Cannot create circular references in hierarchy",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Category ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="parent_id", type="integer"),
     *             @OA\Property(property="color", type="string"),
     *             @OA\Property(property="type", type="string", enum={"secteur", "taille", "priorite", "origine", "personnalisee"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors or circular reference"
     *     )
     * )
     */
    public function update(UpdateCategoryRequest $request, $id): JsonResponse
    {
        $category = Category::active()->find($id);

        if (!$category) {
            return $this->errorResponse('Catégorie non trouvée', 404);
        }

        $validated = $request->validated();
        $originalData = $category->toArray();

        DB::beginTransaction();
        try {
            // Validation de la hiérarchie si parent modifié
            if (isset($validated['parent_id']) && $validated['parent_id'] !== $category->parent_id) {
                if ($validated['parent_id']) {
                    $newParent = Category::find($validated['parent_id']);
                    if (!$newParent || !$newParent->is_active) {
                        return $this->errorResponse('Catégorie parent non trouvée ou inactive', 404);
                    }

                    // Vérifier les références circulaires
                    if (!$category->canHaveParent($newParent)) {
                        return $this->errorResponse('Référence circulaire détectée. Une catégorie ne peut pas être parent de ses ancêtres.', 422);
                    }
                }
            }

            $category->update($validated);

            // Charger les relations pour la réponse
            $category->load(['parent:id,name', 'children:id,name', 'creator:id,name']);
            $category->append(['full_path', 'formatted_type']);

            Log::info('Catégorie modifiée', [
                'category_id' => $category->id,
                'changes' => array_diff_assoc($validated, $originalData),
                'updated_by' => auth()->id(),
                'updated_by_name' => auth()->user()->name
            ]);

            DB::commit();

            return $this->successResponse($category, 'Catégorie mise à jour avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur modification catégorie', [
                'category_id' => $id,
                'error' => $e->getMessage(),
                'updated_by' => auth()->id()
            ]);

            return $this->errorResponse('Erreur lors de la modification de la catégorie', 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/v1/categories/{id}",
     *     tags={"Categories"},
     *     summary="Delete category",
     *     description="Soft delete a category. Will also delete all child categories and unassign from clients",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Category ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found"
     *     )
     * )
     */
    public function destroy($id): JsonResponse
    {
        $category = Category::active()->find($id);

        if (!$category) {
            return $this->errorResponse('Catégorie non trouvée', 404);
        }

        DB::beginTransaction();
        try {
            $clientsCount = $category->clients()->count();
            $childrenCount = $category->children()->count();

            // Supprimer (désactiver) la catégorie et tous ses enfants
            $this->deactivateCategory($category);

            Log::info('Catégorie supprimée', [
                'category_id' => $category->id,
                'name' => $category->name,
                'clients_affected' => $clientsCount,
                'children_affected' => $childrenCount,
                'deleted_by' => auth()->id(),
                'deleted_by_name' => auth()->user()->name
            ]);

            DB::commit();

            return $this->successResponse(null, 'Catégorie supprimée avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur suppression catégorie', [
                'category_id' => $id,
                'error' => $e->getMessage(),
                'deleted_by' => auth()->id()
            ]);

            return $this->errorResponse('Erreur lors de la suppression de la catégorie', 500);
        }
    }

    /**
     * Assign categories to a client
     *
     * @OA\Post(
     *     path="/v1/clients/{clientId}/categories",
     *     tags={"Categories"},
     *     summary="Assign categories to client",
     *     description="Assign one or multiple categories to a client",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="clientId",
     *         in="path",
     *         required=true,
     *         description="Client ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"category_ids"},
     *             @OA\Property(
     *                 property="category_ids",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={1, 3, 5}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Categories assigned successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client not found"
     *     )
     * )
     */
    public function assignToClient(Request $request, $clientId): JsonResponse
    {
        $client = Client::active()->find($clientId);

        if (!$client) {
            return $this->errorResponse('Client non trouvé', 404);
        }

        $request->validate([
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'required|integer|exists:categories,id'
        ]);

        DB::beginTransaction();
        try {
            // Vérifier que toutes les catégories sont actives
            $categories = Category::active()->whereIn('id', $request->category_ids)->get();

            if ($categories->count() !== count($request->category_ids)) {
                return $this->errorResponse('Une ou plusieurs catégories sont introuvables ou inactives', 404);
            }

            // Assigner les catégories avec les métadonnées d'audit
            $syncData = [];
            foreach ($request->category_ids as $categoryId) {
                $syncData[$categoryId] = [
                    'assigned_by' => auth()->id(),
                    'assigned_at' => now()
                ];
            }

            // Sync ne supprime que les catégories non présentes dans la nouvelle liste
            $client->categories()->syncWithoutDetaching($syncData);

            // Récupérer les catégories assignées pour la réponse
            $assignedCategories = $client->categories()
                                        ->with('creator:id,name')
                                        ->get();

            Log::info('Catégories assignées au client', [
                'client_id' => $client->id,
                'client_name' => $client->name,
                'category_ids' => $request->category_ids,
                'assigned_by' => auth()->id(),
                'assigned_by_name' => auth()->user()->name
            ]);

            DB::commit();

            return $this->successResponse([
                'client' => $client->only(['id', 'client_id', 'name']),
                'categories' => $assignedCategories
            ], 'Catégories assignées avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur assignation catégories', [
                'client_id' => $clientId,
                'category_ids' => $request->category_ids,
                'error' => $e->getMessage(),
                'assigned_by' => auth()->id()
            ]);

            return $this->errorResponse('Erreur lors de l\'assignation des catégories', 500);
        }
    }

    /**
     * Remove category from client
     *
     * @OA\Delete(
     *     path="/v1/clients/{clientId}/categories/{categoryId}",
     *     tags={"Categories"},
     *     summary="Remove category from client",
     *     description="Remove a specific category assignment from a client",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="clientId",
     *         in="path",
     *         required=true,
     *         description="Client ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="categoryId",
     *         in="path",
     *         required=true,
     *         description="Category ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category removed from client successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client or category not found, or not assigned"
     *     )
     * )
     */
    public function removeFromClient($clientId, $categoryId): JsonResponse
    {
        $client = Client::active()->find($clientId);
        $category = Category::active()->find($categoryId);

        if (!$client) {
            return $this->errorResponse('Client non trouvé', 404);
        }

        if (!$category) {
            return $this->errorResponse('Catégorie non trouvée', 404);
        }

        DB::beginTransaction();
        try {
            // Vérifier si la catégorie est assignée au client
            if (!$client->categories()->where('category_id', $categoryId)->exists()) {
                return $this->errorResponse('Cette catégorie n\'est pas assignée à ce client', 404);
            }

            // Retirer l'assignation
            $client->categories()->detach($categoryId);

            Log::info('Catégorie retirée du client', [
                'client_id' => $client->id,
                'client_name' => $client->name,
                'category_id' => $categoryId,
                'category_name' => $category->name,
                'removed_by' => auth()->id(),
                'removed_by_name' => auth()->user()->name
            ]);

            DB::commit();

            return $this->successResponse(null, 'Catégorie retirée du client avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur retrait catégorie', [
                'client_id' => $clientId,
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
                'removed_by' => auth()->id()
            ]);

            return $this->errorResponse('Erreur lors du retrait de la catégorie', 500);
        }
    }

    /**
     * Get client categories
     *
     * @OA\Get(
     *     path="/v1/clients/{clientId}/categories",
     *     tags={"Categories"},
     *     summary="Get client categories",
     *     description="Get all categories assigned to a specific client",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="clientId",
     *         in="path",
     *         required=true,
     *         description="Client ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client categories retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client not found"
     *     )
     * )
     */
    public function getClientCategories($clientId): JsonResponse
    {
        $client = Client::active()->find($clientId);

        if (!$client) {
            return $this->errorResponse('Client non trouvé', 404);
        }

        $categories = $client->categories()
                            ->with(['parent:id,name', 'creator:id,name'])
                            ->withPivot('assigned_by', 'assigned_at')
                            ->orderBy('type')
                            ->orderBy('name')
                            ->get();

        // Enrichir avec des informations calculées
        $categories->each(function ($category) {
            $category->append(['full_path', 'formatted_type']);
        });

        return $this->successResponse([
            'client' => $client->only(['id', 'client_id', 'name']),
            'categories' => $categories,
            'categories_count' => $categories->count()
        ], 'Catégories du client récupérées avec succès');
    }

    /**
     * Méthode récursive pour désactiver une catégorie et tous ses enfants
     */
    private function deactivateCategory(Category $category): void
    {
        // Désactiver la catégorie actuelle
        $category->update(['is_active' => false]);

        // Désactiver récursivement tous les enfants
        foreach ($category->children as $child) {
            $this->deactivateCategory($child);
        }
    }

    /**
     * Méthode helper pour les réponses de succès
     */
    private function successResponse($data, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * Méthode helper pour les réponses d'erreur
     */
    private function errorResponse(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $status);
    }
}
