<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTaskNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $task;
    public $notificationType;
    public $data;
    public $tries = 3;
    public $timeout = 60;

    public function __construct(User $user, Task $task, string $notificationType, array $data = [])
    {
        $this->user = $user;
        $this->task = $task;
        $this->notificationType = $notificationType;
        $this->data = $data;
    }

    public function handle()
    {
        try {
            Log::info('Notification de tâche envoyée', [
                'user_id' => $this->user->id,
                'task_id' => $this->task->id,
                'type' => $this->notificationType,
                'data' => $this->data
            ]);

            // For now just log the notification
            // In a full implementation, this would send emails or push notifications
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de notification de tâche', [
                'user_id' => $this->user->id,
                'task_id' => $this->task->id,
                'type' => $this->notificationType,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Échec définitif de l\'envoi de notification de tâche', [
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
            'type' => $this->notificationType,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}
