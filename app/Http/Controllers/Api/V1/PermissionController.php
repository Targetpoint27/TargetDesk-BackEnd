<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Permissions",
 *     description="API pour la gestion des permissions"
 * )
 */
class PermissionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/permissions",
     *     summary="Lister toutes les permissions disponibles",
     *     tags={"Permissions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="module",
     *         in="query",
     *         description="Filtrer par module",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="action",
     *         in="query",
     *         description="Filtrer par action",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="scope",
     *         in="query",
     *         description="Filtrer par scope",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="grouped",
     *         in="query",
     *         description="Grouper les permissions par module",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permissions récupérées avec succès"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Permission::active();

        // Apply filters
        if ($request->has('module')) {
            $query->byModule($request->get('module'));
        }

        if ($request->has('action')) {
            $query->byAction($request->get('action'));
        }

        if ($request->has('scope')) {
            $query->byScope($request->get('scope'));
        }

        $permissions = $query->orderBy('module')
                            ->orderBy('action')
                            ->orderBy('scope')
                            ->get();

        // Group by module if requested
        if ($request->boolean('grouped')) {
            $groupedPermissions = $permissions->groupBy('module');

            return response()->json([
                'success' => true,
                'message' => 'Permissions retrieved successfully',
                'data' => [
                    'permissions' => $groupedPermissions,
                    'modules' => array_keys($groupedPermissions->toArray()),
                    'total' => $permissions->count()
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Permissions retrieved successfully',
            'data' => [
                'permissions' => $permissions,
                'total' => $permissions->count()
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/permissions/modules",
     *     summary="Lister les modules du système",
     *     tags={"Permissions"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Modules récupérés avec succès"
     *     )
     * )
     */
    public function modules(): JsonResponse
    {
        $modules = Permission::getModules();
        $actions = Permission::getActions();
        $scopes = Permission::getScopes();

        return response()->json([
            'success' => true,
            'message' => 'Modules retrieved successfully',
            'data' => [
                'modules' => $modules,
                'available_actions' => $actions,
                'available_scopes' => $scopes
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/permissions/check",
     *     summary="Vérifier une permission spécifique",
     *     tags={"Permissions"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="action", type="string", example="read"),
     *             @OA\Property(property="resource", type="string", example="clients"),
     *             @OA\Property(property="scope", type="string", example="own"),
     *             @OA\Property(property="resource_id", type="integer", example=123)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission vérifiée avec succès"
     *     )
     * )
     */
    public function checkPermission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string',
            'resource' => 'required|string',
            'scope' => 'string|in:global,own,team',
            'resource_id' => 'integer'
        ]);

        $user = auth()->user();
        $action = $validated['action'];
        $resource = $validated['resource'];
        $scope = $validated['scope'] ?? 'global';

        // Check if user has the permission
        $hasPermission = $user->canAccess($resource, $action, $scope);

        // Additional context for the permission check
        $context = [
            'user_id' => $user->id,
            'user_roles' => $user->getRoleNames(),
            'permission_checked' => "{$resource}.{$action}" . ($scope !== 'global' ? ".{$scope}" : ''),
            'resource_id' => $validated['resource_id'] ?? null,
            'timestamp' => now()->toISOString()
        ];

        return response()->json([
            'success' => true,
            'message' => 'Permission checked successfully',
            'data' => [
                'allowed' => $hasPermission,
                'context' => $context
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users/me/permissions",
     *     summary="Permissions de l'utilisateur connecté",
     *     tags={"Permissions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="grouped",
     *         in="query",
     *         description="Grouper les permissions par module",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permissions utilisateur récupérées avec succès"
     *     )
     * )
     */
    public function myPermissions(Request $request): JsonResponse
    {
        $user = auth()->user();
        $permissions = $user->getAllPermissions();

        // Group by module if requested
        if ($request->boolean('grouped')) {
            $permissionsByModule = $user->getPermissionsByModule();

            return response()->json([
                'success' => true,
                'message' => 'User permissions retrieved successfully',
                'data' => [
                    'user' => $user->only(['id', 'name', 'email']),
                    'roles' => $user->roles()->get(['name', 'display_name']),
                    'permissions' => $permissionsByModule,
                    'total_permissions' => $permissions->count()
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'User permissions retrieved successfully',
            'data' => [
                'user' => $user->only(['id', 'name', 'email']),
                'roles' => $user->roles()->get(['name', 'display_name']),
                'permissions' => $permissions,
                'total_permissions' => $permissions->count()
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/permissions/{id}",
     *     summary="Détails d'une permission spécifique",
     *     tags={"Permissions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission récupérée avec succès"
     *     )
     * )
     */
    public function show(Permission $permission): JsonResponse
    {
        $permission->load('roles');

        return response()->json([
            'success' => true,
            'message' => 'Permission retrieved successfully',
            'data' => [
                'permission' => $permission,
                'roles_count' => $permission->roles()->count(),
                'roles' => $permission->roles()->get(['id', 'name', 'display_name'])
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/permissions/user/{user_id}",
     *     summary="Permissions d'un utilisateur spécifique",
     *     tags={"Permissions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="grouped",
     *         in="query",
     *         description="Grouper les permissions par module",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permissions utilisateur récupérées avec succès"
     *     )
     * )
     */
    public function userPermissions(Request $request, User $user): JsonResponse
    {
        $permissions = $user->getAllPermissions();

        // Group by module if requested
        if ($request->boolean('grouped')) {
            $permissionsByModule = $user->getPermissionsByModule();

            return response()->json([
                'success' => true,
                'message' => 'User permissions retrieved successfully',
                'data' => [
                    'user' => $user->only(['id', 'name', 'email']),
                    'roles' => $user->roles()->get(['name', 'display_name']),
                    'permissions' => $permissionsByModule,
                    'total_permissions' => $permissions->count()
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'User permissions retrieved successfully',
            'data' => [
                'user' => $user->only(['id', 'name', 'email']),
                'roles' => $user->roles()->get(['name', 'display_name']),
                'permissions' => $permissions,
                'total_permissions' => $permissions->count()
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/permissions/bulk-check",
     *     summary="Vérification en lot de permissions",
     *     tags={"Permissions"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="permissions", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="action", type="string"),
     *                     @OA\Property(property="resource", type="string"),
     *                     @OA\Property(property="scope", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permissions vérifiées avec succès"
     *     )
     * )
     */
    public function bulkCheckPermissions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*.action' => 'required|string',
            'permissions.*.resource' => 'required|string',
            'permissions.*.scope' => 'string|in:global,own,team'
        ]);

        $user = auth()->user();
        $results = [];

        foreach ($validated['permissions'] as $index => $permissionCheck) {
            $action = $permissionCheck['action'];
            $resource = $permissionCheck['resource'];
            $scope = $permissionCheck['scope'] ?? 'global';

            $hasPermission = $user->canAccess($resource, $action, $scope);
            $permissionName = "{$resource}.{$action}" . ($scope !== 'global' ? ".{$scope}" : '');

            $results[$index] = [
                'permission' => $permissionName,
                'action' => $action,
                'resource' => $resource,
                'scope' => $scope,
                'allowed' => $hasPermission
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk permission check completed',
            'data' => [
                'results' => $results,
                'total_checked' => count($results),
                'allowed_count' => count(array_filter($results, fn($r) => $r['allowed'])),
                'denied_count' => count(array_filter($results, fn($r) => !$r['allowed']))
            ]
        ]);
    }
}
