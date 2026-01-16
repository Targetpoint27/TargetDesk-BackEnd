<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SupplierController extends BaseApiController
{
    /**
     * @OA\Post(
     *     path="/v1/suppliers",
     *     tags={"Suppliers"},
     *     summary="Create a new supplier",
     *     description="Create a new supplier record with automatic ID generation",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","type","email"},
     *             @OA\Property(property="name", type="string", example="Fournisseur ACME"),
     *             @OA\Property(property="type", type="string", enum={"particulier","entreprise"}, example="entreprise"),
     *             @OA\Property(property="email", type="string", format="email", example="contact@acme-supplier.com"),
     *             @OA\Property(property="phone", type="string", example="0123456789"),
     *             @OA\Property(property="address", type="string", example="123 Rue du Fournisseur, 75001 Paris"),
     *             @OA\Property(property="siret", type="string", example="12345678901234"),
     *             @OA\Property(property="sector", type="string", example="Distribution"),
     *             @OA\Property(property="website", type="string", example="https://acme-supplier.com"),
     *             @OA\Property(property="notes", type="string", example="Notes importantes"),
     *             @OA\Property(property="relation_type", type="string", enum={"fournisseur","client_et_fournisseur"}, example="fournisseur"),
     *             @OA\Property(property="payment_terms", type="string", example="30 jours net"),
     *             @OA\Property(property="delivery_delay", type="integer", example=7),
     *             @OA\Property(property="currency", type="string", example="EUR")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Supplier created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Fournisseur créé avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="supplier_id", type="string", example="FOUR-ABC123XY"),
     *                 @OA\Property(property="name", type="string", example="Fournisseur ACME"),
     *                 @OA\Property(property="type", type="string", example="entreprise"),
     *                 @OA\Property(property="email", type="string", example="contact@acme-supplier.com")
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
            'email' => 'required|email|unique:suppliers,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'siret' => 'nullable|string|size:14|unique:suppliers,siret',
            'sector' => 'nullable|string|max:100',
            'website' => 'nullable|url',
            'notes' => 'nullable|string',
            'relation_type' => 'nullable|in:fournisseur,client_et_fournisseur',
            'payment_terms' => 'nullable|string|max:100',
            'delivery_delay' => 'nullable|integer|min:0|max:365',
            'currency' => 'nullable|string|size:3'
        ], [
            'name.required' => 'Le nom/raison sociale est requis',
            'type.required' => 'Le type est requis',
            'type.in' => 'Le type doit être "particulier" ou "entreprise"',
            'email.required' => 'L\'email principal est requis',
            'email.email' => 'L\'email doit être valide',
            'email.unique' => 'Cet email est déjà utilisé',
            'siret.size' => 'Le SIRET doit contenir exactement 14 caractères',
            'siret.unique' => 'Ce SIRET est déjà utilisé',
            'website.url' => 'Le site web doit être une URL valide',
            'relation_type.in' => 'Le type de relation doit être "fournisseur" ou "client_et_fournisseur"',
            'delivery_delay.integer' => 'Le délai de livraison doit être un nombre entier',
            'delivery_delay.min' => 'Le délai de livraison doit être positif',
            'delivery_delay.max' => 'Le délai de livraison ne peut pas dépasser 365 jours',
            'currency.size' => 'La devise doit être un code ISO 4217 de 3 caractères'
        ]);

        $validated['created_by'] = auth()->id();

        $supplier = Supplier::create($validated);

        // Log d'audit
        Log::info('Fournisseur créé', [
            'supplier_id' => $supplier->supplier_id,
            'name' => $supplier->name,
            'relation_type' => $supplier->relation_type,
            'created_by' => auth()->id(),
            'created_by_name' => auth()->user()->name
        ]);

        return $this->successResponse([
            'id' => $supplier->id,
            'supplier_id' => $supplier->supplier_id,
            'name' => $supplier->name,
            'type' => $supplier->type,
            'email' => $supplier->email,
            'phone' => $supplier->phone,
            'address' => $supplier->address,
            'siret' => $supplier->siret,
            'sector' => $supplier->sector,
            'website' => $supplier->website,
            'notes' => $supplier->notes,
            'relation_type' => $supplier->relation_type,
            'payment_terms' => $supplier->payment_terms,
            'delivery_delay' => $supplier->delivery_delay,
            'currency' => $supplier->currency,
            'is_active' => $supplier->is_active,
            'created_at' => $supplier->created_at
        ], 'Fournisseur créé avec succès', 201);
    }

    /**
     * @OA\Get(
     *     path="/v1/suppliers",
     *     tags={"Suppliers"},
     *     summary="List all suppliers",
     *     description="Get a paginated list of all active suppliers",
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
     *     @OA\Parameter(
     *         name="relation_type",
     *         in="query",
     *         description="Filter by relation type",
     *         @OA\Schema(type="string", enum={"fournisseur","client_et_fournisseur"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Suppliers retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Fournisseurs récupérés avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="suppliers", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="pagination", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 100);

        $query = Supplier::with('creator:id,name')
            ->where('is_active', true)
            ->orderBy('created_at', 'desc');

        // Filtrer par type de relation si spécifié
        if ($request->has('relation_type')) {
            $query->where('relation_type', $request->get('relation_type'));
        }

        $suppliers = $query->paginate($perPage);

        return $this->successResponse([
            'suppliers' => $suppliers->items(),
            'pagination' => [
                'current_page' => $suppliers->currentPage(),
                'total_pages' => $suppliers->lastPage(),
                'total_items' => $suppliers->total(),
                'per_page' => $suppliers->perPage()
            ]
        ], 'Fournisseurs récupérés avec succès');
    }

    /**
     * @OA\Get(
     *     path="/v1/suppliers/{id}",
     *     tags={"Suppliers"},
     *     summary="Get supplier details",
     *     description="Get detailed information about a specific supplier",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Supplier ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Supplier retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Fournisseur trouvé"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Supplier not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Fournisseur non trouvé")
     *         )
     *     )
     * )
     */
    public function show($id): JsonResponse
    {
        $supplier = Supplier::with('creator:id,name')->where('is_active', true)->find($id);

        if (!$supplier) {
            return $this->errorResponse('Fournisseur non trouvé', 404);
        }

        return $this->successResponse($supplier, 'Fournisseur trouvé');
    }

    /**
     * @OA\Put(
     *     path="/v1/suppliers/{id}",
     *     tags={"Suppliers"},
     *     summary="Update supplier",
     *     description="Update an existing supplier record",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Supplier ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Fournisseur ACME Modifié"),
     *             @OA\Property(property="type", type="string", enum={"particulier","entreprise"}),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="siret", type="string"),
     *             @OA\Property(property="sector", type="string"),
     *             @OA\Property(property="website", type="string"),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(property="relation_type", type="string", enum={"fournisseur","client_et_fournisseur"}),
     *             @OA\Property(property="payment_terms", type="string"),
     *             @OA\Property(property="delivery_delay", type="integer"),
     *             @OA\Property(property="currency", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Supplier updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Supplier not found"
     *     )
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        $supplier = Supplier::where('is_active', true)->find($id);

        if (!$supplier) {
            return $this->errorResponse('Fournisseur non trouvé', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:particulier,entreprise',
            'email' => 'sometimes|required|email|unique:suppliers,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'siret' => 'nullable|string|size:14|unique:suppliers,siret,' . $id,
            'sector' => 'nullable|string|max:100',
            'website' => 'nullable|url',
            'notes' => 'nullable|string',
            'relation_type' => 'sometimes|required|in:fournisseur,client_et_fournisseur',
            'payment_terms' => 'nullable|string|max:100',
            'delivery_delay' => 'nullable|integer|min:0|max:365',
            'currency' => 'nullable|string|size:3'
        ]);

        $supplier->update($validated);

        // Log d'audit
        Log::info('Fournisseur modifié', [
            'supplier_id' => $supplier->supplier_id,
            'name' => $supplier->name,
            'updated_by' => auth()->id(),
            'updated_by_name' => auth()->user()->name,
            'changes' => $validated
        ]);

        return $this->successResponse($supplier->fresh(), 'Fournisseur mis à jour avec succès');
    }

    /**
     * @OA\Delete(
     *     path="/v1/suppliers/{id}",
     *     tags={"Suppliers"},
     *     summary="Delete supplier",
     *     description="Soft delete a supplier record (set as inactive)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Supplier ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Supplier deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Fournisseur supprimé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Supplier not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Fournisseur non trouvé")
     *         )
     *     )
     * )
     */
    public function destroy($id): JsonResponse
    {
        $supplier = Supplier::where('is_active', true)->find($id);

        if (!$supplier) {
            return $this->errorResponse('Fournisseur non trouvé', 404);
        }

        // Soft delete - désactivation plutôt que suppression physique
        $supplier->update(['is_active' => false]);

        // Log d'audit
        Log::info('Fournisseur supprimé (désactivé)', [
            'supplier_id' => $supplier->supplier_id,
            'name' => $supplier->name,
            'deleted_by' => auth()->id(),
            'deleted_by_name' => auth()->user()->name
        ]);

        return $this->successResponse(null, 'Fournisseur supprimé avec succès');
    }
}
