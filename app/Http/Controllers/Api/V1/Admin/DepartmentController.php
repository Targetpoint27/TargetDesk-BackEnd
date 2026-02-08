<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/departments",
     *     summary="Lister tous les départements",
     *     description="Récupère la liste de tous les départements avec compteur d'appels",
     *     tags={"Admin - Departments"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des départements récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Liste des départements récupérée"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="code", type="string"),
     *                     @OA\Property(property="is_active", type="boolean"),
     *                     @OA\Property(property="calls_count", type="integer")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $departments = Department::withCount(['calls'])
                                     ->with(['manager'])
                                     ->orderBy('name')
                                     ->get();

            Log::info('Liste des départements consultée', [
                'consulted_by' => Auth::id(),
                'total' => $departments->count(),
            ]);

            return $this->successResponse(
                $departments,
                'Liste des départements récupérée',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des départements', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la récupération des départements',
                500
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/departments",
     *     summary="Créer un département",
     *     description="Crée un nouveau département",
     *     tags={"Admin - Departments"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "code"},
     *             @OA\Property(property="name", type="string", example="Support Niveau 2"),
     *             @OA\Property(property="code", type="string", maxLength=10, example="SUP2"),
     *             @OA\Property(property="description", type="string", example="Support technique niveau 2"),
     *             @OA\Property(property="manager_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Département créé avec succès"
     *     ),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:departments,name',
                'code' => 'required|string|max:10|unique:departments,code',
                'description' => 'nullable|string',
                'manager_id' => 'nullable|exists:users,id',
            ], [
                'name.required' => 'Le nom du département est obligatoire',
                'name.unique' => 'Ce nom de département existe déjà',
                'code.required' => 'Le code du département est obligatoire',
                'code.max' => 'Le code ne peut pas dépasser 10 caractères',
                'code.unique' => 'Ce code de département existe déjà',
                'manager_id.exists' => 'Le responsable sélectionné n\'existe pas',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(
                    $validator->errors()->first(),
                    422
                );
            }

            $department = Department::create([
                'name' => $request->name,
                'code' => $request->code,
                'description' => $request->description,
                'manager_id' => $request->manager_id,
                'is_active' => true,
            ]);

            $department->load('manager');

            Log::info('Département créé', [
                'department_id' => $department->id,
                'name' => $department->name,
                'code' => $department->code,
                'created_by' => Auth::id(),
            ]);

            return $this->successResponse(
                $department,
                'Département créé avec succès',
                201
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du département', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la création du département',
                500
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/departments/{id}",
     *     summary="Voir détails d'un département",
     *     description="Récupère les détails complets d'un département",
     *     tags={"Admin - Departments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Détails du département"),
     *     @OA\Response(response=404, description="Département non trouvé")
     * )
     */
    public function show($id)
    {
        try {
            $department = Department::withCount(['calls'])
                                    ->with(['manager'])
                                    ->find($id);

            if (!$department) {
                return $this->errorResponse('Département non trouvé', 404);
            }

            Log::info('Détails département consultés', [
                'department_id' => $id,
                'consulted_by' => Auth::id(),
            ]);

            return $this->successResponse(
                $department,
                'Département trouvé',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du département', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la récupération du département',
                500
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/departments/{id}",
     *     summary="Modifier un département",
     *     description="Met à jour les informations d'un département",
     *     tags={"Admin - Departments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="code", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="manager_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Département modifié"),
     *     @OA\Response(response=404, description="Département non trouvé")
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $department = Department::find($id);

            if (!$department) {
                return $this->errorResponse('Département non trouvé', 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255|unique:departments,name,' . $id,
                'code' => 'sometimes|string|max:10|unique:departments,code,' . $id,
                'description' => 'nullable|string',
                'manager_id' => 'nullable|exists:users,id',
            ], [
                'name.unique' => 'Ce nom de département existe déjà',
                'code.unique' => 'Ce code de département existe déjà',
                'manager_id.exists' => 'Le responsable sélectionné n\'existe pas',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(
                    $validator->errors()->first(),
                    422
                );
            }

            $department->update($request->only(['name', 'code', 'description', 'manager_id']));

            $department->load('manager');

            Log::info('Département modifié', [
                'department_id' => $department->id,
                'modified_by' => Auth::id(),
            ]);

            return $this->successResponse(
                $department,
                'Département modifié avec succès',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la modification du département', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la modification du département',
                500
            );
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/departments/{id}",
     *     summary="Supprimer un département",
     *     description="Suppression logique d'un département (soft delete)",
     *     tags={"Admin - Departments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Département supprimé"),
     *     @OA\Response(response=404, description="Département non trouvé")
     * )
     */
    public function destroy($id)
    {
        try {
            $department = Department::find($id);

            if (!$department) {
                return $this->errorResponse('Département non trouvé', 404);
            }

            $department->delete();

            Log::info('Département supprimé', [
                'department_id' => $id,
                'deleted_by' => Auth::id(),
            ]);

            return $this->successResponse(
                null,
                'Département supprimé avec succès',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du département', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la suppression du département',
                500
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/departments/{id}/toggle-status",
     *     summary="Activer/Désactiver un département",
     *     description="Change le statut actif/inactif d'un département",
     *     tags={"Admin - Departments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Statut modifié"),
     *     @OA\Response(response=404, description="Département non trouvé")
     * )
     */
    public function toggleStatus($id)
    {
        try {
            $department = Department::find($id);

            if (!$department) {
                return $this->errorResponse('Département non trouvé', 404);
            }

            $department->is_active = !$department->is_active;
            $department->save();

            $status = $department->is_active ? 'activé' : 'désactivé';

            Log::info('Statut du département modifié', [
                'department_id' => $id,
                'new_status' => $department->is_active,
                'changed_by' => Auth::id(),
            ]);

            return $this->successResponse(
                $department,
                "Département {$status} avec succès",
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors du changement de statut', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Erreur lors du changement de statut',
                500
            );
        }
    }
}