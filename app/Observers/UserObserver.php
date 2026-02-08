<?php

namespace App\Observers;

use App\Models\User;
use App\Jobs\SendUserNotificationJob;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user)
    {
        // Génère un mot de passe temporaire
        $temporaryPassword = Str::random(12);

        // Ne pas écraser le mot de passe s'il existe déjà
        // Hash et sauvegarde le mot de passe temporaire
        // $user->password = bcrypt($temporaryPassword);
        // $user->saveQuietly(); // Évite les événements récursifs

        // Envoie la notification de création de compte (avec gestion d'erreur)
        try {
            SendUserNotificationJob::dispatch($user, 'account_created', [
                'temporary_password' => $temporaryPassword
            ]);

            Log::info('Notification de création de compte programmée', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        } catch (\Exception $e) {
            Log::warning('Erreur lors de l\'envoi de la notification de création', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);

            // Relancer l'erreur pour que le controller puisse la gérer
            throw new \Exception('Expected response code 354 but got code "503", with message "' . $e->getMessage() . '"');
        }
    }

    /**
     * Handle the User "updating" event.
     */
    public function updating(User $user)
    {
        $originalStatus = $user->getOriginal('status');
        $newStatus = $user->status;

        // Détecte les changements de statut
        if ($originalStatus !== $newStatus) {
            if ($originalStatus === 'active' && $newStatus === 'inactive') {
                // Utilisateur désactivé
                $this->handleAccountDeactivation($user);
            } elseif ($originalStatus === 'inactive' && $newStatus === 'active') {
                // Utilisateur réactivé
                $this->handleAccountReactivation($user);
            }
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user)
    {
        // Envoie notification de suppression de compte
        SendUserNotificationJob::dispatch($user, 'account_deleted', [
            'deleted_by' => auth()->user()->name ?? 'Administrateur',
            'reason' => request('reason') // Optionnel: raison fournie lors de la suppression
        ]);

        Log::info('Notification de suppression de compte programmée', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);
    }

    /**
     * Gère la désactivation d'un compte
     */
    private function handleAccountDeactivation(User $user)
    {
        // Révoque tous les tokens de l'utilisateur
        $user->tokens()->delete();

        // Envoie notification de désactivation
        SendUserNotificationJob::dispatch($user, 'account_deactivated', [
            'reason' => request('reason') ?? 'Mesure administrative'
        ]);

        Log::info('Notification de désactivation de compte programmée', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);
    }

    /**
     * Gère la réactivation d'un compte
     */
    private function handleAccountReactivation(User $user)
    {
        // Envoie notification de réactivation
        SendUserNotificationJob::dispatch($user, 'account_reactivated');

        Log::info('Notification de réactivation de compte programmée', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);
    }

    /**
     * Méthode publique pour envoyer notification de changement de rôle
     * (appelée manuellement depuis le contrôleur)
     */
    public static function notifyRoleChange(User $user, $oldRole, $newRole, $modifiedBy = null)
    {
        SendUserNotificationJob::dispatch($user, 'role_changed', [
            'old_role' => $oldRole,
            'new_role' => $newRole,
            'modified_by' => $modifiedBy,
            'permissions' => $user->getAllPermissions()->pluck('name')->join(', ')
        ]);

        Log::info('Notification de changement de rôle programmée', [
            'user_id' => $user->id,
            'email' => $user->email,
            'old_role' => $oldRole,
            'new_role' => $newRole
        ]);
    }

    /**
     * Méthode publique pour envoyer notification de réinitialisation de mot de passe
     */
    public static function notifyPasswordReset(User $user, $temporaryPassword, $resetBy = null)
    {
        SendUserNotificationJob::dispatch($user, 'password_reset_by_admin', [
            'temporary_password' => $temporaryPassword,
            'reset_by' => $resetBy
        ]);

        Log::info('Notification de réinitialisation de mot de passe programmée', [
            'user_id' => $user->id,
            'email' => $user->email,
            'reset_by' => $resetBy
        ]);
    }
}