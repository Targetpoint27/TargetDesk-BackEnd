<?php

namespace App\Mail\User;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PasswordResetByAdminMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $temporaryPassword;
    public $resetBy;

    public function __construct(User $user, string $temporaryPassword, string $resetBy = null)
    {
        $this->user = $user;
        $this->temporaryPassword = $temporaryPassword;
        $this->resetBy = $resetBy;
    }

    public function build()
    {
        return $this->subject('TargetDesk CRM - Votre mot de passe a été réinitialisé')
                    ->markdown('emails.user.password-reset-by-admin')
                    ->with([
                        'user' => $this->user,
                        'temporaryPassword' => $this->temporaryPassword,
                        'resetBy' => $this->resetBy,
                    ]);
    }
}