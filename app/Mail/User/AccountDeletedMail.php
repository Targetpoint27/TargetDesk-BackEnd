<?php

namespace App\Mail\User;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AccountDeletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $deletedBy;
    public $reason;

    public function __construct(User $user, string $deletedBy = null, string $reason = null)
    {
        $this->user = $user;
        $this->deletedBy = $deletedBy;
        $this->reason = $reason;
    }

    public function build()
    {
        return $this->subject('TargetDesk CRM - Votre compte a été supprimé')
                    ->markdown('emails.user.account-deleted')
                    ->with([
                        'user' => $this->user,
                        'deletedBy' => $this->deletedBy,
                        'reason' => $this->reason,
                    ]);
    }
}