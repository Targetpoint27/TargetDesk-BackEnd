<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

// User notifications
use App\Mail\User\AccountCreatedMail;
use App\Mail\User\AccountDeactivatedMail;
use App\Mail\User\AccountReactivatedMail;
use App\Mail\User\RoleChangedMail;
use App\Mail\User\PasswordResetByAdminMail;
use App\Mail\User\AccountDeletedMail;

// Security notifications
use App\Mail\Security\SuspiciousLoginMail;
use App\Mail\Security\PasswordForcedChangeMail;
use App\Mail\Security\TokensRevokedMail;

class SendUserNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $notificationType;
    public $data;
    public $tries = 3;
    public $timeout = 60;

    public function __construct(User $user, string $notificationType, array $data = [])
    {
        $this->user = $user;
        $this->notificationType = $notificationType;
        $this->data = $data;
    }

    public function handle()
    {
        try {
            $mailable = $this->createMailable();

            if ($mailable) {
                Mail::to($this->user->email)->send($mailable);

                Log::info('Notification envoyée avec succès', [
                    'user_id' => $this->user->id,
                    'email' => $this->user->email,
                    'type' => $this->notificationType
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de notification', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'type' => $this->notificationType,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    private function createMailable()
    {
        switch ($this->notificationType) {
            case 'account_created':
                // Ensure roles are loaded for the email template
                $this->user->load('roles');
                return new AccountCreatedMail(
                    $this->user,
                    $this->data['password']
                );

            case 'account_deactivated':
                return new AccountDeactivatedMail(
                    $this->user,
                    $this->data['reason'] ?? null
                );

            case 'account_reactivated':
                return new AccountReactivatedMail($this->user);

            case 'role_changed':
                return new RoleChangedMail(
                    $this->user,
                    $this->data['old_role'] ?? null,
                    $this->data['new_role'],
                    $this->data['modified_by'] ?? null,
                    $this->data['permissions'] ?? null
                );

            case 'password_reset_by_admin':
                return new PasswordResetByAdminMail(
                    $this->user,
                    $this->data['temporary_password'],
                    $this->data['reset_by'] ?? null
                );

            case 'account_deleted':
                return new AccountDeletedMail(
                    $this->user,
                    $this->data['deleted_by'] ?? null,
                    $this->data['reason'] ?? null
                );

            case 'suspicious_login':
                return new SuspiciousLoginMail(
                    $this->user,
                    $this->data['login_attempt']
                );

            case 'password_forced_change':
                return new PasswordForcedChangeMail(
                    $this->user,
                    $this->data['reason'] ?? null,
                    $this->data['deadline'] ?? null,
                    $this->data['reset_token'] ?? null
                );

            case 'tokens_revoked':
                return new TokensRevokedMail(
                    $this->user,
                    $this->data['session_count'] ?? null,
                    $this->data['reason'] ?? null,
                    $this->data['triggered_by'] ?? null
                );

            default:
                Log::warning('Type de notification inconnu', [
                    'type' => $this->notificationType,
                    'user_id' => $this->user->id
                ]);
                return null;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Échec définitif de l\'envoi de notification', [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'type' => $this->notificationType,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}