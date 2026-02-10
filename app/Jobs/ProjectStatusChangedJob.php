<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class ProjectStatusChangedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $project;
    protected $oldStatus;
    protected $newStatus;
    protected $changedBy;

    public function __construct(Project $project, string $oldStatus, string $newStatus, User $changedBy)
    {
        $this->project = $project;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->changedBy = $changedBy;
    }

    public function handle()
    {
        $recipients = collect();

        $recipients->push($this->project->projectManager);

        if ($this->project->client_type === 'interne' && $this->project->client) {
            $recipients->push($this->project->client);
        }

        foreach ($this->project->teamMembers as $teamMember) {
            $recipients->push($teamMember->user);
        }

        $adminUsers = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['super_admin', 'admin']);
        })->get();

        $recipients = $recipients->merge($adminUsers)->unique('id');

        foreach ($recipients as $recipient) {
            if ($recipient->email) {
                $this->sendNotificationEmail($recipient);
            }
        }
    }

    protected function sendNotificationEmail(User $recipient)
    {
        $statusLabels = [
            'en_cours' => 'En cours',
            'en_attente' => 'En attente',
            'en_danger' => 'En danger',
            'termine' => 'Terminé',
            'annule' => 'Annulé'
        ];

        $subject = "Changement de statut - Projet {$this->project->name}";

        $emailData = [
            'project' => $this->project,
            'oldStatus' => $statusLabels[$this->oldStatus] ?? $this->oldStatus,
            'newStatus' => $statusLabels[$this->newStatus] ?? $this->newStatus,
            'changedBy' => $this->changedBy,
            'recipient' => $recipient,
        ];

        Mail::send('emails.project-status-changed', $emailData, function ($message) use ($recipient, $subject) {
            $message->to($recipient->email)
                    ->subject($subject);
        });
    }
}
