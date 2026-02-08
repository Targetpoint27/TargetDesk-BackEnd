<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'name' => 'agent',
                'display_name' => 'Agent',
                'description' => 'Agent du call center - Traite les appels quotidiens',
                'is_predefined' => true,
                'is_active' => true,
                'created_by' => 1, // Admin user
            ],
            [
                'name' => 'supervisor',
                'display_name' => 'Superviseur',
                'description' => 'Superviseur - Gère une équipe d\'agents',
                'is_predefined' => true,
                'is_active' => true,
                'created_by' => 1,
            ],
            [
                'name' => 'manager',
                'display_name' => 'Manager',
                'description' => 'Manager - Gère le département call center',
                'is_predefined' => true,
                'is_active' => true,
                'created_by' => 1,
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrateur',
                'description' => 'Administrateur système - Accès complet à toutes les fonctionnalités',
                'is_predefined' => true,
                'is_active' => true,
                'created_by' => 1,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }

        $this->command->info('4 roles prédéfinis créés avec succès!');
        $this->command->info('   - agent');
        $this->command->info('   - supervisor');
        $this->command->info('   - manager');
        $this->command->info('   - admin');
    }
}