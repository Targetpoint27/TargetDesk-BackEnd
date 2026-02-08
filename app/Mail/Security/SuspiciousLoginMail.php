<?php

namespace App\Mail\Security;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SuspiciousLoginMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $loginAttempt;

    public function __construct(User $user, array $loginAttempt)
    {
        $this->user = $user;
        $this->loginAttempt = $loginAttempt;
    }

    public function build()
    {
        return $this->subject('TargetDesk CRM - Connexion suspecte détectée')
                    ->markdown('emails.security.suspicious-login')
                    ->with([
                        'user' => $this->user,
                        'loginAttempt' => $this->loginAttempt,
                    ]);
    }
}