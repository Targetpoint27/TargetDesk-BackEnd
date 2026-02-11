<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\StoreUserRequest;
use App\Http\Requests\Api\UpdateUserRequest;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/v1/users",
     *     tags={"Users"},
     *     summary="Get all users",
     *     description="Retrieve all users with pagination and filtering",
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
     *         description="Items per page",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Filter by role name",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"active", "inactive"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with(['roles']);

        // Filter by role
        if ($request->has('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search functionality
        if ($request->has('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        return $this->successResponse($users, 'Utilisateurs récupérés avec succès');
    }

    /**
     * @OA\Get(
     *     path="/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Get user by ID",
     *     description="Retrieve a specific user by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['roles']);
        return $this->successResponse($user, 'Utilisateur récupéré avec succès');
    }

    /**
     * @OA\Post(
     *     path="/v1/users",
     *     tags={"Users"},
     *     summary="Create new user",
     *     description="Create a new user",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","first_name","last_name","password","role_id"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="password_confirmation", type="string"),
     *             @OA\Property(property="role_id", type="integer"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="department", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully"
     *     )
     * )
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Sauvegarder le mot de passe en clair avant hashage pour l'email
        $plainPassword = $validated['password'];
        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['name'] = $validated['first_name'] . ' ' . $validated['last_name'];

        $emailSent = true;
        $emailError = null;

        try {
            $user = User::create($validated);

            // Assign role
            if (isset($validated['role_id'])) {
                $role = Role::find($validated['role_id']);
                if ($role) {
                    $user->assignRole($role->name);
                }
            }

            $user->load(['roles']);

            // Envoyer l'email de bienvenue maintenant que tout est configuré
            try {
                \App\Jobs\SendUserNotificationJob::dispatch($user, 'account_created', [
                    'password' => $plainPassword
                ]);

                Log::info('Email de bienvenue envoyé', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
            } catch (\Exception $emailException) {
                $emailSent = false;
                $emailError = $emailException->getMessage();

                Log::warning('Erreur envoi email de bienvenue', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $emailException->getMessage()
                ]);
            }

            Log::info('Utilisateur créé', [
                'user_id' => $user->id,
                'email' => $user->email,
                'created_by' => auth()->id(),
                'created_by_name' => auth()->user()->name
            ]);

        } catch (\Exception $e) {
            // Si c'est une erreur d'email, on continue mais on note l'erreur
            if (str_contains($e->getMessage(), 'Expected response code 354') ||
                str_contains($e->getMessage(), 'RCPT commands were rejected')) {

                $emailSent = false;
                $emailError = $e->getMessage();

                // L'utilisateur devrait quand même être créé, on le récupère
                $user = User::where('email', $validated['email'])->first();

                if (!$user) {
                    throw $e; // Si l'utilisateur n'existe pas, l'erreur est ailleurs
                }

                // Assign role si l'utilisateur existe
                if (isset($validated['role_id']) && $user) {
                    $role = Role::find($validated['role_id']);
                    if ($role) {
                        $user->assignRole($role->name);
                    }
                }

                $user->load(['roles']);

                Log::warning('Utilisateur créé mais email échoué', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'email_error' => $emailError,
                    'created_by' => auth()->id()
                ]);
            } else {
                throw $e; // Autres erreurs, on les relance
            }
        }

        $message = $emailSent
            ? 'Utilisateur créé avec succès'
            : 'Utilisateur créé avec succès mais l\'envoi de l\'email de notification a échoué. L\'utilisateur peut se connecter normalement.';

        return $this->successResponse($user, $message, 201);
    }

    /**
     * @OA\Put(
     *     path="/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Update user",
     *     description="Update an existing user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="role_id", type="integer"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="department", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully"
     *     )
     * )
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        // Update name if first_name or last_name changed
        if (isset($validated['first_name']) || isset($validated['last_name'])) {
            $firstName = $validated['first_name'] ?? $user->first_name;
            $lastName = $validated['last_name'] ?? $user->last_name;
            $validated['name'] = $firstName . ' ' . $lastName;
        }

        $user->update($validated);

        // Update role if provided
        if (isset($validated['role_id'])) {
            $role = Role::find($validated['role_id']);
            if ($role) {
                $user->syncRoles([$role->name]);
            }
        }

        $user->load(['roles']);

        Log::info('Utilisateur mis à jour', [
            'user_id' => $user->id,
            'email' => $user->email,
            'updated_by' => auth()->id(),
            'updated_by_name' => auth()->user()->name
        ]);

        return $this->successResponse($user, 'Utilisateur mis à jour avec succès');
    }

    /**
     * @OA\Delete(
     *     path="/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Delete user",
     *     description="Delete a user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully"
     *     )
     * )
     */
    public function destroy(User $user): JsonResponse
    {
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return $this->errorResponse('Vous ne pouvez pas supprimer votre propre compte', 403, []);
        }

        $userEmail = $user->email;
        $user->delete();

        Log::info('Utilisateur supprimé', [
            'user_email' => $userEmail,
            'deleted_by' => auth()->id(),
            'deleted_by_name' => auth()->user()->name
        ]);

        return $this->successResponse(null, 'Utilisateur supprimé avec succès');
    }

    /**
     * @OA\Patch(
     *     path="/v1/users/{id}/toggle-status",
     *     tags={"Users"},
     *     summary="Toggle user status",
     *     description="Activate or deactivate a user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User status updated successfully"
     *     )
     * )
     */
    public function toggleStatus(User $user): JsonResponse
    {
        // Prevent self-deactivation
        if ($user->id === auth()->id()) {
            return $this->errorResponse('Vous ne pouvez pas désactiver votre propre compte', 403, []);
        }

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        Log::info('Statut utilisateur modifié', [
            'user_id' => $user->id,
            'email' => $user->email,
            'new_status' => $newStatus,
            'updated_by' => auth()->id(),
            'updated_by_name' => auth()->user()->name
        ]);

        $message = $newStatus === 'active' ? 'Utilisateur activé avec succès' : 'Utilisateur désactivé avec succès';
        return $this->successResponse($user, $message);
    }

    /**
     * @OA\Post(
     *     path="/v1/users/{id}/reset-password",
     *     tags={"Users"},
     *     summary="Reset user password",
     *     description="Reset user password",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successfully"
     *     )
     * )
     */
    public function resetPassword(User $user): JsonResponse
    {
        $newPassword = Str::random(8);
        $user->update(['password' => Hash::make($newPassword)]);

        // Envoyer notification de réinitialisation de mot de passe
        try {
            \App\Observers\UserObserver::notifyPasswordReset($user, $newPassword, auth()->user()->name);
        } catch (\Exception $e) {
            Log::warning('Erreur lors de l\'envoi de la notification de reset password', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
        }

        Log::info('Mot de passe réinitialisé', [
            'user_id' => $user->id,
            'email' => $user->email,
            'reset_by' => auth()->id(),
            'reset_by_name' => auth()->user()->name
        ]);

        return $this->successResponse([
            'new_password' => $newPassword
        ], 'Mot de passe réinitialisé avec succès');
    }

    /**
     * @OA\Get(
     *     path="/v1/users/search",
     *     tags={"Users"},
     *     summary="Search users",
     *     description="Search users by name or email",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=true,
     *         description="Search term",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results"
     *     )
     * )
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2']);

        $query = $request->q;
        $users = User::with(['roles'])
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get();

        return $this->successResponse($users, 'Résultats de recherche');
    }

    /**
     * @OA\Get(
     *     path="/v1/users/validate-email",
     *     tags={"Users"},
     *     summary="Validate email uniqueness",
     *     description="Check if email is unique",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="email")
     *     ),
     *     @OA\Parameter(
     *         name="exclude",
     *         in="query",
     *         description="User ID to exclude from check",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Validation result"
     *     )
     * )
     */
    public function validateEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'exclude' => 'sometimes|integer'
        ]);

        $query = User::where('email', $request->email);

        if ($request->has('exclude')) {
            $query->where('id', '!=', $request->exclude);
        }

        $exists = $query->exists();

        return $this->successResponse([
            'email' => $request->email,
            'is_unique' => !$exists,
            'is_available' => !$exists
        ], $exists ? 'Email déjà utilisé' : 'Email disponible');
    }

    /**
     * @OA\Get(
     *     path="/v1/users/stats",
     *     tags={"Users"},
     *     summary="Get users statistics",
     *     description="Get statistics about users",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistics retrieved successfully"
     *     )
     * )
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'inactive_users' => User::where('status', 'inactive')->count(),
            'users_by_role' => []
        ];

        // Get users count by role
        $roleStats = Role::withCount('users')->get();
        foreach ($roleStats as $role) {
            $stats['users_by_role'][$role->name] = $role->users_count;
        }

        // Recent registrations (last 30 days)
        $stats['recent_registrations'] = User::where('created_at', '>=', now()->subDays(30))->count();

        return $this->successResponse($stats, 'Statistiques récupérées avec succès');
    }
}