<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Roles",
 *     description="API pour la gestion des rôles"
 * )
 */
class RoleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/roles",
     *     summary="Lister tous les rôles",
     *     tags={"Roles"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="include_permissions",
     *         in="query",
     *         description="Inclure les permissions des rôles",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="filter",
     *         in="query",
     *         description="Filtrer par type (predefined, custom, all)",
     *         @OA\Schema(type="string", enum={"predefined", "custom", "all"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des rôles récupérée avec succès"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Role::active();

        // Filter by type
        $filter = $request->get('filter', 'all');
        if ($filter === 'predefined') {
            $query->predefined();
        } elseif ($filter === 'custom') {
            $query->custom();
        }

        // Include permissions if requested
        if ($request->boolean('include_permissions')) {
            $query->with('permissions');
        }

        $roles = $query->orderBy('is_predefined', 'desc')
                      ->orderBy('name')
                      ->get();

        return response()->json([
            'success' => true,
            'message' => 'Roles retrieved successfully',
            'data' => [
                'roles' => $roles,
                'total' => $roles->count()
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/roles",
     *     summary="Créer un nouveau rôle personnalisé",
     *     tags={"Roles"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="custom_manager"),
     *             @OA\Property(property="display_name", type="string", example="Gestionnaire personnalisé"),
     *             @OA\Property(property="description", type="string", example="Rôle de gestionnaire avec permissions spécifiques"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Rôle créé avec succès"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:roles,name',
                'display_name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'permissions' => 'array',
                'permissions.*' => 'exists:permissions,id'
            ]);

            DB::beginTransaction();

            $role = Role::create([
                'name' => $validated['name'],
                'display_name' => $validated['display_name'],
                'description' => $validated['description'] ?? null,
                'is_predefined' => false,
                'is_active' => true,
                'created_by' => auth()->id()
            ]);

            // Attach permissions if provided
            if (!empty($validated['permissions'])) {
                $permissionData = [];
                foreach ($validated['permissions'] as $permissionId) {
                    $permissionData[$permissionId] = [
                        'granted_by' => auth()->id(),
                        'granted_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
                $role->permissions()->attach($permissionData);
            }

            DB::commit();

            $role->load('permissions');

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully',
                'data' => ['role' => $role]
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the role'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/roles/{id}",
     *     summary="Détails d'un rôle spécifique",
     *     tags={"Roles"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du rôle récupérés avec succès"
     *     )
     * )
     */
    public function show(Role $role): JsonResponse
    {
        $role->load(['permissions', 'creator', 'users']);

        return response()->json([
            'success' => true,
            'message' => 'Role retrieved successfully',
            'data' => [
                'role' => $role,
                'permissions_by_module' => $role->getPermissionsByModule(),
                'users_count' => $role->users()->count(),
                'can_be_deleted' => $role->canBeDeleted()
            ]
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/roles/{id}",
     *     summary="Modifier un rôle",
     *     tags={"Roles"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="display_name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rôle modifié avec succès"
     *     )
     * )
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        try {
            $validated = $request->validate([
                'display_name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'permissions' => 'array',
                'permissions.*' => 'exists:permissions,id',
                'is_active' => 'boolean'
            ]);

            DB::beginTransaction();

            $role->update($validated);

            // Update permissions if provided
            if (isset($validated['permissions'])) {
                $permissionData = [];
                foreach ($validated['permissions'] as $permissionId) {
                    $permissionData[$permissionId] = [
                        'granted_by' => auth()->id(),
                        'granted_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
                $role->permissions()->sync($permissionData);
            }

            DB::commit();

            $role->load('permissions');

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully',
                'data' => ['role' => $role]
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the role'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/roles/{id}",
     *     summary="Supprimer un rôle personnalisé",
     *     tags={"Roles"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rôle supprimé avec succès"
     *     )
     * )
     */
    public function destroy(Role $role): JsonResponse
    {
        if (!$role->canBeDeleted()) {
            return response()->json([
                'success' => false,
                'message' => 'This role cannot be deleted (predefined role or has assigned users)'
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Detach all permissions
            $role->permissions()->detach();

            // Delete the role
            $role->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the role'
            ], 500);
        }
    }
}
