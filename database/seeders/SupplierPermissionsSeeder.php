<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class SupplierPermissionsSeeder extends Seeder
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
            // Créer les permissions SUPPLIERS
            $supplierPermissions = [
                [
                    'name' => 'suppliers.view',
                    'display_name' => 'Voir les fournisseurs',
                    'description' => 'Permet de consulter la liste des fournisseurs',
                    'module' => 'suppliers',
                    'action' => 'view',
                    'scope' => 'all',
                    'is_active' => true
                ],
                [
                    'name' => 'suppliers.create',
                    'display_name' => 'Créer des fournisseurs',
                    'description' => 'Permet de créer de nouveaux fournisseurs',
                    'module' => 'suppliers'
                ],
                [
                    'name' => 'suppliers.edit',
                    'display_name' => 'Modifier les fournisseurs',
                    'description' => 'Permet de modifier les informations des fournisseurs',
                    'module' => 'suppliers'
                ],
                [
                    'name' => 'suppliers.delete',
                    'display_name' => 'Supprimer des fournisseurs',
                    'description' => 'Permet de supprimer des fournisseurs',
                    'module' => 'suppliers'
                ],
                [
                    'name' => 'suppliers.manage',
                    'display_name' => 'Gérer les fournisseurs',
                    'description' => 'Accès complet à la gestion des fournisseurs',
                    'module' => 'suppliers'
                ],
                [
                    'name' => 'suppliers.contracts.view',
                    'display_name' => 'Voir les contrats fournisseurs',
                    'description' => 'Permet de consulter les contrats des fournisseurs',
                    'module' => 'suppliers'
                ],
                [
                    'name' => 'suppliers.contracts.manage',
                    'display_name' => 'Gérer les contrats fournisseurs',
                    'description' => 'Permet de gérer les contrats des fournisseurs',
                    'module' => 'suppliers'
                ],
                [
                    'name' => 'suppliers.payments.view',
                    'display_name' => 'Voir les paiements fournisseurs',
                    'description' => 'Permet de consulter les paiements aux fournisseurs',
                    'module' => 'suppliers'
                ],
                [
                    'name' => 'suppliers.payments.manage',
                    'display_name' => 'Gérer les paiements fournisseurs',
                    'description' => 'Permet de gérer les paiements aux fournisseurs',
                    'module' => 'suppliers'
                ],
                [
                    'name' => 'suppliers.orders.view',
                    'display_name' => 'Voir les commandes fournisseurs',
                    'description' => 'Permet de consulter les commandes aux fournisseurs',
                    'module' => 'suppliers'
                ],
                [
                    'name' => 'suppliers.orders.create',
                    'display_name' => 'Créer des commandes fournisseurs',
                    'description' => 'Permet de créer des commandes aux fournisseurs',
                    'module' => 'suppliers'
                ],
                [
                    'name' => 'suppliers.orders.manage',
                    'display_name' => 'Gérer les commandes fournisseurs',
                    'description' => 'Permet de gérer les commandes aux fournisseurs',
                    'module' => 'suppliers'
                ],
                [
                    'name' => 'suppliers.reports.view',
                    'display_name' => 'Voir les rapports fournisseurs',
                    'description' => 'Permet de consulter les rapports sur les fournisseurs',
                    'module' => 'suppliers'
                ],
                [
                    'name' => 'suppliers.export',
                    'display_name' => 'Exporter les fournisseurs',
                    'description' => 'Permet d\'exporter la liste des fournisseurs',
                    'module' => 'suppliers',
                ]
            ];

            foreach ($supplierPermissions as $permissionData) {
                Permission::firstOrCreate(
                    ['name' => $permissionData['name']],
                    $permissionData
                );
            }

            // Assigner les permissions aux rôles appropriés
            $this->assignPermissionsToRoles();

            DB::commit();

            echo "Permissions SUPPLIERS créées avec succès !\n";
            echo "14 permissions ajoutées au module suppliers\n";

        } catch (\Exception $e) {
            DB::rollBack();
            echo "Erreur lors de la création des permissions: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    private function assignPermissionsToRoles()
    {
        // Super Admin - Toutes les permissions
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $allSupplierPermissions = Permission::where('module', 'suppliers')->get();
            $superAdminRole->permissions()->syncWithoutDetaching($allSupplierPermissions);
        }

        // Admin - Toutes les permissions sauf delete
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminSupplierPermissions = Permission::where('module', 'suppliers')
                ->where('name', '!=', 'suppliers.delete')
                ->get();
            $adminRole->permissions()->syncWithoutDetaching($adminSupplierPermissions);
        }

        // Manager - Permissions de consultation et gestion
        $managerRole = Role::where('name', 'manager')->first();
        if ($managerRole) {
            $managerPermissions = Permission::whereIn('name', [
                'suppliers.view',
                'suppliers.create',
                'suppliers.edit',
                'suppliers.contracts.view',
                'suppliers.contracts.manage',
                'suppliers.orders.view',
                'suppliers.orders.create',
                'suppliers.orders.manage',
                'suppliers.reports.view',
                'suppliers.export'
            ])->get();
            $managerRole->permissions()->syncWithoutDetaching($managerPermissions);
        }

        // Commercial - Permissions de base
        $commercialRole = Role::where('name', 'commercial')->first();
        if ($commercialRole) {
            $commercialPermissions = Permission::whereIn('name', [
                'suppliers.view',
                'suppliers.orders.view',
                'suppliers.orders.create',
                'suppliers.reports.view'
            ])->get();
            $commercialRole->permissions()->syncWithoutDetaching($commercialPermissions);
        }
    }
}
