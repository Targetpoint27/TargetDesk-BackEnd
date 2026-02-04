<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserNotificationPreference;

class UserNotificationPreferencesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Initialiser les préférences pour tous les utilisateurs existants
        $users = User::whereDoesntHave('notificationPreferences')->get();

        foreach ($users as $user) {
            UserNotificationPreference::create([
                'user_id' => $user->id,
                'type' => 'appointment_reminder',
                'timing' => [60], // 1 heure par défaut
                'email_enabled' => true,
                'is_active' => true
            ]);
        }

        echo "Préférences de notification créées pour " . $users->count() . " utilisateurs\n";
    }
}
