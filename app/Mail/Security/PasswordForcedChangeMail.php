<?php

namespace App\Mail\Security;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PasswordForcedChangeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $reason;
    public $deadline;
    public $resetToken;

    public function __construct(User $user, string $reason = null, string $deadline = null, string $resetToken = null)
    {
        $this->user = $user;
        $this->reason = $reason;
        $this->deadline = $deadline;
        $this->resetToken = $resetToken;
    }

    public function build()
    {
        return $this->subject('TargetDesk CRM - Changement de mot de passe obligatoire')
                    ->markdown('emails.security.password-forced-change')
                    ->with([
                        'user' => $this->user,
                        'reason' => $this->reason,
                        'deadline' => $this->deadline,
                        'resetToken' => $this->resetToken,
                    ]);
    }
}