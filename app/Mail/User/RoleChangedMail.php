<?php

namespace App\Mail\User;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class RoleChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $oldRole;
    public $newRole;
    public $modifiedBy;
    public $permissions;

    public function __construct(User $user, string $oldRole = null, string $newRole, string $modifiedBy = null, string $permissions = null)
    {
        $this->user = $user;
        $this->oldRole = $oldRole;
        $this->newRole = $newRole;
        $this->modifiedBy = $modifiedBy;
        $this->permissions = $permissions;
    }

    public function build()
    {
        return $this->subject('TargetDesk CRM - Vos permissions ont été modifiées')
                    ->markdown('emails.user.role-changed')
                    ->with([
                        'user' => $this->user,
                        'oldRole' => $this->oldRole,
                        'newRole' => $this->newRole,
                        'modifiedBy' => $this->modifiedBy,
                        'permissions' => $this->permissions,
                    ]);
    }
}