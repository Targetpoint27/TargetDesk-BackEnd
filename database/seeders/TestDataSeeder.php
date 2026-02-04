<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;
use App\Models\Appointment;
use App\Models\UserNotificationPreference;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un utilisateur de test (ou le récupérer s'il existe)
        $user = User::firstOrCreate(
            ['email' => 'commercial@test.com'],
            [
                'name' => 'Test Commercial',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'status' => 'active'
            ]
        );

        // Créer un client de test
        $client = Client::create([
            'name' => 'Client Test',
            'type' => 'company',
            'email' => 'client@test.com',
            'phone' => '+33 1 23 45 67 89',
            'address' => '123 Rue de la Test, 75001 Test City, France',
            'website' => 'https://www.clienttest.com',
            'sector' => 'Technology',
            'is_active' => true,
            'created_by' => $user->id
        ]);

        // Créer des préférences de notification
        UserNotificationPreference::create([
            'user_id' => $user->id,
            'type' => 'appointment_reminder',
            'timing' => [15, 60, 1440], // 15 min, 1h, 1 jour
            'email_enabled' => true,
            'is_active' => true
        ]);

        // Créer des rendez-vous de test
        // Rendez-vous dans 2 heures
        Appointment::create([
            'title' => 'Rendez-vous test - 2 heures',
            'description' => 'Test appointment in 2 hours',
            'scheduled_at' => now()->addHours(2),
            'duration' => 60,
            'location' => 'Bureau Test',
            'status' => 'scheduled',
            'client_id' => $client->id,
            'user_id' => $user->id
        ]);

        // Rendez-vous demain
        Appointment::create([
            'title' => 'Rendez-vous test - demain',
            'description' => 'Test appointment tomorrow',
            'scheduled_at' => now()->addDay()->setHour(14)->setMinute(0)->setSecond(0),
            'duration' => 90,
            'location' => 'Chez le client',
            'status' => 'scheduled',
            'client_id' => $client->id,
            'user_id' => $user->id
        ]);

        // Rendez-vous dans une semaine
        Appointment::create([
            'title' => 'Rendez-vous test - semaine',
            'description' => 'Test appointment next week',
            'scheduled_at' => now()->addWeek()->setHour(10)->setMinute(30)->setSecond(0),
            'duration' => 120,
            'location' => 'Visioconférence',
            'status' => 'scheduled',
            'client_id' => $client->id,
            'user_id' => $user->id
        ]);

        echo "Test data created successfully:\n";
        echo "- User: {$user->email} (password: password)\n";
        echo "- Client: {$client->name}\n";
        echo "- 3 appointments created\n";
        echo "- Notification preferences set\n";
    }
}
