<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class CallCenterPermissionsSeeder extends Seeder
{
    public function run()
    {
        DB::beginTransaction();
        try {
            $permissions = [
                ['name' => 'call_center.access', 'display_name' => 'Accès Call Center', 'module' => 'call_center', 'action' => 'access', 'scope' => 'global'],
                ['name' => 'call_center.create', 'display_name' => 'Créer Appel', 'module' => 'call_center', 'action' => 'create', 'scope' => 'global'],
                ['name' => 'call_center.view', 'display_name' => 'Voir Appels', 'module' => 'call_center', 'action' => 'view', 'scope' => 'global'],
                ['name' => 'call_center.supervisor', 'display_name' => 'Superviseur Call Center', 'module' => 'call_center', 'action' => 'supervisor', 'scope' => 'global'],
                ['name' => 'call_center.admin', 'display_name' => 'Admin Call Center', 'module' => 'call_center', 'action' => 'admin', 'scope' => 'global'],
                // Add any other specific ones you've used in your code here
            ];

            foreach ($permissions as $p) {
                Permission::firstOrCreate(['name' => $p['name']], $p);
            }

            // Sync with Super Admin
            $superAdmin = Role::where('name', 'super_admin')->first();
            if ($superAdmin) {
                $ccPermissions = Permission::where('module', 'call_center')->get();
                $superAdmin->permissions()->syncWithoutDetaching($ccPermissions);
            }

            DB::commit();
            $this->command->info('Call Center permissions seeded and linked to Super Admin!');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}