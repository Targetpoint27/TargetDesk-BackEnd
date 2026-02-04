# 🔧 Commande pour ajouter les permissions SUPPLIERS en production

## Problème
Le seeder `RolesAndPermissionsSeeder` n'inclut pas les nouvelles permissions SUPPLIERS.

## ✅ Solution simple - Commandes SQL directes

Exécutez ces commandes SQL directement dans phpMyAdmin ou Terminal :

```sql
-- 1. Insérer les permissions SUPPLIERS
INSERT IGNORE INTO permissions (name, display_name, description, module, action, scope, is_active, created_at, updated_at) VALUES
('suppliers.view', 'Voir les fournisseurs', 'Permet de consulter la liste des fournisseurs', 'suppliers', 'view', 'all', 1, NOW(), NOW()),
('suppliers.create', 'Créer des fournisseurs', 'Permet de créer de nouveaux fournisseurs', 'suppliers', 'create', 'all', 1, NOW(), NOW()),
('suppliers.edit', 'Modifier les fournisseurs', 'Permet de modifier les informations des fournisseurs', 'suppliers', 'edit', 'all', 1, NOW(), NOW()),
('suppliers.delete', 'Supprimer des fournisseurs', 'Permet de supprimer des fournisseurs', 'suppliers', 'delete', 'all', 1, NOW(), NOW()),
('suppliers.manage', 'Gérer les fournisseurs', 'Accès complet à la gestion des fournisseurs', 'suppliers', 'manage', 'all', 1, NOW(), NOW()),
('suppliers.contracts.view', 'Voir les contrats fournisseurs', 'Permet de consulter les contrats des fournisseurs', 'suppliers', 'view', 'contracts', 1, NOW(), NOW()),
('suppliers.contracts.manage', 'Gérer les contrats fournisseurs', 'Permet de gérer les contrats des fournisseurs', 'suppliers', 'manage', 'contracts', 1, NOW(), NOW()),
('suppliers.payments.view', 'Voir les paiements fournisseurs', 'Permet de consulter les paiements aux fournisseurs', 'suppliers', 'view', 'payments', 1, NOW(), NOW()),
('suppliers.payments.manage', 'Gérer les paiements fournisseurs', 'Permet de gérer les paiements aux fournisseurs', 'suppliers', 'manage', 'payments', 1, NOW(), NOW()),
('suppliers.orders.view', 'Voir les commandes fournisseurs', 'Permet de consulter les commandes aux fournisseurs', 'suppliers', 'view', 'orders', 1, NOW(), NOW()),
('suppliers.orders.create', 'Créer des commandes fournisseurs', 'Permet de créer des commandes aux fournisseurs', 'suppliers', 'create', 'orders', 1, NOW(), NOW()),
('suppliers.orders.manage', 'Gérer les commandes fournisseurs', 'Permet de gérer les commandes aux fournisseurs', 'suppliers', 'manage', 'orders', 1, NOW(), NOW()),
('suppliers.reports.view', 'Voir les rapports fournisseurs', 'Permet de consulter les rapports sur les fournisseurs', 'suppliers', 'view', 'reports', 1, NOW(), NOW()),
('suppliers.export', 'Exporter les fournisseurs', 'Permet d\'exporter la liste des fournisseurs', 'suppliers', 'export', 'all', 1, NOW(), NOW());

-- 2. Assigner les permissions aux rôles
-- Super Admin (supposons ID = 1)
INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at, updated_at)
SELECT 1, id, NOW(), NOW() FROM permissions WHERE module = 'suppliers';

-- Admin (supposons ID = 2) - Toutes sauf delete
INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at, updated_at)
SELECT 2, id, NOW(), NOW() FROM permissions WHERE module = 'suppliers' AND name != 'suppliers.delete';

-- Manager (supposons ID = 3) - Permissions gestion
INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at, updated_at)
SELECT 3, id, NOW(), NOW() FROM permissions
WHERE name IN (
    'suppliers.view', 'suppliers.create', 'suppliers.edit',
    'suppliers.contracts.view', 'suppliers.contracts.manage',
    'suppliers.orders.view', 'suppliers.orders.create', 'suppliers.orders.manage',
    'suppliers.reports.view', 'suppliers.export'
);

-- Commercial (supposons ID = 4) - Permissions de base
INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at, updated_at)
SELECT 4, id, NOW(), NOW() FROM permissions
WHERE name IN (
    'suppliers.view', 'suppliers.orders.view',
    'suppliers.orders.create', 'suppliers.reports.view'
);
```

## 🔍 Vérification

```sql
-- Vérifier que les permissions sont créées
SELECT name, display_name, module FROM permissions WHERE module = 'suppliers';

-- Vérifier les assignations aux rôles
SELECT r.name as role_name, p.name as permission_name
FROM role_permissions rp
JOIN roles r ON rp.role_id = r.id
JOIN permissions p ON rp.permission_id = p.id
WHERE p.module = 'suppliers'
ORDER BY r.name, p.name;
```

## ⚠️ IMPORTANT

Avant d'exécuter, vérifiez les IDs de vos rôles :

```sql
SELECT id, name FROM roles ORDER BY id;
```

Et ajustez les IDs dans les requêtes INSERT ci-dessus.

## ✅ Alternative - Commande Artisan

Si vous préférez, exécutez cette commande dans Terminal cPanel :

```bash
cd ~/public_html/api
php artisan tinker --execute="
\$permissions = ['suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete', 'suppliers.manage', 'suppliers.contracts.view', 'suppliers.contracts.manage', 'suppliers.payments.view', 'suppliers.payments.manage', 'suppliers.orders.view', 'suppliers.orders.create', 'suppliers.orders.manage', 'suppliers.reports.view', 'suppliers.export'];
foreach(\$permissions as \$perm) {
    \$parts = explode('.', \$perm);
    \$action = end(\$parts);
    \$scope = count(\$parts) > 2 ? \$parts[1] : 'all';
    \App\Models\Permission::firstOrCreate(['name' => \$perm], [
        'display_name' => ucfirst(str_replace('.', ' ', \$perm)),
        'description' => 'Permission ' . \$perm,
        'module' => 'suppliers',
        'action' => \$action,
        'scope' => \$scope,
        'is_active' => true
    ]);
}
echo '14 permissions SUPPLIERS créées !';
"
```

---

**Les permissions SUPPLIERS seront maintenant disponibles dans votre système !** 🚀