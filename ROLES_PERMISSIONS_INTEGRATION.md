# Documentation d'Intégration - Système de Rôles et Permissions

## Vue d'ensemble

Le système de rôles et permissions de TargetDesk CRM offre un contrôle d'accès granulaire avec 5 rôles prédéfinis, 49 permissions spécifiques et 3 niveaux de scope (global, team, own).

### Architecture du Système

```
Utilisateur → Rôles → Permissions → Actions
     ↓           ↓         ↓          ↓
   User     Role(s)   Permission  Resource
```

## Rôles Prédéfinis

| Rôle | Description | Niveau d'Accès |
|------|-------------|----------------|
| `super_admin` | Accès complet au système | Toutes permissions |
| `admin` | Administration utilisateurs et rôles | Gestion + lecture globale |
| `manager` | Gestion d'équipe et supervision | Team + own |
| `commercial` | Utilisateur commercial standard | Own seulement |
| `consultant` | Accès en lecture seule | Read own seulement |

## Modules et Permissions

### Permissions par Module

| Module | Actions | Scopes Disponibles |
|--------|---------|-------------------|
| **users** | create, read, update, delete | global, own |
| **roles** | create, read, update, delete, assign | global |
| **permissions** | read, check | global |
| **clients** | create, read, update, delete, export | global, team, own |
| **contacts** | create, read, update, delete | global, own |
| **documents** | create, read, update, delete | global, own |
| **dashboard** | view (commercial/personal) | global, own |
| **reports** | generate, view | global, own |
| **team** | view.enable, view.logs | team, global |
| **access** | rules.* | global |

---

## API Endpoints

### Base URL
```
http://your-domain.com/api/v1
```

### Authentication
Tous les endpoints nécessitent un token Bearer :
```
Authorization: Bearer {your-token}
```

### 1. Gestion des Rôles

#### Lister tous les rôles
```http
GET /roles
```

**Paramètres de requête :**
- `include_permissions` (boolean) : Inclure les permissions
- `filter` (string) : `predefined`, `custom`, `all`

**Réponse :**
```json
{
  "success": true,
  "message": "Roles retrieved successfully",
  "data": {
    "roles": [
      {
        "id": 1,
        "name": "super_admin",
        "display_name": "Super Admin",
        "description": "Full system access",
        "is_predefined": true,
        "is_active": true,
        "permissions": [...] // si include_permissions=true
      }
    ],
    "total": 5
  }
}
```

#### Créer un rôle personnalisé
```http
POST /roles
```

**Body :**
```json
{
  "name": "regional_manager",
  "display_name": "Gestionnaire Régional",
  "description": "Gestionnaire avec accès régional",
  "permissions": [1, 2, 3, 15, 22]
}
```

#### Obtenir les détails d'un rôle
```http
GET /roles/{id}
```

#### Modifier un rôle
```http
PUT /roles/{id}
```

#### Supprimer un rôle
```http
DELETE /roles/{id}
```

#### Gérer les permissions d'un rôle
```http
GET /roles/{id}/permissions
POST /roles/{id}/permissions
DELETE /roles/{id}/permissions/{permission_id}
```

### 2. Gestion des Permissions

#### Lister toutes les permissions
```http
GET /permissions
```

**Paramètres :**
- `module` (string) : Filtrer par module
- `action` (string) : Filtrer par action
- `scope` (string) : Filtrer par scope
- `grouped` (boolean) : Grouper par module

#### Obtenir les modules du système
```http
GET /permissions/modules
```

#### Vérifier une permission
```http
POST /permissions/check
```

**Body :**
```json
{
  "action": "read",
  "resource": "clients",
  "scope": "own",
  "resource_id": 123
}
```

**Réponse :**
```json
{
  "success": true,
  "message": "Permission checked successfully",
  "data": {
    "allowed": true,
    "context": {
      "user_id": 1,
      "user_roles": ["manager"],
      "permission_checked": "clients.read.own",
      "resource_id": 123,
      "timestamp": "2026-01-30T10:00:00.000Z"
    }
  }
}
```

#### Vérification en lot
```http
POST /permissions/bulk-check
```

#### Mes permissions
```http
GET /users/me/permissions?grouped=true
```

### 3. Attribution de Rôles aux Utilisateurs

#### Rôles d'un utilisateur
```http
GET /users/{id}/roles
```

#### Assigner un rôle
```http
POST /users/{id}/roles
```

**Body :**
```json
{
  "role_id": 2,
  "effective_from": "2026-02-01T00:00:00Z",
  "effective_until": "2026-12-31T23:59:59Z"
}
```

#### Assigner plusieurs rôles
```http
POST /users/{id}/roles/bulk-assign
```

#### Modifier une assignation
```http
PUT /users/{id}/roles/{role_id}
```

#### Retirer un rôle
```http
DELETE /users/{id}/roles/{role_id}
```

---

## Intégration Angular

### 1. Service de Rôles et Permissions

Créez un service Angular pour gérer les rôles et permissions :

```typescript
// src/app/services/auth-permissions.service.ts
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, BehaviorSubject } from 'rxjs';
import { map, tap } from 'rxjs/operators';

export interface Permission {
  id: number;
  name: string;
  display_name: string;
  module: string;
  action: string;
  scope: string;
}

export interface Role {
  id: number;
  name: string;
  display_name: string;
  description: string;
  is_predefined: boolean;
  permissions?: Permission[];
}

export interface User {
  id: number;
  name: string;
  email: string;
  roles: Role[];
}

@Injectable({
  providedIn: 'root'
})
export class AuthPermissionsService {
  private apiUrl = 'http://your-domain.com/api/v1';
  private userSubject = new BehaviorSubject<User | null>(null);
  private permissionsSubject = new BehaviorSubject<Permission[]>([]);
  private rolesSubject = new BehaviorSubject<Role[]>([]);

  public user$ = this.userSubject.asObservable();
  public permissions$ = this.permissionsSubject.asObservable();
  public roles$ = this.rolesSubject.asObservable();

  constructor(private http: HttpClient) {}

  private getHeaders(): HttpHeaders {
    const token = localStorage.getItem('auth_token');
    return new HttpHeaders({
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    });
  }

  // Charger les permissions de l'utilisateur connecté
  loadUserPermissions(): Observable<Permission[]> {
    return this.http.get<any>(`${this.apiUrl}/users/me/permissions?grouped=true`, {
      headers: this.getHeaders()
    }).pipe(
      map(response => response.data.permissions),
      tap(permissions => {
        // Aplatir les permissions groupées
        const flatPermissions = Object.values(permissions).flat() as Permission[];
        this.permissionsSubject.next(flatPermissions);
      })
    );
  }

  // Charger tous les rôles
  loadRoles(): Observable<Role[]> {
    return this.http.get<any>(`${this.apiUrl}/roles?include_permissions=true`, {
      headers: this.getHeaders()
    }).pipe(
      map(response => response.data.roles),
      tap(roles => this.rolesSubject.next(roles))
    );
  }

  // Vérifier une permission spécifique
  checkPermission(action: string, resource: string, scope: string = 'global', resourceId?: number): Observable<boolean> {
    return this.http.post<any>(`${this.apiUrl}/permissions/check`, {
      action,
      resource,
      scope,
      resource_id: resourceId
    }, { headers: this.getHeaders() }).pipe(
      map(response => response.data.allowed)
    );
  }

  // Vérifier plusieurs permissions
  checkPermissions(permissions: {action: string, resource: string, scope?: string}[]): Observable<any[]> {
    return this.http.post<any>(`${this.apiUrl}/permissions/bulk-check`, {
      permissions
    }, { headers: this.getHeaders() }).pipe(
      map(response => response.data.results)
    );
  }

  // Vérifier si l'utilisateur a un rôle spécifique
  hasRole(roleName: string): boolean {
    const user = this.userSubject.value;
    return user?.roles?.some(role => role.name === roleName) || false;
  }

  // Vérifier si l'utilisateur a au moins un des rôles
  hasAnyRole(roleNames: string[]): boolean {
    const user = this.userSubject.value;
    return user?.roles?.some(role => roleNames.includes(role.name)) || false;
  }

  // Vérifier une permission côté client (cache)
  hasPermission(permissionName: string): boolean {
    const permissions = this.permissionsSubject.value;
    return permissions.some(p => p.name === permissionName);
  }

  // Obtenir les permissions par module
  getPermissionsByModule(moduleName: string): Permission[] {
    const permissions = this.permissionsSubject.value;
    return permissions.filter(p => p.module === moduleName);
  }

  // Gestion des rôles utilisateur
  assignUserRole(userId: number, roleId: number, effectiveFrom?: string, effectiveUntil?: string): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/users/${userId}/roles`, {
      role_id: roleId,
      effective_from: effectiveFrom,
      effective_until: effectiveUntil
    }, { headers: this.getHeaders() });
  }

  removeUserRole(userId: number, roleId: number): Observable<any> {
    return this.http.delete<any>(`${this.apiUrl}/users/${userId}/roles/${roleId}`, {
      headers: this.getHeaders()
    });
  }

  getUserRoles(userId: number): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/users/${userId}/roles`, {
      headers: this.getHeaders()
    });
  }

  // Gestion des rôles
  createRole(roleData: {name: string, display_name: string, description?: string, permissions?: number[]}): Observable<Role> {
    return this.http.post<any>(`${this.apiUrl}/roles`, roleData, {
      headers: this.getHeaders()
    }).pipe(map(response => response.data.role));
  }

  updateRole(roleId: number, roleData: any): Observable<Role> {
    return this.http.put<any>(`${this.apiUrl}/roles/${roleId}`, roleData, {
      headers: this.getHeaders()
    }).pipe(map(response => response.data.role));
  }

  deleteRole(roleId: number): Observable<any> {
    return this.http.delete<any>(`${this.apiUrl}/roles/${roleId}`, {
      headers: this.getHeaders()
    });
  }
}
```

### 2. Guard pour protéger les routes

```typescript
// src/app/guards/permission.guard.ts
import { Injectable } from '@angular/core';
import { CanActivate, ActivatedRouteSnapshot, RouterStateSnapshot, Router } from '@angular/router';
import { Observable, of } from 'rxjs';
import { map, catchError } from 'rxjs/operators';
import { AuthPermissionsService } from '../services/auth-permissions.service';

@Injectable({
  providedIn: 'root'
})
export class PermissionGuard implements CanActivate {

  constructor(
    private authPermissions: AuthPermissionsService,
    private router: Router
  ) {}

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<boolean> {

    const requiredPermission = route.data['permission'];
    const requiredRoles = route.data['roles'];

    // Vérification des rôles
    if (requiredRoles) {
      if (Array.isArray(requiredRoles)) {
        if (!this.authPermissions.hasAnyRole(requiredRoles)) {
          this.router.navigate(['/unauthorized']);
          return of(false);
        }
      } else {
        if (!this.authPermissions.hasRole(requiredRoles)) {
          this.router.navigate(['/unauthorized']);
          return of(false);
        }
      }
    }

    // Vérification des permissions
    if (requiredPermission) {
      const { action, resource, scope = 'global' } = requiredPermission;

      return this.authPermissions.checkPermission(action, resource, scope).pipe(
        map(hasPermission => {
          if (!hasPermission) {
            this.router.navigate(['/unauthorized']);
            return false;
          }
          return true;
        }),
        catchError(() => {
          this.router.navigate(['/unauthorized']);
          return of(false);
        })
      );
    }

    return of(true);
  }
}
```

### 3. Directive pour masquer/afficher les éléments

```typescript
// src/app/directives/has-permission.directive.ts
import { Directive, Input, TemplateRef, ViewContainerRef, OnInit } from '@angular/core';
import { AuthPermissionsService } from '../services/auth-permissions.service';

@Directive({
  selector: '[appHasPermission]'
})
export class HasPermissionDirective implements OnInit {
  private permission: string = '';
  private roles: string[] = [];

  constructor(
    private templateRef: TemplateRef<any>,
    private viewContainer: ViewContainerRef,
    private authPermissions: AuthPermissionsService
  ) {}

  @Input() set appHasPermission(permission: string) {
    this.permission = permission;
    this.updateView();
  }

  @Input() set appHasPermissionRoles(roles: string | string[]) {
    this.roles = Array.isArray(roles) ? roles : [roles];
    this.updateView();
  }

  ngOnInit() {
    this.updateView();
  }

  private updateView() {
    let hasAccess = true;

    // Vérifier les rôles
    if (this.roles.length > 0) {
      hasAccess = this.authPermissions.hasAnyRole(this.roles);
    }

    // Vérifier les permissions
    if (hasAccess && this.permission) {
      hasAccess = this.authPermissions.hasPermission(this.permission);
    }

    if (hasAccess) {
      this.viewContainer.createEmbeddedView(this.templateRef);
    } else {
      this.viewContainer.clear();
    }
  }
}
```

### 4. Configuration des routes

```typescript
// src/app/app-routing.module.ts
import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { PermissionGuard } from './guards/permission.guard';

const routes: Routes = [
  {
    path: 'dashboard',
    loadChildren: () => import('./dashboard/dashboard.module').then(m => m.DashboardModule),
    canActivate: [PermissionGuard],
    data: {
      permission: { action: 'view', resource: 'dashboard', scope: 'own' }
    }
  },
  {
    path: 'admin',
    loadChildren: () => import('./admin/admin.module').then(m => m.AdminModule),
    canActivate: [PermissionGuard],
    data: {
      roles: ['admin', 'super_admin']
    }
  },
  {
    path: 'clients',
    loadChildren: () => import('./clients/clients.module').then(m => m.ClientsModule),
    canActivate: [PermissionGuard],
    data: {
      permission: { action: 'read', resource: 'clients', scope: 'own' }
    }
  },
  {
    path: 'users/management',
    component: UserManagementComponent,
    canActivate: [PermissionGuard],
    data: {
      permission: { action: 'read', resource: 'users' }
    }
  }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
```

### 5. Utilisation dans les templates

```html
<!-- Masquer/afficher selon les permissions -->
<button
  *appHasPermission="'clients.create'"
  (click)="createClient()"
  class="btn btn-primary">
  Créer Client
</button>

<!-- Masquer/afficher selon les rôles -->
<div *appHasPermissionRoles="['admin', 'manager']">
  <h3>Section Administration</h3>
  <!-- Contenu admin -->
</div>

<!-- Combinaison permission + rôles -->
<nav *appHasPermission="'clients.read.team'" appHasPermissionRoles="manager">
  <a routerLink="/team-clients">Clients Équipe</a>
</nav>

<!-- Gestion conditionnelle -->
<div class="user-actions">
  <button
    *appHasPermission="'users.update'"
    (click)="editUser(user)"
    class="btn btn-secondary">
    Modifier
  </button>

  <button
    *appHasPermission="'users.delete'"
    (click)="deleteUser(user)"
    class="btn btn-danger">
    Supprimer
  </button>
</div>
```

### 6. Composant de gestion des rôles

```typescript
// src/app/components/role-management.component.ts
import { Component, OnInit } from '@angular/core';
import { AuthPermissionsService, Role, Permission } from '../services/auth-permissions.service';

@Component({
  selector: 'app-role-management',
  template: `
    <div class="role-management">
      <h2>Gestion des Rôles</h2>

      <!-- Liste des rôles -->
      <div class="roles-list">
        <div *ngFor="let role of roles" class="role-card">
          <h3>{{ role.display_name }}</h3>
          <p>{{ role.description }}</p>
          <span class="badge" [class.predefined]="role.is_predefined">
            {{ role.is_predefined ? 'Prédéfini' : 'Personnalisé' }}
          </span>

          <div class="role-actions">
            <button *appHasPermission="'roles.update'"
                    (click)="editRole(role)">
              Modifier
            </button>
            <button *appHasPermission="'roles.delete'"
                    [disabled]="role.is_predefined"
                    (click)="deleteRole(role)">
              Supprimer
            </button>
          </div>
        </div>
      </div>

      <!-- Formulaire de création -->
      <div *appHasPermission="'roles.create'" class="create-role">
        <h3>Créer un Rôle</h3>
        <form (ngSubmit)="createRole()">
          <input [(ngModel)]="newRole.name" placeholder="Nom du rôle" required>
          <input [(ngModel)]="newRole.display_name" placeholder="Nom d'affichage" required>
          <textarea [(ngModel)]="newRole.description" placeholder="Description"></textarea>

          <!-- Sélection des permissions -->
          <div class="permissions-selection">
            <h4>Permissions</h4>
            <div *ngFor="let module of permissionModules" class="module-permissions">
              <h5>{{ module }}</h5>
              <div *ngFor="let permission of getModulePermissions(module)"
                   class="permission-checkbox">
                <label>
                  <input type="checkbox"
                         [value]="permission.id"
                         (change)="onPermissionChange($event, permission)">
                  {{ permission.display_name }} ({{ permission.scope }})
                </label>
              </div>
            </div>
          </div>

          <button type="submit">Créer Rôle</button>
        </form>
      </div>
    </div>
  `
})
export class RoleManagementComponent implements OnInit {
  roles: Role[] = [];
  permissions: Permission[] = [];
  permissionModules: string[] = [];

  newRole = {
    name: '',
    display_name: '',
    description: '',
    permissions: [] as number[]
  };

  constructor(private authPermissions: AuthPermissionsService) {}

  ngOnInit() {
    this.loadRoles();
    this.loadPermissions();
  }

  loadRoles() {
    this.authPermissions.loadRoles().subscribe(roles => {
      this.roles = roles;
    });
  }

  loadPermissions() {
    this.authPermissions.loadUserPermissions().subscribe(permissions => {
      this.permissions = permissions;
      this.permissionModules = [...new Set(permissions.map(p => p.module))];
    });
  }

  getModulePermissions(module: string): Permission[] {
    return this.permissions.filter(p => p.module === module);
  }

  onPermissionChange(event: any, permission: Permission) {
    if (event.target.checked) {
      this.newRole.permissions.push(permission.id);
    } else {
      const index = this.newRole.permissions.indexOf(permission.id);
      if (index > -1) {
        this.newRole.permissions.splice(index, 1);
      }
    }
  }

  createRole() {
    this.authPermissions.createRole(this.newRole).subscribe(
      role => {
        this.roles.push(role);
        this.resetForm();
        alert('Rôle créé avec succès');
      },
      error => {
        alert('Erreur lors de la création du rôle');
      }
    );
  }

  editRole(role: Role) {
    // Implémentation de l'édition
  }

  deleteRole(role: Role) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce rôle ?')) {
      this.authPermissions.deleteRole(role.id).subscribe(() => {
        this.roles = this.roles.filter(r => r.id !== role.id);
        alert('Rôle supprimé avec succès');
      });
    }
  }

  private resetForm() {
    this.newRole = {
      name: '',
      display_name: '',
      description: '',
      permissions: []
    };
  }
}
```

### 7. Initialisation dans l'app

```typescript
// src/app/app.component.ts
import { Component, OnInit } from '@angular/core';
import { AuthPermissionsService } from './services/auth-permissions.service';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html'
})
export class AppComponent implements OnInit {

  constructor(private authPermissions: AuthPermissionsService) {}

  ngOnInit() {
    // Charger les permissions au démarrage de l'app
    this.authPermissions.loadUserPermissions().subscribe();
  }
}
```

### 8. Module principal

```typescript
// src/app/app.module.ts
import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { HttpClientModule } from '@angular/common/http';
import { FormsModule } from '@angular/forms';

import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { AuthPermissionsService } from './services/auth-permissions.service';
import { PermissionGuard } from './guards/permission.guard';
import { HasPermissionDirective } from './directives/has-permission.directive';
import { RoleManagementComponent } from './components/role-management.component';

@NgModule({
  declarations: [
    AppComponent,
    HasPermissionDirective,
    RoleManagementComponent
  ],
  imports: [
    BrowserModule,
    AppRoutingModule,
    HttpClientModule,
    FormsModule
  ],
  providers: [
    AuthPermissionsService,
    PermissionGuard
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
```

## Exemples d'Utilisation Avancée

### 1. Gestion contextuelle des permissions

```typescript
// Vérifier l'accès à un client spécifique
checkClientAccess(clientId: number): Observable<boolean> {
  return this.authPermissions.checkPermission('read', 'clients', 'own', clientId);
}

// Dans le composant
canEditClient(client: any): Observable<boolean> {
  return this.authPermissions.checkPermission('update', 'clients', 'own', client.id);
}
```

### 2. Interface adaptative selon les rôles

```html
<!-- Menu adaptatif -->
<nav class="main-nav">
  <a routerLink="/dashboard" *appHasPermission="'dashboard.personal'">
    Tableau de Bord
  </a>

  <a routerLink="/commercial-dashboard" *appHasPermissionRoles="['manager', 'admin']">
    Dashboard Commercial
  </a>

  <div *appHasPermissionRoles="'admin'" class="admin-menu">
    <a routerLink="/users">Utilisateurs</a>
    <a routerLink="/roles">Rôles</a>
    <a routerLink="/permissions">Permissions</a>
  </div>
</nav>
```

### 3. Formulaires conditionnels

```html
<form [formGroup]="clientForm">
  <!-- Champs de base -->
  <input formControlName="name" placeholder="Nom">

  <!-- Champs admin seulement -->
  <div *appHasPermissionRoles="['admin', 'manager']">
    <select formControlName="assigned_to">
      <option *ngFor="let user of users" [value]="user.id">
        {{ user.name }}
      </option>
    </select>
  </div>

  <!-- Actions selon permissions -->
  <div class="form-actions">
    <button type="submit"
            *appHasPermission="'clients.update.own'"
            [disabled]="!canSave">
      Sauvegarder
    </button>

    <button type="button"
            *appHasPermission="'clients.delete'"
            (click)="deleteClient()"
            class="btn-danger">
      Supprimer
    </button>
  </div>
</form>
```

## Notes d'Implémentation

### Sécurité
- ⚠️ **Important** : Les vérifications côté client sont pour l'UX uniquement
- Toujours valider les permissions côté serveur
- Les middlewares Laravel protègent les endpoints
- Ne jamais se fier uniquement aux guards Angular

### Performance
- Mettre en cache les permissions après connexion
- Utiliser les BehaviorSubjects pour la réactivité
- Éviter les appels API répétés pour les mêmes vérifications

### Gestion d'erreurs
```typescript
// Gestion des erreurs d'autorisation
this.authPermissions.checkPermission('delete', 'clients').subscribe(
  allowed => {
    if (allowed) {
      this.deleteClient();
    } else {
      this.showAccessDenied();
    }
  },
  error => {
    if (error.status === 403) {
      this.showAccessDenied();
    } else {
      this.showError('Erreur de connexion');
    }
  }
);
```

### Debugging
```typescript
// Utilitaires de debug
debugUserPermissions(): void {
  console.log('User roles:', this.authPermissions.hasAnyRole(['admin']));
  console.log('Permissions:', this.authPermissions.permissions$.value);
}
```

---

Cette documentation fournit une intégration complète du système de rôles et permissions dans Angular. Le système est extensible et peut être adapté selon les besoins spécifiques de votre application.