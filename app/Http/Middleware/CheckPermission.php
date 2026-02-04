<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $permission
     * @param  string|null  $scope
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|JsonResponse
     */
    public function handle(Request $request, Closure $next, string $permission, string $scope = 'global')
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $user = auth()->user();

        // Parse permission format: module.action or module.action.scope
        $permissionParts = explode('.', $permission);

        if (count($permissionParts) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid permission format'
            ], 500);
        }

        $module = $permissionParts[0];
        $action = $permissionParts[1];

        // Use scope from permission if provided, otherwise use parameter
        if (count($permissionParts) >= 3) {
            $scope = $permissionParts[2];
        }

        // Check if user has the required permission
        $hasPermission = $user->canAccess($module, $action, $scope);

        if (!$hasPermission) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions',
                'required_permission' => "{$module}.{$action}" . ($scope !== 'global' ? ".{$scope}" : ''),
                'user_roles' => $user->getRoleNames()
            ], 403);
        }

        return $next($request);
    }
}
