# Documentation API - Gestion des Utilisateurs

## Vue d'ensemble

Cette documentation présente les endpoints API pour la gestion des utilisateurs dans TargetDesk CRM. Tous les endpoints nécessitent une authentification via token Bearer.

## Base URL
```
http://localhost:8000/api/v1
```

## Authentification
```http
Authorization: Bearer YOUR_TOKEN_HERE
```

## Endpoints Disponibles

### 1. CRUD de base

#### GET /users - Récupérer tous les utilisateurs
```http
GET /api/v1/users
```

**Paramètres de requête optionnels :**
- `page` (integer) - Numéro de page (défaut: 1)
- `per_page` (integer) - Éléments par page (défaut: 15)
- `role` (string) - Filtrer par nom de rôle
- `status` (string) - Filtrer par statut (active/inactive)
- `q` (string) - Recherche dans nom, prénom, email

**Réponse :**
```json
{
  "success": true,
  "message": "Utilisateurs récupérés avec succès",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Jean Martin",
        "first_name": "Jean",
        "last_name": "Martin",
        "email": "jean@example.com",
        "status": "active",
        "phone": "+33123456789",
        "department": "Commercial",
        "last_login": "2026-01-30T10:30:00Z",
        "created_at": "2026-01-30T09:00:00Z",
        "roles": [...]
      }
    ],
    "total": 10,
    "per_page": 15,
    ...
  }
}
```

#### GET /users/{id} - Récupérer un utilisateur par ID
```http
GET /api/v1/users/1
```

**Réponse :**
```json
{
  "success": true,
  "message": "Utilisateur récupéré avec succès",
  "data": {
    "id": 1,
    "name": "Jean Martin",
    "first_name": "Jean",
    "last_name": "Martin",
    "email": "jean@example.com",
    "status": "active",
    "phone": "+33123456789",
    "department": "Commercial",
    "last_login": "2026-01-30T10:30:00Z",
    "created_at": "2026-01-30T09:00:00Z",
    "roles": [...]
  }
}
```

#### POST /users - Créer un nouvel utilisateur
```http
POST /api/v1/users
Content-Type: application/json
```

**Corps de la requête :**
```json
{
  "email": "newuser@example.com",
  "first_name": "Marie",
  "last_name": "Dupont",
  "password": "password123",
  "password_confirmation": "password123",
  "role_id": 14,
  "status": "active",
  "phone": "+33987654321",
  "department": "Marketing"
}
```

**Validation :**
- `email` : requis, email valide, unique
- `first_name` : requis, string max 255
- `last_name` : requis, string max 255
- `password` : requis, min 8 caractères, confirmé
- `role_id` : requis, doit exister en base
- `status` : optionnel, "active" ou "inactive"
- `phone` : optionnel, string max 20
- `department` : optionnel, string max 100

#### PUT /users/{id} - Mettre à jour un utilisateur
```http
PUT /api/v1/users/1
Content-Type: application/json
```

**Corps de la requête :**
```json
{
  "first_name": "Marie",
  "last_name": "Dupont",
  "role_id": 13,
  "status": "active",
  "phone": "+33987654321",
  "department": "Marketing"
}
```

#### DELETE /users/{id} - Supprimer un utilisateur
```http
DELETE /api/v1/users/1
```

**Note :** Un utilisateur ne peut pas supprimer son propre compte.

### 2. Gestion du statut

#### PATCH /users/{id}/toggle-status - Activer/Désactiver un utilisateur
```http
PATCH /api/v1/users/1/toggle-status
```

**Réponse :**
```json
{
  "success": true,
  "message": "Utilisateur désactivé avec succès",
  "data": {
    "id": 1,
    "status": "inactive",
    ...
  }
}
```

### 3. Gestion des mots de passe

#### POST /users/{id}/reset-password - Réinitialiser le mot de passe
```http
POST /api/v1/users/1/reset-password
```

**Réponse :**
```json
{
  "success": true,
  "message": "Mot de passe réinitialisé avec succès",
  "data": {
    "new_password": "abc12345"
  }
}
```

### 4. Recherche et filtrage

#### GET /users/search - Rechercher des utilisateurs
```http
GET /api/v1/users/search?q=Jean
```

**Paramètres :**
- `q` : terme de recherche (minimum 2 caractères)

**Réponse :** Liste limitée à 10 résultats

#### Filtrage par rôle
```http
GET /api/v1/users?role=commercial
```

#### Filtrage par statut
```http
GET /api/v1/users?status=active
```

### 5. Validation

#### GET /users/validate-email - Vérifier l'unicité de l'email
```http
GET /api/v1/users/validate-email?email=test@example.com&exclude=1
```

**Paramètres :**
- `email` : email à vérifier
- `exclude` : ID utilisateur à exclure (pour la modification)

**Réponse :**
```json
{
  "success": true,
  "message": "Email disponible",
  "data": {
    "email": "test@example.com",
    "is_unique": true,
    "is_available": true
  }
}
```

### 6. Statistiques

#### GET /users/stats - Statistiques des utilisateurs
```http
GET /api/v1/users/stats
```

**Réponse :**
```json
{
  "success": true,
  "message": "Statistiques récupérées avec succès",
  "data": {
    "total_users": 25,
    "active_users": 23,
    "inactive_users": 2,
    "users_by_role": {
      "super_admin": 2,
      "admin": 3,
      "manager": 5,
      "commercial": 12,
      "consultant": 3
    },
    "recent_registrations": 8
  }
}
```

## Types de données

### CreateUserRequest
```typescript
interface CreateUserRequest {
  email: string;
  first_name: string;
  last_name: string;
  password: string;
  password_confirmation: string;
  role_id: number;
  status?: 'active' | 'inactive';
  phone?: string;
  department?: string;
}
```

### UpdateUserRequest
```typescript
interface UpdateUserRequest {
  first_name?: string;
  last_name?: string;
  email?: string;
  role_id?: number;
  status?: 'active' | 'inactive';
  phone?: string;
  department?: string;
}
```

### User Response
```typescript
interface User {
  id: number;
  name: string;
  first_name: string;
  last_name: string;
  email: string;
  status: 'active' | 'inactive';
  phone?: string;
  department?: string;
  last_login?: string;
  created_at: string;
  updated_at: string;
  roles: Role[];
}
```

## Codes d'erreur

| Code | Description |
|------|-------------|
| 200  | Succès |
| 201  | Créé avec succès |
| 400  | Requête malformée |
| 401  | Non authentifié |
| 403  | Non autorisé |
| 404  | Utilisateur non trouvé |
| 422  | Erreurs de validation |
| 500  | Erreur serveur |

## Exemples d'utilisation avec JavaScript

### Service Angular/TypeScript

```typescript
import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class UserService {
  private apiUrl = 'http://localhost:8000/api/v1/users';

  constructor(private http: HttpClient) {}

  // Récupérer tous les utilisateurs
  getUsers(filters?: {
    page?: number;
    per_page?: number;
    role?: string;
    status?: string;
    q?: string;
  }): Observable<any> {
    let params = new HttpParams();
    if (filters) {
      Object.keys(filters).forEach(key => {
        if (filters[key]) {
          params = params.set(key, filters[key]);
        }
      });
    }
    return this.http.get(this.apiUrl, { params });
  }

  // Récupérer un utilisateur par ID
  getUser(id: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/${id}`);
  }

  // Créer un utilisateur
  createUser(userData: CreateUserRequest): Observable<any> {
    return this.http.post(this.apiUrl, userData);
  }

  // Mettre à jour un utilisateur
  updateUser(id: number, userData: UpdateUserRequest): Observable<any> {
    return this.http.put(`${this.apiUrl}/${id}`, userData);
  }

  // Supprimer un utilisateur
  deleteUser(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/${id}`);
  }

  // Toggle statut
  toggleStatus(id: number): Observable<any> {
    return this.http.patch(`${this.apiUrl}/${id}/toggle-status`, {});
  }

  // Réinitialiser mot de passe
  resetPassword(id: number): Observable<any> {
    return this.http.post(`${this.apiUrl}/${id}/reset-password`, {});
  }

  // Rechercher
  searchUsers(query: string): Observable<any> {
    return this.http.get(`${this.apiUrl}/search`, {
      params: { q: query }
    });
  }

  // Valider email
  validateEmail(email: string, excludeId?: number): Observable<any> {
    let params = new HttpParams().set('email', email);
    if (excludeId) {
      params = params.set('exclude', excludeId.toString());
    }
    return this.http.get(`${this.apiUrl}/validate-email`, { params });
  }

  // Statistiques
  getStats(): Observable<any> {
    return this.http.get(`${this.apiUrl}/stats`);
  }
}
```

### Exemple d'utilisation dans un composant

```typescript
import { Component, OnInit } from '@angular/core';
import { UserService } from './user.service';

@Component({
  selector: 'app-user-list',
  templateUrl: './user-list.component.html'
})
export class UserListComponent implements OnInit {
  users: any[] = [];
  loading = false;
  stats: any = {};

  constructor(private userService: UserService) {}

  ngOnInit() {
    this.loadUsers();
    this.loadStats();
  }

  loadUsers(filters?: any) {
    this.loading = true;
    this.userService.getUsers(filters).subscribe({
      next: (response) => {
        this.users = response.data.data;
        this.loading = false;
      },
      error: (error) => {
        console.error('Erreur lors du chargement:', error);
        this.loading = false;
      }
    });
  }

  loadStats() {
    this.userService.getStats().subscribe({
      next: (response) => {
        this.stats = response.data;
      }
    });
  }

  toggleUserStatus(user: any) {
    this.userService.toggleStatus(user.id).subscribe({
      next: (response) => {
        // Mettre à jour l'utilisateur local
        const index = this.users.findIndex(u => u.id === user.id);
        if (index !== -1) {
          this.users[index] = response.data;
        }
      },
      error: (error) => {
        console.error('Erreur lors du toggle:', error);
      }
    });
  }

  searchUsers(query: string) {
    if (query.length >= 2) {
      this.userService.searchUsers(query).subscribe({
        next: (response) => {
          this.users = response.data;
        }
      });
    } else {
      this.loadUsers();
    }
  }
}
```

## Notes importantes

1. **Sécurité :** Tous les endpoints nécessitent une authentification
2. **Pagination :** Les listes sont paginées par défaut (15 éléments/page)
3. **Auto-protection :** Un utilisateur ne peut pas se supprimer ou se désactiver
4. **Logs :** Toutes les actions sont loggées pour audit
5. **Validation :** Validation stricte des données côté serveur
6. **Rôles :** La gestion des rôles utilise le système RBAC intégré