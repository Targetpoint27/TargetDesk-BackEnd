# Guide Frontend - Système de Permissions TargetDesk CRM

## Vue d'ensemble

Ce document détaille l'ensemble du système de permissions de TargetDesk CRM pour faciliter l'implémentation côté frontend. Le système utilise un modèle RBAC (Role-Based Access Control) avec des permissions granulaires organisées par modules, actions et portées.

## 🎯 Architecture des Permissions

### Structure des Permissions
```
permission_name = {module}.{action}.{scope}
```

**Exemples :**
- `clients.read.global` - Voir tous les clients
- `clients.read.own` - Voir seulement ses propres clients
- `users.update` - Mettre à jour les utilisateurs (global par défaut)

## 📊 Modules Disponibles

| Module | Description | Fonctionnalités |
|--------|-------------|----------------|
| **access** | Contrôle d'accès | Règles d'accès avancées |
| **clients** | Gestion clients | CRUD clients, export, recherche |
| **contacts** | Gestion contacts | Contacts clients/fournisseurs |
| **dashboard** | Tableaux de bord | Dashboards commercial/personnel |
| **documents** | Gestion documents | Documents clients/fournisseurs |
| **permissions** | Permissions | Vérification des droits |
| **reports** | Rapports | Génération et consultation |
| **roles** | Gestion rôles | CRUD rôles, assignation |
| **system** | Administration | Configuration système |
| **team** | Gestion équipe | Mode équipe, logs d'accès |
| **users** | Gestion utilisateurs | CRUD utilisateurs |

## 🔧 Actions Disponibles

| Action | Description | Utilisation |
|--------|-------------|-------------|
| `read` | Consultation | Affichage, liste, détails |
| `create` | Création | Nouveaux enregistrements |
| `update` | Modification | Édition d'enregistrements |
| `delete` | Suppression | Suppression d'enregistrements |
| `view` | Visualisation | Accès aux vues/dashboards |
| `export` | Exportation | Export de données |
| `generate` | Génération | Création de rapports |
| `assign` | Attribution | Assignation de rôles |
| `check` | Vérification | Contrôle de permissions |
| `manage` | Administration | Gestion complète |
| `logs` | Journalisation | Accès aux logs |

## 🎯 Portées (Scopes)

| Scope | Description | Application |
|-------|-------------|-------------|
| `global` | Accès complet | Toutes les données du système |
| `team` | Accès équipe | Données de l'équipe seulement |
| `own` | Accès personnel | Données propres à l'utilisateur |

## 📋 Liste Complète des Permissions

### 🏢 Module Clients

| Permission | Description | Scope |
|------------|-------------|-------|
| `clients.create` | Créer de nouveaux clients | global |
| `clients.read` | Voir tous les clients | global |
| `clients.read.team` | Voir les clients de l'équipe | team |
| `clients.read.own` | Voir seulement ses clients | own |
| `clients.update` | Modifier tous les clients | global |
| `clients.update.team` | Modifier les clients de l'équipe | team |
| `clients.update.own` | Modifier seulement ses clients | own |
| `clients.delete` | Supprimer des clients | global |
| `clients.export` | Exporter tous les clients | global |
| `clients.export.team` | Exporter les clients de l'équipe | team |
| `clients.export.own` | Exporter ses clients | own |

### 👥 Module Contacts

| Permission | Description | Scope |
|------------|-------------|-------|
| `contacts.create` | Créer des contacts | global |
| `contacts.read` | Voir tous les contacts | global |
| `contacts.read.own` | Voir ses contacts clients | own |
| `contacts.update` | Modifier tous les contacts | global |
| `contacts.update.own` | Modifier ses contacts | own |
| `contacts.delete` | Supprimer des contacts | global |

### 📄 Module Documents

| Permission | Description | Scope |
|------------|-------------|-------|
| `documents.create` | Créer des documents | global |
| `documents.read` | Voir tous les documents | global |
| `documents.read.own` | Voir ses documents clients | own |
| `documents.update` | Modifier tous les documents | global |
| `documents.update.own` | Modifier ses documents | own |
| `documents.delete` | Supprimer des documents | global |

### 👤 Module Users

| Permission | Description | Scope |
|------------|-------------|-------|
| `users.create` | Créer des utilisateurs | global |
| `users.read` | Voir tous les utilisateurs | global |
| `users.read.own` | Voir son profil | own |
| `users.update` | Modifier tous les utilisateurs | global |
| `users.update.own` | Modifier son profil | own |
| `users.delete` | Supprimer des utilisateurs | global |

### 🎭 Module Roles

| Permission | Description | Scope |
|------------|-------------|-------|
| `roles.create` | Créer des rôles | global |
| `roles.read` | Voir tous les rôles | global |
| `roles.update` | Modifier des rôles | global |
| `roles.delete` | Supprimer des rôles | global |
| `roles.assign` | Assigner des rôles | global |

### 📊 Module Dashboard

| Permission | Description | Scope |
|------------|-------------|-------|
| `dashboard.commercial` | Dashboard commercial/managérial | global |
| `dashboard.personal` | Dashboard personnel | own |

### 📈 Module Reports

| Permission | Description | Scope |
|------------|-------------|-------|
| `reports.generate` | Générer des rapports | global |
| `reports.view` | Voir tous les rapports | global |
| `reports.view.own` | Voir ses rapports | own |

### 🔐 Module Access

| Permission | Description | Scope |
|------------|-------------|-------|
| `access.rules.create` | Créer règles d'accès | global |
| `access.rules.read` | Voir règles d'accès | global |
| `access.rules.update` | Modifier règles d'accès | global |
| `access.rules.delete` | Supprimer règles d'accès | global |

### 👥 Module Team

| Permission | Description | Scope |
|------------|-------------|-------|
| `team.view.enable` | Activer mode équipe | team |
| `team.view.logs` | Voir logs d'accès équipe | global |

### 🔧 Module System

| Permission | Description | Scope |
|------------|-------------|-------|
| `system.view` | Voir infos système | global |
| `system.manage` | Administration complète | global |

### ✅ Module Permissions

| Permission | Description | Scope |
|------------|-------------|-------|
| `permissions.read` | Voir toutes les permissions | global |
| `permissions.check` | Vérifier permissions utilisateur | global |

## 👑 Rôles Prédéfinis

### Super Admin
**Permissions :** Toutes les permissions du système
- Accès complet à toutes les fonctionnalités
- Gestion des utilisateurs et rôles
- Configuration système

### Admin
**Permissions principales :**
- `users.*` (gestion utilisateurs)
- `roles.*` (gestion rôles)
- `clients.read`, `clients.update` (clients)
- `reports.view` (rapports)
- `dashboard.commercial` (dashboard)

### Manager
**Permissions principales :**
- `clients.*` (gestion clients complète)
- `contacts.*` (gestion contacts)
- `documents.*` (gestion documents)
- `reports.view`, `reports.generate` (rapports)
- `dashboard.commercial` (dashboard managérial)
- `team.view.enable` (mode équipe)

### Commercial
**Permissions principales :**
- `clients.read.own`, `clients.update.own` (ses clients)
- `contacts.read.own`, `contacts.update.own` (ses contacts)
- `documents.read.own`, `documents.update.own` (ses documents)
- `dashboard.personal` (dashboard personnel)
- `users.read.own`, `users.update.own` (son profil)

### Consultant
**Permissions principales (lecture seule) :**
- `clients.read.own` (voir ses clients)
- `contacts.read.own` (voir ses contacts)
- `documents.read.own` (voir ses documents)
- `reports.view.own` (ses rapports)
- `dashboard.personal` (dashboard personnel)

## 🔌 Intégration Frontend

### 1. Service Angular de Permissions

```typescript
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class PermissionService {
  private apiUrl = 'http://localhost:8000/api/v1';
  private userPermissions = new BehaviorSubject<string[]>([]);

  constructor(private http: HttpClient) {}

  // Récupérer les permissions de l'utilisateur connecté
  loadUserPermissions(): Observable<any> {
    return this.http.get(`${this.apiUrl}/users/me/permissions`);
  }

  // Vérifier une permission
  hasPermission(permission: string): boolean {
    return this.userPermissions.value.includes(permission);
  }

  // Vérifier multiple permissions (ET logique)
  hasAllPermissions(permissions: string[]): boolean {
    return permissions.every(permission => this.hasPermission(permission));
  }

  // Vérifier multiple permissions (OU logique)
  hasAnyPermission(permissions: string[]): boolean {
    return permissions.some(permission => this.hasPermission(permission));
  }

  // Vérifier permissions par module/action
  canAccess(module: string, action: string, scope: string = 'global'): boolean {
    const permission = scope === 'global'
      ? `${module}.${action}`
      : `${module}.${action}.${scope}`;

    // Essayer d'abord avec le scope spécifié, puis global
    return this.hasPermission(permission) ||
           (scope !== 'global' && this.hasPermission(`${module}.${action}`));
  }

  // Vérifier permissions avec fallback de scope
  canAccessWithFallback(module: string, action: string): boolean {
    return this.canAccess(module, action, 'global') ||
           this.canAccess(module, action, 'team') ||
           this.canAccess(module, action, 'own');
  }

  // Mettre à jour les permissions
  setPermissions(permissions: string[]): void {
    this.userPermissions.next(permissions);
  }

  // Obtenir les permissions actuelles
  getPermissions(): string[] {
    return this.userPermissions.value;
  }
}
```

### 2. Guard Angular pour la Protection des Routes

```typescript
import { Injectable } from '@angular/core';
import { CanActivate, ActivatedRouteSnapshot, RouterStateSnapshot, Router } from '@angular/router';
import { PermissionService } from './permission.service';

@Injectable({
  providedIn: 'root'
})
export class PermissionGuard implements CanActivate {

  constructor(
    private permissionService: PermissionService,
    private router: Router
  ) {}

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): boolean {
    const requiredPermissions = route.data['permissions'] as string[];
    const requireAll = route.data['requireAll'] || true;

    if (!requiredPermissions) {
      return true; // Pas de permissions requises
    }

    const hasAccess = requireAll
      ? this.permissionService.hasAllPermissions(requiredPermissions)
      : this.permissionService.hasAnyPermission(requiredPermissions);

    if (!hasAccess) {
      this.router.navigate(['/forbidden']);
      return false;
    }

    return true;
  }
}
```

### 3. Directive Angular pour Cacher les Éléments

```typescript
import { Directive, Input, TemplateRef, ViewContainerRef } from '@angular/core';
import { PermissionService } from './permission.service';

@Directive({
  selector: '[appHasPermission]'
})
export class HasPermissionDirective {
  private permissions: string[] = [];
  private requireAll: boolean = true;

  constructor(
    private templateRef: TemplateRef<any>,
    private viewContainer: ViewContainerRef,
    private permissionService: PermissionService
  ) {}

  @Input() set appHasPermission(permissions: string | string[]) {
    this.permissions = Array.isArray(permissions) ? permissions : [permissions];
    this.updateView();
  }

  @Input() set appHasPermissionRequireAll(requireAll: boolean) {
    this.requireAll = requireAll;
    this.updateView();
  }

  private updateView(): void {
    const hasAccess = this.requireAll
      ? this.permissionService.hasAllPermissions(this.permissions)
      : this.permissionService.hasAnyPermission(this.permissions);

    if (hasAccess) {
      this.viewContainer.createEmbeddedView(this.templateRef);
    } else {
      this.viewContainer.clear();
    }
  }
}
```

### 4. Utilisation dans les Templates

```html
<!-- Protection de bouton simple -->
<button
  *appHasPermission="'users.create'"
  (click)="createUser()">
  Créer Utilisateur
</button>

<!-- Protection avec multiple permissions (ET) -->
<div *appHasPermission="['clients.read', 'clients.update']">
  <client-edit></client-edit>
</div>

<!-- Protection avec multiple permissions (OU) -->
<div
  *appHasPermission="['clients.read.global', 'clients.read.team', 'clients.read.own']"
  [appHasPermissionRequireAll]="false">
  <client-list></client-list>
</div>

<!-- Utilisation avec service dans le composant -->
<ng-container *ngIf="canEditClient()">
  <button (click)="editClient()">Éditer</button>
</ng-container>
```

### 5. Configuration des Routes avec Permissions

```typescript
const routes: Routes = [
  {
    path: 'users',
    component: UsersComponent,
    canActivate: [PermissionGuard],
    data: { permissions: ['users.read'] }
  },
  {
    path: 'admin',
    component: AdminComponent,
    canActivate: [PermissionGuard],
    data: { permissions: ['system.manage', 'users.create'] }
  },
  {
    path: 'clients/create',
    component: ClientCreateComponent,
    canActivate: [PermissionGuard],
    data: { permissions: ['clients.create'] }
  },
  {
    path: 'reports',
    component: ReportsComponent,
    canActivate: [PermissionGuard],
    data: {
      permissions: ['reports.view', 'reports.view.own'],
      requireAll: false // OU logique
    }
  }
];
```

### 6. Composant Exemple avec Permissions

```typescript
@Component({
  selector: 'app-client-list',
  templateUrl: './client-list.component.html'
})
export class ClientListComponent implements OnInit {
  clients: Client[] = [];

  constructor(private permissionService: PermissionService) {}

  ngOnInit() {
    this.loadClients();
  }

  // Méthodes de vérification des permissions
  canCreateClient(): boolean {
    return this.permissionService.hasPermission('clients.create');
  }

  canEditClient(client: Client): boolean {
    return this.permissionService.canAccess('clients', 'update', 'global') ||
           (this.permissionService.canAccess('clients', 'update', 'own') &&
            client.created_by === this.currentUserId);
  }

  canDeleteClient(): boolean {
    return this.permissionService.hasPermission('clients.delete');
  }

  canExportClients(): boolean {
    return this.permissionService.canAccessWithFallback('clients', 'export');
  }

  // Actions conditionnelles
  exportClients() {
    if (this.canExportClients()) {
      // Logique d'export
    }
  }
}
```

## 🛠 API Endpoints pour Permissions

### Vérifier les Permissions Utilisateur
```http
GET /api/v1/users/me/permissions
Authorization: Bearer {token}
```

### Vérifier une Permission Spécifique
```http
POST /api/v1/permissions/check
Content-Type: application/json
{
  "permission": "clients.read.own"
}
```

### Vérifier Multiple Permissions
```http
POST /api/v1/permissions/bulk-check
Content-Type: application/json
{
  "permissions": ["clients.read", "clients.update", "clients.delete"]
}
```

### Obtenir les Permissions d'un Utilisateur
```http
GET /api/v1/permissions/user/{user_id}
Authorization: Bearer {token}
```

## 🎨 Exemples d'Utilisation par Cas

### Cas 1: Liste des Clients
```typescript
// Dans le composant
loadClients() {
  if (this.permissionService.hasPermission('clients.read')) {
    // Charger tous les clients
    this.clientService.getAllClients();
  } else if (this.permissionService.hasPermission('clients.read.team')) {
    // Charger clients équipe
    this.clientService.getTeamClients();
  } else if (this.permissionService.hasPermission('clients.read.own')) {
    // Charger ses propres clients
    this.clientService.getOwnClients();
  }
}
```

### Cas 2: Menu Navigation Dynamique
```typescript
interface MenuItem {
  label: string;
  route: string;
  permission: string;
  icon: string;
}

getMenuItems(): MenuItem[] {
  const allItems: MenuItem[] = [
    { label: 'Clients', route: '/clients', permission: 'clients.read', icon: 'people' },
    { label: 'Utilisateurs', route: '/users', permission: 'users.read', icon: 'person' },
    { label: 'Rapports', route: '/reports', permission: 'reports.view', icon: 'assessment' },
    { label: 'Administration', route: '/admin', permission: 'system.manage', icon: 'settings' }
  ];

  return allItems.filter(item =>
    this.permissionService.hasPermission(item.permission) ||
    this.permissionService.hasAnyPermission([item.permission + '.own', item.permission + '.team'])
  );
}
```

### Cas 3: Formulaire Conditionnel
```html
<form [formGroup]="clientForm">
  <!-- Champs toujours visibles -->
  <input formControlName="name" placeholder="Nom">

  <!-- Champ conditionnel selon permissions -->
  <div *appHasPermission="'clients.update'">
    <select formControlName="status">
      <option value="active">Actif</option>
      <option value="inactive">Inactif</option>
    </select>
  </div>

  <!-- Boutons conditionnels -->
  <button
    type="submit"
    *appHasPermission="['clients.create', 'clients.update']"
    [appHasPermissionRequireAll]="false">
    Sauvegarder
  </button>

  <button
    type="button"
    *appHasPermission="'clients.delete'"
    (click)="deleteClient()">
    Supprimer
  </button>
</form>
```

## 🚨 Bonnes Pratiques

### 1. **Sécurité Côté Serveur**
- ⚠️ **IMPORTANT**: Les permissions frontend sont pour l'UX seulement
- Toujours valider les permissions côté serveur
- Ne jamais faire confiance uniquement au frontend

### 2. **Performance**
- Charger les permissions une seule fois au login
- Mettre en cache les permissions utilisateur
- Utiliser des guards pour éviter les chargements inutiles

### 3. **UX/UI**
- Cacher plutôt que désactiver les éléments non autorisés
- Fournir des messages d'erreur clairs pour les accès refusés
- Adapter l'interface selon le niveau de permissions

### 4. **Maintenance**
- Centraliser la logique de permissions dans des services
- Documenter les permissions requises pour chaque fonctionnalité
- Tester régulièrement avec différents niveaux d'accès

### 5. **Évolutivité**
- Utiliser des constantes pour les noms de permissions
- Grouper les permissions logiquement
- Prévoir des permissions granulaires pour l'avenir

## 📞 Support & Documentation API

### Endpoints de Documentation
- **Swagger UI**: `http://localhost:8000/api/documentation`
- **Permissions API**: `GET /api/v1/permissions`
- **Rôles API**: `GET /api/v1/roles`

### Contact
- **Backend Team**: Pour questions sur les permissions serveur
- **Frontend Team**: Pour implémentation côté client

---

**Version**: 1.0
**Dernière mise à jour**: 2026-02-03
**Compatibilité**: TargetDesk CRM v1.0+