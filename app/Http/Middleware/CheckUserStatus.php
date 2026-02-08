<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Vérifier si l'utilisateur est authentifié
        if (Auth::check()) {
            $user = Auth::user();

            // Vérifier si l'utilisateur est inactif
            if ($user->status !== 'active') {
                // Révoquer tous les tokens de l'utilisateur
                $user->tokens()->delete();

                // Déconnecter l'utilisateur
                Auth::logout();

                return response()->json([
                    'success' => false,
                    'message' => 'Votre compte a été désactivé. Veuillez contacter l\'administrateur.',
                    'error_code' => 'ACCOUNT_DEACTIVATED'
                ], 403);
            }
        }

        return $next($request);
    }
}
