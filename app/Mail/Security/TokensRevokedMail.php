<?php

namespace App\Mail\Security;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class TokensRevokedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $sessionCount;
    public $reason;
    public $triggeredBy;

    public function __construct(User $user, int $sessionCount = null, string $reason = null, string $triggeredBy = null)
    {
        $this->user = $user;
        $this->sessionCount = $sessionCount;
        $this->reason = $reason;
        $this->triggeredBy = $triggeredBy;
    }

    public function build()
    {
        return $this->subject('TargetDesk CRM - Sessions fermées pour sécurité')
                    ->markdown('emails.security.tokens-revoked')
                    ->with([
                        'user' => $this->user,
                        'sessionCount' => $this->sessionCount,
                        'reason' => $this->reason,
                        'triggeredBy' => $this->triggeredBy,
                    ]);
    }
}