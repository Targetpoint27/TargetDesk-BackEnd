# 🔐 Système de Permissions TargetDesk CRM

Ce document liste toutes les permissions disponibles dans le système TargetDesk CRM avec leurs descriptions détaillées.

## 📊 Vue d'ensemble

Le système contient **62 permissions** organisées par modules fonctionnels :
- **Contrôle d'accès** (4 permissions)
- **Gestion des clients** (10 permissions)
- **Gestion des contacts** (6 permissions)
- **Tableau de bord** (2 permissions)
- **Gestion des documents** (6 permissions)
- **Système de permissions** (2 permissions)
- **Rapports** (3 permissions)
- **Gestion des rôles** (5 permissions)
- **Gestion des fournisseurs** (12 permissions)
- **Administration système** (2 permissions)
- **Vue d'équipe** (2 permissions)
- **Gestion des utilisateurs** (6 permissions)

---

## 🛡️ Contrôle d'Accès (Access Rules)

| Permission | Description |
|------------|-------------|
| `access.rules.create` | Créer des règles de contrôle d'accès |
| `access.rules.delete` | Supprimer des règles de contrôle d'accès |
| `access.rules.read` | Voir les règles de contrôle d'accès |
| `access.rules.update` | Mettre à jour les règles de contrôle d'accès |

---

## 👥 Gestion des Clients

| Permission | Description | Portée |
|------------|-------------|--------|
| `clients.create` | Créer de nouveaux clients | Système |
| `clients.delete` | Supprimer n'importe quel client | Système |
| `clients.export` | Exporter toutes les données clients | Système |
| `clients.export.own` | Exporter ses propres données clients | Personnel |
| `clients.export.team` | Exporter les données clients de l'équipe | Équipe |
| `clients.read` | Voir tous les clients du système | Système |
| `clients.read.own` | Voir uniquement les clients assignés | Personnel |
| `clients.read.team` | Voir les clients de l'équipe | Équipe |
| `clients.update` | Mettre à jour n'importe quel client | Système |
| `clients.update.own` | Mettre à jour uniquement les clients assignés | Personnel |
| `clients.update.team` | Mettre à jour les clients de l'équipe | Équipe |

---

## 📞 Gestion des Contacts

| Permission | Description | Portée |
|------------|-------------|--------|
| `contacts.create` | Créer de nouveaux contacts | Système |
| `contacts.delete` | Supprimer des contacts | Système |
| `contacts.read` | Voir tous les contacts | Système |
| `contacts.read.own` | Voir les contacts de ses propres clients | Personnel |
| `contacts.update` | Mettre à jour les informations de contact | Système |
| `contacts.update.own` | Mettre à jour les contacts de ses propres clients | Personnel |

---

## 📈 Tableau de Bord

| Permission | Description |
|------------|-------------|
| `dashboard.commercial` | Accès au tableau de bord commercial |
| `dashboard.personal` | Accès au tableau de bord personnel |

---

## 📁 Gestion des Documents

| Permission | Description | Portée |
|------------|-------------|--------|
| `documents.create` | Créer de nouveaux documents | Système |
| `documents.delete` | Supprimer des documents | Système |
| `documents.read` | Voir tous les documents | Système |
| `documents.read.own` | Voir les documents de ses propres clients | Personnel |
| `documents.update` | Mettre à jour les informations de document | Système |
| `documents.update.own` | Mettre à jour les documents de ses propres clients | Personnel |

---

## 🔑 Système de Permissions

| Permission | Description |
|------------|-------------|
| `permissions.check` | Vérifier les permissions utilisateur |
| `permissions.read` | Voir toutes les permissions |

---

## 📊 Rapports

| Permission | Description | Portée |
|------------|-------------|--------|
| `reports.generate` | Générer des rapports système | Système |
| `reports.view` | Voir tous les rapports | Système |
| `reports.view.own` | Voir ses propres rapports | Personnel |

---

## 🎭 Gestion des Rôles

| Permission | Description |
|------------|-------------|
| `roles.assign` | Assigner des rôles aux utilisateurs |
| `roles.create` | Créer de nouveaux rôles |
| `roles.delete` | Supprimer les rôles personnalisés |
| `roles.read` | Voir tous les rôles |
| `roles.update` | Mettre à jour les informations de rôle |

---

## 🏢 Gestion des Fournisseurs

| Permission | Description | Portée |
|------------|-------------|--------|
| `suppliers.create` | Créer de nouveaux fournisseurs | Système |
| `suppliers.delete.all` | Supprimer n'importe quel fournisseur | Système |
| `suppliers.delete.own` | Supprimer uniquement les fournisseurs assignés | Personnel |
| `suppliers.delete.team` | Supprimer les fournisseurs assignés à l'équipe | Équipe |
| `suppliers.export.all` | Exporter toutes les données fournisseurs | Système |
| `suppliers.export.own` | Exporter ses propres données fournisseurs | Personnel |
| `suppliers.export.team` | Exporter les données fournisseurs de l'équipe | Équipe |
| `suppliers.import` | Importer des fournisseurs depuis des sources externes | Système |
| `suppliers.read.all` | Voir tous les fournisseurs du système | Système |
| `suppliers.read.own` | Voir uniquement les fournisseurs assignés | Personnel |
| `suppliers.read.team` | Voir les fournisseurs assignés à l'équipe | Équipe |
| `suppliers.update.all` | Mettre à jour n'importe quel fournisseur | Système |
| `suppliers.update.own` | Mettre à jour uniquement les fournisseurs assignés | Personnel |
| `suppliers.update.team` | Mettre à jour les fournisseurs assignés à l'équipe | Équipe |

---

## ⚙️ Administration Système

| Permission | Description |
|------------|-------------|
| `system.manage` | Accès complet à la gestion du système |
| `system.view` | Voir les informations système |

---

## 👁️ Vue d'Équipe

| Permission | Description |
|------------|-------------|
| `team.view.enable` | Activer le mode vue d'équipe |
| `team.view.logs` | Voir les logs d'accès de la vue d'équipe |

---

## 👤 Gestion des Utilisateurs

| Permission | Description | Portée |
|------------|-------------|--------|
| `users.create` | Créer de nouveaux utilisateurs | Système |
| `users.delete` | Supprimer des utilisateurs | Système |
| `users.read` | Voir tous les utilisateurs | Système |
| `users.read.own` | Voir son propre profil utilisateur | Personnel |
| `users.update` | Mettre à jour les informations utilisateur | Système |
| `users.update.own` | Mettre à jour son propre profil utilisateur | Personnel |

---

## 🔍 Niveaux de Portée

### 🌐 Système
Permissions qui s'appliquent à l'ensemble du système sans restriction.

### 👤 Personnel
Permissions limitées aux données personnelles de l'utilisateur.

### 👥 Équipe
Permissions qui s'appliquent aux données de l'équipe de l'utilisateur.

---

## 📝 Notes d'Utilisation

1. **Hiérarchie des permissions** : Les permissions système incluent généralement les permissions équipe et personnelles.

2. **Sécurité** : Toutes les permissions sont vérifiées côté serveur avant l'exécution des actions.

3. **Flexibilité** : Le système permet une granularité fine dans l'attribution des permissions.

4. **Audit** : Toutes les actions liées aux permissions sont loggées pour traçabilité.

---

*Généré automatiquement le 10 février 2026*