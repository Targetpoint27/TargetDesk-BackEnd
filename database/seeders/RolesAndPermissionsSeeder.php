<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::beginTransaction();

        try {
            // Create permissions first
            $this->createPermissions();

            // Create predefined roles
            $this->createRoles();

            // Assign permissions to roles
            $this->assignPermissionsToRoles();

            // Assign super admin role to first user if exists
            $this->assignSuperAdminToFirstUser();

            DB::commit();

            $this->command->info('Roles and permissions seeded successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            $this->command->error('Error seeding roles and permissions: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create all permissions
     */
    private function createPermissions()
    {
        $permissions = [
            // System permissions
            ['name' => 'system.manage', 'display_name' => 'Manage System', 'description' => 'Full system management access', 'module' => 'system', 'action' => 'manage', 'scope' => 'global'],
            ['name' => 'system.view', 'display_name' => 'View System Info', 'description' => 'View system information', 'module' => 'system', 'action' => 'view', 'scope' => 'global'],

            // User management permissions
            ['name' => 'users.create', 'display_name' => 'Create Users', 'description' => 'Create new users', 'module' => 'users', 'action' => 'create', 'scope' => 'global'],
            ['name' => 'users.read', 'display_name' => 'View Users', 'description' => 'View all users', 'module' => 'users', 'action' => 'read', 'scope' => 'global'],
            ['name' => 'users.update', 'display_name' => 'Update Users', 'description' => 'Update user information', 'module' => 'users', 'action' => 'update', 'scope' => 'global'],
            ['name' => 'users.delete', 'display_name' => 'Delete Users', 'description' => 'Delete users', 'module' => 'users', 'action' => 'delete', 'scope' => 'global'],
            ['name' => 'users.read.own', 'display_name' => 'View Own Profile', 'description' => 'View own user profile', 'module' => 'users', 'action' => 'read', 'scope' => 'own'],
            ['name' => 'users.update.own', 'display_name' => 'Update Own Profile', 'description' => 'Update own user profile', 'module' => 'users', 'action' => 'update', 'scope' => 'own'],

            // Role management permissions
            ['name' => 'roles.create', 'display_name' => 'Create Roles', 'description' => 'Create new roles', 'module' => 'roles', 'action' => 'create', 'scope' => 'global'],
            ['name' => 'roles.read', 'display_name' => 'View Roles', 'description' => 'View all roles', 'module' => 'roles', 'action' => 'read', 'scope' => 'global'],
            ['name' => 'roles.update', 'display_name' => 'Update Roles', 'description' => 'Update role information', 'module' => 'roles', 'action' => 'update', 'scope' => 'global'],
            ['name' => 'roles.delete', 'display_name' => 'Delete Roles', 'description' => 'Delete custom roles', 'module' => 'roles', 'action' => 'delete', 'scope' => 'global'],
            ['name' => 'roles.assign', 'display_name' => 'Assign Roles', 'description' => 'Assign roles to users', 'module' => 'roles', 'action' => 'assign', 'scope' => 'global'],

            // Permission management
            ['name' => 'permissions.read', 'display_name' => 'View Permissions', 'description' => 'View all permissions', 'module' => 'permissions', 'action' => 'read', 'scope' => 'global'],
            ['name' => 'permissions.check', 'display_name' => 'Check Permissions', 'description' => 'Check user permissions', 'module' => 'permissions', 'action' => 'check', 'scope' => 'global'],

            // Client permissions - Global access
            ['name' => 'clients.create', 'display_name' => 'Create Clients', 'description' => 'Create new clients', 'module' => 'clients', 'action' => 'create', 'scope' => 'global'],
            ['name' => 'clients.read', 'display_name' => 'View All Clients', 'description' => 'View all clients in system', 'module' => 'clients', 'action' => 'read', 'scope' => 'global'],
            ['name' => 'clients.update', 'display_name' => 'Update All Clients', 'description' => 'Update any client', 'module' => 'clients', 'action' => 'update', 'scope' => 'global'],
            ['name' => 'clients.delete', 'display_name' => 'Delete Clients', 'description' => 'Delete any client', 'module' => 'clients', 'action' => 'delete', 'scope' => 'global'],
            ['name' => 'clients.export', 'display_name' => 'Export All Clients', 'description' => 'Export all clients data', 'module' => 'clients', 'action' => 'export', 'scope' => 'global'],

            // Client permissions - Own access (commercial users)
            ['name' => 'clients.read.own', 'display_name' => 'View Own Clients', 'description' => 'View only assigned clients', 'module' => 'clients', 'action' => 'read', 'scope' => 'own'],
            ['name' => 'clients.update.own', 'display_name' => 'Update Own Clients', 'description' => 'Update only assigned clients', 'module' => 'clients', 'action' => 'update', 'scope' => 'own'],
            ['name' => 'clients.export.own', 'display_name' => 'Export Own Clients', 'description' => 'Export own clients data', 'module' => 'clients', 'action' => 'export', 'scope' => 'own'],

            // Client permissions - Team access (managers)
            ['name' => 'clients.read.team', 'display_name' => 'View Team Clients', 'description' => 'View team clients', 'module' => 'clients', 'action' => 'read', 'scope' => 'team'],
            ['name' => 'clients.update.team', 'display_name' => 'Update Team Clients', 'description' => 'Update team clients', 'module' => 'clients', 'action' => 'update', 'scope' => 'team'],
            ['name' => 'clients.export.team', 'display_name' => 'Export Team Clients', 'description' => 'Export team clients data', 'module' => 'clients', 'action' => 'export', 'scope' => 'team'],

            // Contact permissions
            ['name' => 'contacts.create', 'display_name' => 'Create Contacts', 'description' => 'Create new contacts', 'module' => 'contacts', 'action' => 'create', 'scope' => 'global'],
            ['name' => 'contacts.read', 'display_name' => 'View All Contacts', 'description' => 'View all contacts', 'module' => 'contacts', 'action' => 'read', 'scope' => 'global'],
            ['name' => 'contacts.update', 'display_name' => 'Update Contacts', 'description' => 'Update contact information', 'module' => 'contacts', 'action' => 'update', 'scope' => 'global'],
            ['name' => 'contacts.delete', 'display_name' => 'Delete Contacts', 'description' => 'Delete contacts', 'module' => 'contacts', 'action' => 'delete', 'scope' => 'global'],
            ['name' => 'contacts.read.own', 'display_name' => 'View Own Contacts', 'description' => 'View own client contacts', 'module' => 'contacts', 'action' => 'read', 'scope' => 'own'],
            ['name' => 'contacts.update.own', 'display_name' => 'Update Own Contacts', 'description' => 'Update own client contacts', 'module' => 'contacts', 'action' => 'update', 'scope' => 'own'],

            // Document permissions
            ['name' => 'documents.create', 'display_name' => 'Create Documents', 'description' => 'Create new documents', 'module' => 'documents', 'action' => 'create', 'scope' => 'global'],
            ['name' => 'documents.read', 'display_name' => 'View All Documents', 'description' => 'View all documents', 'module' => 'documents', 'action' => 'read', 'scope' => 'global'],
            ['name' => 'documents.update', 'display_name' => 'Update Documents', 'description' => 'Update document information', 'module' => 'documents', 'action' => 'update', 'scope' => 'global'],
            ['name' => 'documents.delete', 'display_name' => 'Delete Documents', 'description' => 'Delete documents', 'module' => 'documents', 'action' => 'delete', 'scope' => 'global'],
            ['name' => 'documents.read.own', 'display_name' => 'View Own Documents', 'description' => 'View own client documents', 'module' => 'documents', 'action' => 'read', 'scope' => 'own'],
            ['name' => 'documents.update.own', 'display_name' => 'Update Own Documents', 'description' => 'Update own client documents', 'module' => 'documents', 'action' => 'update', 'scope' => 'own'],

            // Dashboard permissions
            ['name' => 'dashboard.commercial', 'display_name' => 'Commercial Dashboard', 'description' => 'Access commercial dashboard', 'module' => 'dashboard', 'action' => 'view', 'scope' => 'global'],
            ['name' => 'dashboard.personal', 'display_name' => 'Personal Dashboard', 'description' => 'Access personal dashboard', 'module' => 'dashboard', 'action' => 'view', 'scope' => 'own'],

            // Reports permissions
            ['name' => 'reports.generate', 'display_name' => 'Generate Reports', 'description' => 'Generate system reports', 'module' => 'reports', 'action' => 'generate', 'scope' => 'global'],
            ['name' => 'reports.view', 'display_name' => 'View Reports', 'description' => 'View all reports', 'module' => 'reports', 'action' => 'view', 'scope' => 'global'],
            ['name' => 'reports.view.own', 'display_name' => 'View Own Reports', 'description' => 'View own reports', 'module' => 'reports', 'action' => 'view', 'scope' => 'own'],

            // Team view permissions
            ['name' => 'team.view.enable', 'display_name' => 'Enable Team View', 'description' => 'Enable team view mode', 'module' => 'team', 'action' => 'view', 'scope' => 'team'],
            ['name' => 'team.view.logs', 'display_name' => 'View Team Logs', 'description' => 'View team view access logs', 'module' => 'team', 'action' => 'logs', 'scope' => 'global'],

            // Access control permissions
            ['name' => 'access.rules.create', 'display_name' => 'Create Access Rules', 'description' => 'Create access control rules', 'module' => 'access', 'action' => 'create', 'scope' => 'global'],
            ['name' => 'access.rules.read', 'display_name' => 'View Access Rules', 'description' => 'View access control rules', 'module' => 'access', 'action' => 'read', 'scope' => 'global'],
            ['name' => 'access.rules.update', 'display_name' => 'Update Access Rules', 'description' => 'Update access control rules', 'module' => 'access', 'action' => 'update', 'scope' => 'global'],
            ['name' => 'access.rules.delete', 'display_name' => 'Delete Access Rules', 'description' => 'Delete access control rules', 'module' => 'access', 'action' => 'delete', 'scope' => 'global']
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        $this->command->info('Permissions created: ' . count($permissions));
    }

    /**
     * Create predefined roles
     */
    private function createRoles()
    {
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Admin',
                'description' => 'Full system access with all permissions',
                'is_predefined' => true
            ],
            [
                'name' => 'admin',
                'display_name' => 'Admin',
                'description' => 'Administrator with user and role management permissions',
                'is_predefined' => true
            ],
            [
                'name' => 'manager',
                'display_name' => 'Manager',
                'description' => 'Manager with team oversight and advanced client access',
                'is_predefined' => true
            ],
            [
                'name' => 'commercial',
                'display_name' => 'Commercial',
                'description' => 'Commercial user with access to own clients and basic features',
                'is_predefined' => true
            ],
            [
                'name' => 'consultant',
                'display_name' => 'Consultant',
                'description' => 'Read-only consultant access to assigned clients',
                'is_predefined' => true
            ]
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                $role
            );
        }

        $this->command->info('Roles created: ' . count($roles));
    }

    /**
     * Assign permissions to roles
     */
    private function assignPermissionsToRoles()
    {
        // Super Admin - All permissions
        $superAdmin = Role::where('name', 'super_admin')->first();
        $allPermissions = Permission::all();
        $superAdmin->permissions()->sync($allPermissions->pluck('id')->toArray());

        // Admin - User, role, and system management
        $admin = Role::where('name', 'admin')->first();
        $adminPermissions = Permission::whereIn('name', [
            'users.create', 'users.read', 'users.update', 'users.delete',
            'roles.create', 'roles.read', 'roles.update', 'roles.delete', 'roles.assign',
            'permissions.read', 'permissions.check',
            'clients.read', 'clients.update', 'clients.export',
            'contacts.read', 'contacts.update',
            'documents.read', 'documents.update',
            'dashboard.commercial', 'dashboard.personal',
            'reports.generate', 'reports.view',
            'team.view.logs',
            'access.rules.create', 'access.rules.read', 'access.rules.update', 'access.rules.delete'
        ])->pluck('id')->toArray();
        $admin->permissions()->sync($adminPermissions);

        // Manager - Team management and oversight
        $manager = Role::where('name', 'manager')->first();
        $managerPermissions = Permission::whereIn('name', [
            'users.read', 'users.update.own',
            'roles.read',
            'permissions.read', 'permissions.check',
            'clients.create', 'clients.read.team', 'clients.update.team', 'clients.export.team',
            'contacts.create', 'contacts.read.own', 'contacts.update.own',
            'documents.create', 'documents.read.own', 'documents.update.own',
            'dashboard.commercial', 'dashboard.personal',
            'reports.view', 'reports.view.own',
            'team.view.enable', 'team.view.logs'
        ])->pluck('id')->toArray();
        $manager->permissions()->sync($managerPermissions);

        // Commercial - Own clients and basic operations
        $commercial = Role::where('name', 'commercial')->first();
        $commercialPermissions = Permission::whereIn('name', [
            'users.read.own', 'users.update.own',
            'permissions.check',
            'clients.create', 'clients.read.own', 'clients.update.own', 'clients.export.own',
            'contacts.create', 'contacts.read.own', 'contacts.update.own',
            'documents.create', 'documents.read.own', 'documents.update.own',
            'dashboard.personal',
            'reports.view.own'
        ])->pluck('id')->toArray();
        $commercial->permissions()->sync($commercialPermissions);

        // Consultant - Read-only access
        $consultant = Role::where('name', 'consultant')->first();
        $consultantPermissions = Permission::whereIn('name', [
            'users.read.own', 'users.update.own',
            'permissions.check',
            'clients.read.own',
            'contacts.read.own',
            'documents.read.own',
            'dashboard.personal',
            'reports.view.own'
        ])->pluck('id')->toArray();
        $consultant->permissions()->sync($consultantPermissions);

        $this->command->info('Permissions assigned to all roles');
    }

    /**
     * Assign super admin role to first user if exists
     */
    private function assignSuperAdminToFirstUser()
    {
        $firstUser = User::first();
        $superAdminRole = Role::where('name', 'super_admin')->first();

        if ($firstUser && $superAdminRole) {
            if (!$firstUser->hasRole($superAdminRole)) {
                $firstUser->assignRole($superAdminRole, null);
                $this->command->info('Super Admin role assigned to first user: ' . $firstUser->email);
            } else {
                $this->command->info('First user already has Super Admin role');
            }
        } else {
            $this->command->warn('No users found or super admin role not created');
        }
    }
}
