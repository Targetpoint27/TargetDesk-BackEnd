<?php

namespace App\Mail\User;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AccountCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $password;
    public $userRole;

    public function __construct(User $user, string $password)
    {
        $this->user = $user;
        $this->password = $password;

        // Récupérer le rôle de l'utilisateur avec un nom affiché plus propre
        $role = $user->roles->first();
        if ($role) {
            switch($role->name) {
                case 'admin':
                    $this->userRole = 'Administrateur';
                    break;
                case 'manager':
                    $this->userRole = 'Chef de Projet';
                    break;
                case 'user':
                    $this->userRole = 'Utilisateur';
                    break;
                case 'client':
                    $this->userRole = 'Client';
                    break;
                default:
                    $this->userRole = ucfirst($role->name);
            }
        } else {
            $this->userRole = 'Utilisateur';
        }
    }

    public function build()
    {
        return $this->subject('Bienvenue sur TargetDesk CRM - Votre compte a été créé')
                    ->markdown('emails.user.account-created')
                    ->with([
                        'user' => $this->user,
                        'password' => $this->password,
                        'userRole' => $this->userRole,
                        'loginUrl' => 'https://targetdesk.fr/#/login'
                    ]);
    }
}