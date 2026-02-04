<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="User Roles",
 *     description="API pour la gestion des rôles utilisateur"
 * )
 */
class UserRoleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/users/{id}/roles",
     *     summary="Rôles d'un utilisateur",
     *     tags={"User Roles"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="include_inactive",
     *         in="query",
     *         description="Inclure les rôles inactifs",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rôles utilisateur récupérés avec succès"
     *     )
     * )
     */
    public function index(Request $request, User $user): JsonResponse
    {
        $query = $request->boolean('include_inactive') ? $user->allRoles() : $user->roles();

        $roles = $query->withPivot('assigned_by', 'assigned_at', 'effective_from', 'effective_until', 'is_active')
                      ->with('creator:id,name,email')
                      ->orderBy('pivot_assigned_at', 'desc')
                      ->get();

        // Get assignment history with assigner info
        $rolesWithHistory = $roles->map(function ($role) {
            $assignerInfo = null;
            if ($role->pivot->assigned_by) {
                $assigner = User::find($role->pivot->assigned_by);
                $assignerInfo = $assigner ? $assigner->only(['id', 'name', 'email']) : null;
            }

            return [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
                'description' => $role->description,
                'is_predefined' => $role->is_predefined,
                'is_active' => $role->is_active,
                'assignment' => [
                    'assigned_by' => $assignerInfo,
                    'assigned_at' => $role->pivot->assigned_at,
                    'effective_from' => $role->pivot->effective_from,
                    'effective_until' => $role->pivot->effective_until,
                    'is_active' => $role->pivot->is_active,
                    'is_currently_effective' => $this->isCurrentlyEffective($role->pivot)
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'User roles retrieved successfully',
            'data' => [
                'user' => $user->only(['id', 'name', 'email']),
                'roles' => $rolesWithHistory,
                'active_roles_count' => $roles->where('pivot.is_active', true)->count(),
                'total_roles_count' => $roles->count()
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users/{id}/roles",
     *     summary="Assigner un rôle à un utilisateur",
     *     tags={"User Roles"},
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
     *             @OA\Property(property="role_id", type="integer", example=1),
     *             @OA\Property(property="effective_from", type="string", format="date-time", nullable=true),
     *             @OA\Property(property="effective_until", type="string", format="date-time", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Rôle assigné avec succès"
     *     )
     * )
     */
    public function store(Request $request, User $user): JsonResponse
    {
        try {
            $validated = $request->validate([
                'role_id' => 'required|exists:roles,id',
                'effective_from' => 'nullable|date',
                'effective_until' => 'nullable|date|after:effective_from'
            ]);

            $role = Role::findOrFail($validated['role_id']);

            // Check if role is already assigned
            if ($user->roles()->where('role_id', $role->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already has this role assigned'
                ], 409);
            }

            DB::beginTransaction();

            // Assign the role
            $user->assignRole(
                $role,
                auth()->id(),
                isset($validated['effective_from']) && $validated['effective_from'] ? Carbon::parse($validated['effective_from']) : null,
                isset($validated['effective_until']) && $validated['effective_until'] ? Carbon::parse($validated['effective_until']) : null
            );

            DB::commit();

            // Reload user with new role
            $user->load(['roles' => function ($query) use ($role) {
                $query->where('role_id', $role->id);
            }]);

            return response()->json([
                'success' => true,
                'message' => 'Role assigned successfully',
                'data' => [
                    'user' => $user->only(['id', 'name', 'email']),
                    'assigned_role' => $role,
                    'assignment_details' => [
                        'assigned_by' => auth()->user()->only(['id', 'name', 'email']),
                        'assigned_at' => now(),
                        'effective_from' => $validated['effective_from'] ?? null,
                        'effective_until' => $validated['effective_until'] ?? null
                    ]
                ]
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
            \Log::error('Role assignment error', [
                'user_id' => $user->id,
                'role_id' => $validated['role_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while assigning the role',
                'debug' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/users/{id}/roles/{role_id}",
     *     summary="Retirer un rôle d'un utilisateur",
     *     tags={"User Roles"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="role_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rôle retiré avec succès"
     *     )
     * )
     */
    public function destroy(User $user, Role $role): JsonResponse
    {
        if (!$user->hasRole($role)) {
            return response()->json([
                'success' => false,
                'message' => 'User does not have this role'
            ], 404);
        }

        try {
            DB::beginTransaction();

            $user->removeRole($role);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role removed successfully',
                'data' => [
                    'user' => $user->only(['id', 'name', 'email']),
                    'removed_role' => $role->only(['id', 'name', 'display_name']),
                    'removed_by' => auth()->user()->only(['id', 'name', 'email']),
                    'removed_at' => now()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing the role'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users/{id}/roles/bulk-assign",
     *     summary="Assigner plusieurs rôles à un utilisateur",
     *     tags={"User Roles"},
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
     *             @OA\Property(property="role_ids", type="array", @OA\Items(type="integer")),
     *             @OA\Property(property="effective_from", type="string", format="date-time", nullable=true),
     *             @OA\Property(property="effective_until", type="string", format="date-time", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Rôles assignés avec succès"
     *     )
     * )
     */
    public function bulkAssign(Request $request, User $user): JsonResponse
    {
        try {
            $validated = $request->validate([
                'role_ids' => 'required|array',
                'role_ids.*' => 'exists:roles,id',
                'effective_from' => 'nullable|date',
                'effective_until' => 'nullable|date|after:effective_from'
            ]);

            $roles = Role::whereIn('id', $validated['role_ids'])->get();

            // Check for already assigned roles
            $existingRoles = $user->roles()->whereIn('role_id', $validated['role_ids'])->pluck('role_id')->toArray();
            $newRoleIds = array_diff($validated['role_ids'], $existingRoles);

            if (empty($newRoleIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'All specified roles are already assigned to this user'
                ], 409);
            }

            DB::beginTransaction();

            $assignedRoles = [];
            foreach ($newRoleIds as $roleId) {
                $role = $roles->where('id', $roleId)->first();

                $user->assignRole(
                    $role,
                    auth()->id(),
                    $validated['effective_from'] ? Carbon::parse($validated['effective_from']) : null,
                    $validated['effective_until'] ? Carbon::parse($validated['effective_until']) : null
                );

                $assignedRoles[] = $role;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Roles assigned successfully',
                'data' => [
                    'user' => $user->only(['id', 'name', 'email']),
                    'assigned_roles' => collect($assignedRoles)->map(fn($role) => $role->only(['id', 'name', 'display_name'])),
                    'already_assigned_roles' => $roles->whereIn('id', $existingRoles)->map(fn($role) => $role->only(['id', 'name', 'display_name'])),
                    'total_assigned' => count($assignedRoles),
                    'assignment_details' => [
                        'assigned_by' => auth()->user()->only(['id', 'name', 'email']),
                        'assigned_at' => now(),
                        'effective_from' => $validated['effective_from'] ?? null,
                        'effective_until' => $validated['effective_until'] ?? null
                    ]
                ]
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
                'message' => 'An error occurred while assigning roles'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users/{id}/roles/bulk-remove",
     *     summary="Retirer plusieurs rôles d'un utilisateur",
     *     tags={"User Roles"},
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
     *             @OA\Property(property="role_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rôles retirés avec succès"
     *     )
     * )
     */
    public function bulkRemove(Request $request, User $user): JsonResponse
    {
        try {
            $validated = $request->validate([
                'role_ids' => 'required|array',
                'role_ids.*' => 'exists:roles,id'
            ]);

            $roles = Role::whereIn('id', $validated['role_ids'])->get();

            // Check for assigned roles
            $assignedRoleIds = $user->roles()->whereIn('role_id', $validated['role_ids'])->pluck('role_id')->toArray();

            if (empty($assignedRoleIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have any of the specified roles'
                ], 404);
            }

            DB::beginTransaction();

            $removedRoles = [];
            foreach ($assignedRoleIds as $roleId) {
                $role = $roles->where('id', $roleId)->first();
                $user->removeRole($role);
                $removedRoles[] = $role;
            }

            DB::commit();

            $notAssignedRoleIds = array_diff($validated['role_ids'], $assignedRoleIds);

            return response()->json([
                'success' => true,
                'message' => 'Roles removed successfully',
                'data' => [
                    'user' => $user->only(['id', 'name', 'email']),
                    'removed_roles' => collect($removedRoles)->map(fn($role) => $role->only(['id', 'name', 'display_name'])),
                    'not_assigned_roles' => $roles->whereIn('id', $notAssignedRoleIds)->map(fn($role) => $role->only(['id', 'name', 'display_name'])),
                    'total_removed' => count($removedRoles),
                    'removed_by' => auth()->user()->only(['id', 'name', 'email']),
                    'removed_at' => now()
                ]
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
                'message' => 'An error occurred while removing roles'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/users/{id}/roles/{role_id}",
     *     summary="Modifier l'assignation d'un rôle",
     *     tags={"User Roles"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="role_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="effective_from", type="string", format="date-time", nullable=true),
     *             @OA\Property(property="effective_until", type="string", format="date-time", nullable=true),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assignation de rôle modifiée avec succès"
     *     )
     * )
     */
    public function update(Request $request, User $user, Role $role): JsonResponse
    {
        if (!$user->hasRole($role)) {
            return response()->json([
                'success' => false,
                'message' => 'User does not have this role'
            ], 404);
        }

        try {
            $validated = $request->validate([
                'effective_from' => 'nullable|date',
                'effective_until' => 'nullable|date|after:effective_from',
                'is_active' => 'boolean'
            ]);

            DB::beginTransaction();

            // Update pivot table
            $updateData = [];
            if (isset($validated['effective_from'])) {
                $updateData['effective_from'] = $validated['effective_from'] ? Carbon::parse($validated['effective_from']) : null;
            }
            if (isset($validated['effective_until'])) {
                $updateData['effective_until'] = $validated['effective_until'] ? Carbon::parse($validated['effective_until']) : null;
            }
            if (isset($validated['is_active'])) {
                $updateData['is_active'] = $validated['is_active'];
            }

            $user->roles()->updateExistingPivot($role->id, $updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role assignment updated successfully',
                'data' => [
                    'user' => $user->only(['id', 'name', 'email']),
                    'role' => $role->only(['id', 'name', 'display_name']),
                    'updated_by' => auth()->user()->only(['id', 'name', 'email']),
                    'updated_at' => now(),
                    'changes' => $updateData
                ]
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
                'message' => 'An error occurred while updating the role assignment'
            ], 500);
        }
    }

    /**
     * Check if role assignment is currently effective
     */
    private function isCurrentlyEffective($pivot): bool
    {
        if (!$pivot->is_active) {
            return false;
        }

        $now = now();

        // Check effective_from date
        if ($pivot->effective_from && Carbon::parse($pivot->effective_from)->isFuture()) {
            return false;
        }

        // Check effective_until date
        if ($pivot->effective_until && Carbon::parse($pivot->effective_until)->isPast()) {
            return false;
        }

        return true;
    }
}
