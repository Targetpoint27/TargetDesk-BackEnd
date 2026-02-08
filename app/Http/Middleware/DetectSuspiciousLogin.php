<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendUserNotificationJob;

class DetectSuspiciousLogin
{
    public function handle(Request $request, Closure $next)
    {
        // Ne s'exécute que lors des connexions réussies
        if (Auth::check() && $this->isLoginRequest($request)) {
            $this->analyzeSuspiciousActivity($request, Auth::user());
        }

        return $next($request);
    }

    private function isLoginRequest(Request $request): bool
    {
        return $request->is('api/*/auth/login') && $request->isMethod('post');
    }

    private function analyzeSuspiciousActivity(Request $request, $user)
    {
        $currentIp = $request->ip();
        $userAgent = $request->userAgent();
        $cacheKey = "user_login_history_{$user->id}";

        // Récupère l'historique des connexions récentes
        $loginHistory = Cache::get($cacheKey, []);

        $isSuspicious = false;
        $suspiciousReasons = [];

        // Analyse 1: Nouvelle adresse IP
        $recentIps = collect($loginHistory)->pluck('ip')->unique();
        if ($recentIps->count() > 0 && !$recentIps->contains($currentIp)) {
            $isSuspicious = true;
            $suspiciousReasons[] = 'Nouvelle adresse IP détectée';
        }

        // Analyse 2: Nouveau navigateur/appareil
        $recentUserAgents = collect($loginHistory)->pluck('user_agent')->unique();
        if ($recentUserAgents->count() > 0 && !$recentUserAgents->contains($userAgent)) {
            $isSuspicious = true;
            $suspiciousReasons[] = 'Nouveau navigateur/appareil détecté';
        }

        // Analyse 3: Connexions trop fréquentes (possible brute force)
        $recentLogins = collect($loginHistory)->where('timestamp', '>', now()->subHours(1));
        if ($recentLogins->count() > 10) {
            $isSuspicious = true;
            $suspiciousReasons[] = 'Trop de tentatives de connexion récentes';
        }

        // Analyse 4: Heure inhabituelle
        $currentHour = now()->hour;
        $usualHours = collect($loginHistory)->pluck('hour')->mode();
        if (!empty($usualHours) && abs($currentHour - $usualHours[0]) > 6) {
            $isSuspicious = true;
            $suspiciousReasons[] = 'Connexion à une heure inhabituelle';
        }

        // Enregistre la connexion actuelle
        $loginHistory[] = [
            'ip' => $currentIp,
            'user_agent' => $userAgent,
            'timestamp' => now()->toISOString(),
            'hour' => $currentHour,
            'location' => $this->getLocationFromIp($currentIp)
        ];

        // Garde seulement les 20 dernières connexions
        $loginHistory = array_slice($loginHistory, -20);

        // Stocke l'historique pour 30 jours
        Cache::put($cacheKey, $loginHistory, now()->addDays(30));

        // Envoie une notification si connexion suspecte
        if ($isSuspicious) {
            $this->sendSuspiciousLoginNotification($user, [
                'ip' => $currentIp,
                'user_agent' => $userAgent,
                'timestamp' => now()->toISOString(),
                'location' => $this->getLocationFromIp($currentIp),
                'reasons' => $suspiciousReasons,
                'status' => 'Autorisée', // La connexion a réussi mais est signalée
                'blocked' => false
            ]);

            Log::warning('Connexion suspecte détectée', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $currentIp,
                'user_agent' => $userAgent,
                'reasons' => $suspiciousReasons
            ]);
        }

        // Met à jour la dernière connexion de l'utilisateur
        $user->update(['last_login' => now()]);
    }

    private function getLocationFromIp(string $ip): string
    {
        // Simplifié - dans un vrai projet, utilisez un service de géolocalisation
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'Localhost';
        }

        // Vous pouvez intégrer un service comme IPInfo, GeoIP, etc.
        return 'Localisation non disponible';
    }

    private function sendSuspiciousLoginNotification($user, array $loginAttempt)
    {
        SendUserNotificationJob::dispatch($user, 'suspicious_login', [
            'login_attempt' => $loginAttempt
        ]);
    }
}