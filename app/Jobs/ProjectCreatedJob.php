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

class ProjectCreatedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $project;
    protected $createdBy;

    public function __construct(Project $project, User $createdBy)
    {
        $this->project = $project;
        $this->createdBy = $createdBy;
    }

    public function handle()
    {
        $recipients = collect();

        $recipients->push($this->project->projectManager);

        if ($this->project->client_type === 'interne' && $this->project->client) {
            $recipients->push($this->project->client);
        }

        $adminUsers = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['super_admin', 'admin']);
        })->get();

        $recipients = $recipients->merge($adminUsers)->unique('id');

        foreach ($recipients as $recipient) {
            if ($recipient->email && $recipient->id !== $this->createdBy->id) {
                $this->sendNotificationEmail($recipient);
            }
        }
    }

    protected function sendNotificationEmail(User $recipient)
    {
        $subject = "Nouveau projet créé - {$this->project->name}";

        $emailData = [
            'project' => $this->project,
            'createdBy' => $this->createdBy,
            'recipient' => $recipient,
        ];

        Mail::send('emails.project-created', $emailData, function ($message) use ($recipient, $subject) {
            $message->to($recipient->email)
                    ->subject($subject);
        });
    }
}
