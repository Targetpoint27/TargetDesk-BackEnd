<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/users",
     *     summary="Lister tous les utilisateurs",
     *     tags={"Admin - Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Liste des utilisateurs")
     * )
     */
    public function index()
    {
        try {
            $users = User::with(['primaryDepartment', 'roles'])
                         ->withCount(['assignedCalls', 'createdCalls'])
                         ->orderBy('name')
                         ->get();

            Log::info('Liste des utilisateurs consultée', [
                'consulted_by' => Auth::id(),
                'total' => $users->count(),
            ]);

            return $this->successResponse(
                $users,
                'Liste des utilisateurs récupérée',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des utilisateurs', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la récupération des utilisateurs',
                500
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/users",
     *     summary="Créer un utilisateur",
     *     tags={"Admin - Users"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password"},
     *             @OA\Property(property="name", type="string", example="Marie Dupont"),
     *             @OA\Property(property="first_name", type="string", example="Marie"),
     *             @OA\Property(property="last_name", type="string", example="Dupont"),
     *             @OA\Property(property="email", type="string", example="marie.dupont@targetpoint.fr"),
     *             @OA\Property(property="password", type="string", example="password123"),
     *             @OA\Property(property="phone", type="string", example="0612345678"),
     *             @OA\Property(property="department_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Utilisateur créé")
     * )
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'phone' => 'nullable|string|max:20',
                'department_id' => 'nullable|exists:departments,id',
            ], [
                'name.required' => 'Le nom est obligatoire',
                'email.required' => 'L\'email est obligatoire',
                'email.unique' => 'Cet email existe déjà',
                'password.required' => 'Le mot de passe est obligatoire',
                'password.min' => 'Le mot de passe doit contenir au moins 6 caractères',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(
                    $validator->errors()->first(),
                    422
                );
            }

            $user = User::create([
                'name' => $request->name,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'department_id' => $request->department_id,
                'status' => 'active',
            ]);

            $user->load('primaryDepartment');

            Log::info('Utilisateur créé', [
                'user_id' => $user->id,
                'name' => $user->name,
                'created_by' => Auth::id(),
            ]);

            return $this->successResponse(
                $user->makeHidden(['password']),
                'Utilisateur créé avec succès',
                201
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de l\'utilisateur', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la création de l\'utilisateur',
                500
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/users/{id}",
     *     summary="Voir détails d'un utilisateur",
     *     tags={"Admin - Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Détails de l'utilisateur")
     * )
     */
    public function show($id)
    {
        try {
            $user = User::with(['primaryDepartment', 'roles'])
                        ->withCount(['assignedCalls', 'createdCalls', 'closedCalls'])
                        ->find($id);

            if (!$user) {
                return $this->errorResponse('Utilisateur non trouvé', 404);
            }

            Log::info('Détails utilisateur consultés', [
                'user_id' => $id,
                'consulted_by' => Auth::id(),
            ]);

            return $this->successResponse(
                $user->makeHidden(['password']),
                'Utilisateur trouvé',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de l\'utilisateur', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la récupération de l\'utilisateur',
                500
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/users/{id}",
     *     summary="Modifier un utilisateur",
     *     tags={"Admin - Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="department_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Utilisateur modifié")
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return $this->errorResponse('Utilisateur non trouvé', 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'phone' => 'nullable|string|max:20',
                'department_id' => 'nullable|exists:departments,id',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(
                    $validator->errors()->first(),
                    422
                );
            }

            $user->update($request->only([
                'name',
                'first_name',
                'last_name',
                'email',
                'phone',
                'department_id'
            ]));

            $user->load('primaryDepartment');

            Log::info('Utilisateur modifié', [
                'user_id' => $id,
                'modified_by' => Auth::id(),
            ]);

            return $this->successResponse(
                $user->makeHidden(['password']),
                'Utilisateur modifié avec succès',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la modification de l\'utilisateur', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la modification de l\'utilisateur',
                500
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/users/{id}/assign-role",
     *     summary="Assigner un rôle à un utilisateur",
     *     tags={"Admin - Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"role"},
     *             @OA\Property(property="role", type="string", example="agent", description="agent, supervisor, manager, admin")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Rôle assigné")
     * )
     */
    public function assignRole(Request $request, $id)
    {
        try {
            $user = User::with('roles')->find($id);

            if (!$user) {
                return $this->errorResponse('Utilisateur non trouvé', 404);
            }

            $validator = Validator::make($request->all(), [
                'role' => 'required|string|in:agent,supervisor,manager,admin',
            ], [
                'role.required' => 'Le rôle est obligatoire',
                'role.in' => 'Rôle invalide. Valeurs acceptées: agent, supervisor, manager, admin',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(
                    $validator->errors()->first(),
                    422
                );
            }

            // Sync roles (replace all existing roles with the new one)
            $user->syncRoles([$request->role]);

            $user->load('roles');

            Log::info('Rôle assigné à l\'utilisateur', [
                'user_id' => $id,
                'role' => $request->role,
                'assigned_by' => Auth::id(),
            ]);

            return $this->successResponse(
                $user->makeHidden(['password']),
                'Rôle assigné avec succès',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'assignation du rôle', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Erreur lors de l\'assignation du rôle',
                500
            );
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/users/{id}/toggle-status",
     *     summary="Activer/Désactiver un utilisateur",
     *     tags={"Admin - Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Statut modifié")
     * )
     */
    public function toggleStatus($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return $this->errorResponse('Utilisateur non trouvé', 404);
            }

            $user->status = $user->status === 'active' ? 'inactive' : 'active';
            $user->save();

            $status = $user->status === 'active' ? 'activé' : 'désactivé';

            Log::info('Statut utilisateur modifié', [
                'user_id' => $id,
                'new_status' => $user->status,
                'changed_by' => Auth::id(),
            ]);

            return $this->successResponse(
                $user->makeHidden(['password']),
                "Utilisateur {$status} avec succès",
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors du changement de statut', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Erreur lors du changement de statut',
                500
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/users/{id}/reset-password",
     *     summary="Réinitialiser le mot de passe",
     *     tags={"Admin - Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"new_password"},
     *             @OA\Property(property="new_password", type="string", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Mot de passe réinitialisé")
     * )
     */
    public function resetPassword(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return $this->errorResponse('Utilisateur non trouvé', 404);
            }

            $validator = Validator::make($request->all(), [
                'new_password' => 'required|string|min:6',
            ], [
                'new_password.required' => 'Le nouveau mot de passe est obligatoire',
                'new_password.min' => 'Le mot de passe doit contenir au moins 6 caractères',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(
                    $validator->errors()->first(),
                    422
                );
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            Log::info('Mot de passe réinitialisé', [
                'user_id' => $id,
                'reset_by' => Auth::id(),
            ]);

            return $this->successResponse(
                null,
                'Mot de passe réinitialisé avec succès',
                200
            );

        } catch (\Exception $e) {
            Log::error('Erreur lors de la réinitialisation du mot de passe', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Erreur lors de la réinitialisation du mot de passe',
                500
            );
        }
    }
}