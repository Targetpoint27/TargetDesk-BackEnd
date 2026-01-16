<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Contact;
use App\Models\Client;
use App\Models\Supplier;
use App\Http\Requests\Api\StoreContactRequest;
use App\Http\Requests\Api\UpdateContactRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ContactController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/v1/clients/{clientId}/contacts",
     *     tags={"Contacts"},
     *     summary="List client contacts",
     *     description="Get all contacts for a specific client, ordered by primary contact first",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="clientId",
     *         in="path",
     *         required=true,
     *         description="Client ID",
     *         @OA\Schema(type="integer")
     *     ),
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
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contacts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Contacts récupérés avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="contacts", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="pagination", type="object"),
     *                 @OA\Property(property="client", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Client not found")
     * )
     */
    public function index(Request $request, $clientId): JsonResponse
    {
        $client = Client::find($clientId);

        if (!$client) {
            return $this->errorResponse('Client non trouvé', 404);
        }

        $perPage = min($request->get('per_page', 10), 100);

        $contacts = Contact::with(['emails', 'phones', 'creator:id,name'])
            ->forClient($clientId)
            ->active()
            ->orderedByPrimary()
            ->paginate($perPage);

        return $this->successResponse([
            'contacts' => $contacts->items(),
            'pagination' => [
                'current_page' => $contacts->currentPage(),
                'total_pages' => $contacts->lastPage(),
                'total_items' => $contacts->total(),
                'per_page' => $contacts->perPage()
            ],
            'client' => [
                'id' => $client->id,
                'client_id' => $client->client_id,
                'name' => $client->name
            ]
        ], 'Contacts récupérés avec succès');
    }

    /**
     * @OA\Post(
     *     path="/v1/clients/{clientId}/contacts",
     *     tags={"Contacts"},
     *     summary="Create a new contact",
     *     description="Add a new contact to a client",
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
     *             required={"first_name","last_name","emails"},
     *             @OA\Property(property="civility", type="string", enum={"M.","Mme","Dr.","Prof.","Maître"}),
     *             @OA\Property(property="first_name", type="string", example="Jean"),
     *             @OA\Property(property="last_name", type="string", example="Dupont"),
     *             @OA\Property(property="function", type="string", example="Directeur Commercial"),
     *             @OA\Property(property="department", type="string", example="Ventes"),
     *             @OA\Property(property="is_primary", type="boolean", example=false),
     *             @OA\Property(property="emails", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="email", type="string", example="jean.dupont@acme.com"),
     *                     @OA\Property(property="type", type="string", enum={"professionnel","personnel"}),
     *                     @OA\Property(property="is_primary", type="boolean", example=true)
     *                 )
     *             ),
     *             @OA\Property(property="phones", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="phone", type="string", example="0123456789"),
     *                     @OA\Property(property="type", type="string", enum={"bureau","mobile","fax","autre"}),
     *                     @OA\Property(property="is_primary", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Contact created successfully"
     *     ),
     *     @OA\Response(response=404, description="Client not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreContactRequest $request, $clientId): JsonResponse
    {
        $client = Client::find($clientId);

        if (!$client) {
            return $this->errorResponse('Client non trouvé', 404);
        }

        $validated = $request->validated();
        $validated['client_id'] = $clientId;
        $validated['created_by'] = auth()->id();

        DB::beginTransaction();
        try {
            // Créer le contact
            $contact = Contact::create($validated);

            // Si c'est le premier contact du client, le rendre principal automatiquement
            $existingContactsCount = Contact::forClient($clientId)->active()->count();
            if ($existingContactsCount === 1) {
                $contact->update(['is_primary' => true]);
            }

            // Gérer les emails
            if (isset($validated['emails'])) {
                foreach ($validated['emails'] as $emailData) {
                    $contact->emails()->create($emailData);
                }
            }

            // Gérer les téléphones
            if (isset($validated['phones'])) {
                foreach ($validated['phones'] as $phoneData) {
                    $contact->phones()->create($phoneData);
                }
            }

            // Charger les relations pour la réponse
            $contact->load(['emails', 'phones', 'client:id,client_id,name']);

            Log::info('Contact créé', [
                'contact_id' => $contact->id,
                'client_id' => $clientId,
                'full_name' => $contact->full_name,
                'created_by' => auth()->id(),
                'created_by_name' => auth()->user()->name
            ]);

            DB::commit();

            return $this->successResponse($contact, 'Contact créé avec succès', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création contact', [
                'client_id' => $clientId,
                'error' => $e->getMessage(),
                'created_by' => auth()->id()
            ]);

            return $this->errorResponse('Erreur lors de la création du contact', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/v1/contacts/{id}",
     *     tags={"Contacts"},
     *     summary="Get contact details",
     *     description="Get detailed information about a specific contact",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Contact ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contact retrieved successfully"
     *     ),
     *     @OA\Response(response=404, description="Contact not found")
     * )
     */
    public function show($id): JsonResponse
    {
        $contact = Contact::with(['emails', 'phones', 'client:id,client_id,name', 'creator:id,name'])
            ->active()
            ->find($id);

        if (!$contact) {
            return $this->errorResponse('Contact non trouvé', 404);
        }

        return $this->successResponse($contact, 'Contact trouvé');
    }

    /**
     * @OA\Put(
     *     path="/v1/contacts/{id}",
     *     tags={"Contacts"},
     *     summary="Update contact",
     *     description="Update an existing contact",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Contact ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Contact updated successfully"),
     *     @OA\Response(response=404, description="Contact not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateContactRequest $request, $id): JsonResponse
    {
        $contact = Contact::with(['emails', 'phones'])->active()->find($id);

        if (!$contact) {
            return $this->errorResponse('Contact non trouvé', 404);
        }

        $validated = $request->validated();
        $originalData = $contact->toArray();

        DB::beginTransaction();
        try {
            // Mettre à jour les informations du contact
            $contact->update($validated);

            // Gérer les emails si fournis
            if (isset($validated['emails'])) {
                $contact->emails()->delete();
                foreach ($validated['emails'] as $emailData) {
                    $contact->emails()->create($emailData);
                }
            }

            // Gérer les téléphones si fournis
            if (isset($validated['phones'])) {
                $contact->phones()->delete();
                foreach ($validated['phones'] as $phoneData) {
                    $contact->phones()->create($phoneData);
                }
            }

            // Charger les relations pour la réponse
            $contact->load(['emails', 'phones', 'client:id,client_id,name', 'supplier:id,supplier_id,name']);

            // Calculer les changements sans les arrays complexes
            $contactChanges = array_intersect_key($validated, array_flip(['civility', 'first_name', 'last_name', 'function', 'department', 'is_primary']));
            $originalContactData = array_intersect_key($originalData, array_flip(['civility', 'first_name', 'last_name', 'function', 'department', 'is_primary']));

            Log::info('Contact modifié', [
                'contact_id' => $contact->id,
                'client_id' => $contact->client_id,
                'supplier_id' => $contact->supplier_id,
                'full_name' => $contact->full_name,
                'updated_by' => auth()->id(),
                'updated_by_name' => auth()->user()->name,
                'contact_changes' => array_diff_assoc($contactChanges, $originalContactData),
                'emails_updated' => isset($validated['emails']),
                'phones_updated' => isset($validated['phones'])
            ]);

            DB::commit();

            return $this->successResponse($contact->fresh(), 'Contact mis à jour avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur modification contact', [
                'contact_id' => $id,
                'error' => $e->getMessage(),
                'updated_by' => auth()->id()
            ]);

            return $this->errorResponse('Erreur lors de la modification du contact', 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/v1/contacts/{id}/make-primary",
     *     tags={"Contacts"},
     *     summary="Make contact primary",
     *     description="Set a contact as the primary contact for its client",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Contact ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Contact set as primary successfully"),
     *     @OA\Response(response=404, description="Contact not found")
     * )
     */
    public function makePrimary($id): JsonResponse
    {
        $contact = Contact::active()->find($id);

        if (!$contact) {
            return $this->errorResponse('Contact non trouvé', 404);
        }

        if ($contact->is_primary) {
            return $this->successResponse($contact, 'Contact déjà principal');
        }

        $contact->makePrimary();

        Log::info('Contact défini comme principal', [
            'contact_id' => $contact->id,
            'client_id' => $contact->client_id,
            'full_name' => $contact->full_name,
            'updated_by' => auth()->id(),
            'updated_by_name' => auth()->user()->name
        ]);

        return $this->successResponse($contact->fresh(), 'Contact défini comme principal');
    }

    /**
     * @OA\Delete(
     *     path="/v1/contacts/{id}",
     *     tags={"Contacts"},
     *     summary="Delete contact",
     *     description="Soft delete a contact (set as inactive)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Contact ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Contact deleted successfully"),
     *     @OA\Response(response=404, description="Contact not found"),
     *     @OA\Response(response=422, description="Cannot delete primary contact")
     * )
     */
    public function destroy($id): JsonResponse
    {
        $contact = Contact::active()->find($id);

        if (!$contact) {
            return $this->errorResponse('Contact non trouvé', 404);
        }

        // Vérifier si c'est le contact principal et s'il y a d'autres contacts
        $otherActiveContacts = Contact::forClient($contact->client_id)
            ->active()
            ->where('id', '!=', $contact->id)
            ->count();

        if ($contact->is_primary && $otherActiveContacts > 0) {
            return $this->errorResponse(
                'Impossible de supprimer le contact principal. Définissez d\'abord un autre contact comme principal.',
                422
            );
        }

        // Suppression logique
        $contact->update(['is_active' => false]);

        // Si c'était le seul contact, pas de problème de contact principal
        // Si c'était le contact principal et le seul, il n'y a plus de contact principal

        Log::info('Contact supprimé (désactivé)', [
            'contact_id' => $contact->id,
            'client_id' => $contact->client_id,
            'full_name' => $contact->full_name,
            'was_primary' => $contact->is_primary,
            'deleted_by' => auth()->id(),
            'deleted_by_name' => auth()->user()->name
        ]);

        return $this->successResponse(null, 'Contact supprimé avec succès');
    }

    /**
     * @OA\Get(
     *     path="/v1/suppliers/{supplierId}/contacts",
     *     tags={"Contacts"},
     *     summary="List supplier contacts",
     *     description="Get all contacts for a specific supplier, ordered by primary contact first",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="supplierId",
     *         in="path",
     *         required=true,
     *         description="Supplier ID",
     *         @OA\Schema(type="integer")
     *     ),
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
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contacts retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Supplier not found"
     *     )
     * )
     */
    public function indexForSupplier(Request $request, $supplierId): JsonResponse
    {
        $supplier = Supplier::where('is_active', true)->find($supplierId);

        if (!$supplier) {
            return $this->errorResponse('Fournisseur non trouvé', 404);
        }

        $perPage = min($request->get('per_page', 10), 100);

        $contacts = Contact::with(['emails', 'phones', 'creator:id,name'])
            ->forSupplier($supplierId)
            ->active()
            ->orderedByPrimary()
            ->paginate($perPage);

        return $this->successResponse([
            'contacts' => $contacts->items(),
            'pagination' => [
                'current_page' => $contacts->currentPage(),
                'total_pages' => $contacts->lastPage(),
                'total_items' => $contacts->total(),
                'per_page' => $contacts->perPage()
            ],
            'supplier' => [
                'id' => $supplier->id,
                'supplier_id' => $supplier->supplier_id,
                'name' => $supplier->name
            ]
        ], 'Contacts récupérés avec succès');
    }

    /**
     * @OA\Post(
     *     path="/v1/suppliers/{supplierId}/contacts",
     *     tags={"Contacts"},
     *     summary="Create a new supplier contact",
     *     description="Add a new contact to a supplier",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="supplierId",
     *         in="path",
     *         required=true,
     *         description="Supplier ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name","last_name","emails"},
     *             @OA\Property(property="civility", type="string", example="M."),
     *             @OA\Property(property="first_name", type="string", example="Jean"),
     *             @OA\Property(property="last_name", type="string", example="Dupont"),
     *             @OA\Property(property="function", type="string", example="Responsable commercial"),
     *             @OA\Property(property="department", type="string", example="Ventes"),
     *             @OA\Property(property="emails", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="email", type="string", example="jean.dupont@supplier.com"),
     *                 @OA\Property(property="type", type="string", enum={"professionnel","personnel"}, example="professionnel"),
     *                 @OA\Property(property="is_primary", type="boolean", example=true)
     *             )),
     *             @OA\Property(property="phones", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="phone", type="string", example="0123456789"),
     *                 @OA\Property(property="type", type="string", enum={"bureau","mobile","fax","autre"}, example="bureau"),
     *                 @OA\Property(property="is_primary", type="boolean", example=true)
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Contact created successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Supplier not found"
     *     )
     * )
     */
    public function storeForSupplier(StoreContactRequest $request, $supplierId): JsonResponse
    {
        $supplier = Supplier::where('is_active', true)->find($supplierId);

        if (!$supplier) {
            return $this->errorResponse('Fournisseur non trouvé', 404);
        }

        return DB::transaction(function () use ($request, $supplierId) {
            $validated = $request->validated();
            $validated['supplier_id'] = $supplierId;
            $validated['created_by'] = auth()->id();

            // Créer le contact
            $contact = Contact::create([
                'supplier_id' => $validated['supplier_id'],
                'civility' => $validated['civility'] ?? null,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'function' => $validated['function'] ?? null,
                'department' => $validated['department'] ?? null,
                'is_primary' => false, // Sera défini par l'observer si nécessaire
                'created_by' => $validated['created_by']
            ]);

            // Ajouter les emails
            foreach ($validated['emails'] as $emailData) {
                $contact->emails()->create([
                    'email' => $emailData['email'],
                    'type' => $emailData['type'],
                    'is_primary' => $emailData['is_primary'] ?? false
                ]);
            }

            // Ajouter les téléphones si fournis
            if (isset($validated['phones'])) {
                foreach ($validated['phones'] as $phoneData) {
                    $contact->phones()->create([
                        'phone' => $phoneData['phone'],
                        'type' => $phoneData['type'],
                        'is_primary' => $phoneData['is_primary'] ?? false
                    ]);
                }
            }

            // Recharger le contact avec ses relations
            $contact->load(['emails', 'phones', 'creator:id,name', 'supplier:id,supplier_id,name']);

            Log::info('Contact fournisseur créé', [
                'contact_id' => $contact->id,
                'supplier_id' => $contact->supplier_id,
                'full_name' => $contact->full_name,
                'is_primary' => $contact->is_primary,
                'created_by' => auth()->id(),
                'created_by_name' => auth()->user()->name
            ]);

            return $this->successResponse($contact, 'Contact créé avec succès', 201);
        });
    }

    /**
     * @OA\Get(
     *     path="/v1/contacts",
     *     tags={"Contacts"},
     *     summary="List all contacts",
     *     description="Get all contacts from both clients and suppliers with entity information",
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
     *         name="search",
     *         in="query",
     *         description="Search in contact names and emails",
     *         @OA\Schema(type="string", example="jean")
     *     ),
     *     @OA\Parameter(
     *         name="entity_type",
     *         in="query",
     *         description="Filter by entity type",
     *         @OA\Schema(type="string", enum={"client","supplier"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="All contacts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Contacts récupérés avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="contacts", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="pagination", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function indexAll(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 100);
        $search = $request->get('search');
        $entityType = $request->get('entity_type');

        $query = Contact::with(['emails', 'phones', 'creator:id,name', 'client:id,client_id,name', 'supplier:id,supplier_id,name'])
            ->active()
            ->orderByDesc('is_primary')
            ->orderBy('last_name')
            ->orderBy('first_name');

        // Filtrage par type d'entité
        if ($entityType === 'client') {
            $query->whereNotNull('client_id');
        } elseif ($entityType === 'supplier') {
            $query->whereNotNull('supplier_id');
        }

        // Recherche dans les noms et emails
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhereHas('emails', function ($emailQuery) use ($search) {
                      $emailQuery->where('email', 'like', "%{$search}%");
                  });
            });
        }

        $contacts = $query->paginate($perPage);

        // Enrichir chaque contact avec des informations sur l'entité
        $enrichedContacts = $contacts->getCollection()->map(function ($contact) {
            $contactArray = $contact->toArray();

            // Ajouter des informations sur le type d'entité
            $contactArray['entity_type'] = $contact->client_id ? 'client' : 'supplier';
            $contactArray['entity_name'] = $contact->client ? $contact->client->name : ($contact->supplier ? $contact->supplier->name : null);
            $contactArray['entity_id'] = $contact->client ? $contact->client->client_id : ($contact->supplier ? $contact->supplier->supplier_id : null);

            return $contactArray;
        });

        $contacts->setCollection($enrichedContacts);

        return $this->successResponse([
            'contacts' => $contacts->items(),
            'pagination' => [
                'current_page' => $contacts->currentPage(),
                'total_pages' => $contacts->lastPage(),
                'total_items' => $contacts->total(),
                'per_page' => $contacts->perPage()
            ],
            'filters' => [
                'search' => $search,
                'entity_type' => $entityType
            ]
        ], 'Contacts récupérés avec succès');
    }
}