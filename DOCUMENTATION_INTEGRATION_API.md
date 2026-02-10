# 📋 DOCUMENTATION COMPLÈTE D'INTÉGRATION API
## SYSTÈME DE GESTION DES TÂCHES ET PROJETS

**Version :** 1.0
**Date :** 2026-02-10
**Statut :** Production Ready

---

## 🎯 VUE D'ENSEMBLE

Ce document fournit la documentation complète d'intégration pour l'API de gestion des tâches et projets TargetDesk. Il couvre tous les endpoints, modèles de données, permissions, et exemples d'intégration.

### Architecture Générale
- **Backend :** Laravel 8.x avec API REST
- **Authentication :** Sanctum Token-based
- **Format de données :** JSON
- **Base URL :** `https://your-domain.com/api/v1/`
- **Rate Limiting :** 60 requêtes/minute par utilisateur

---

## 🔐 AUTHENTIFICATION ET AUTORISATION

### Système d'Authentification
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### Hiérarchie des Rôles et Permissions

#### 1. **ADMIN (Administrateur Système)**
- **Permissions :** Accès complet à tous les endpoints
- **Peut :** CRUD sur tous les projets, tâches, utilisateurs, notifications
- **Restrictions :** Aucune

#### 2. **MANAGER (Chef de Projet)**
- **Permissions :** Gestion complète des projets assignés
- **Peut :** CRUD sur ses projets et toutes leurs tâches, assigner utilisateurs, voir tous les temps
- **Restrictions :** Limité aux projets dont il est propriétaire

#### 3. **USER (Membre d'équipe)**
- **Permissions :** Gestion de ses tâches assignées
- **Peut :** Voir/modifier ses tâches, saisir temps, commenter, joindre fichiers
- **Restrictions :** Accès limité aux tâches assignées

#### 4. **CLIENT (Client externe)**
- **Permissions :** Vue en lecture seule
- **Peut :** Consulter projets/tâches autorisés, commenter
- **Restrictions :** Pas de modification, pas d'accès aux données temps

---

## 📊 MODÈLES DE DONNÉES

### Format Standard de Réponse API

#### Réponse de Succès
```json
{
  "success": true,
  "data": {}, // ou [] pour collections
  "message": "Action réalisée avec succès",
  "meta": { // Pour pagination
    "current_page": 1,
    "total": 100,
    "per_page": 20,
    "last_page": 5
  }
}
```

#### Réponse d'Erreur
```json
{
  "success": false,
  "message": "Description de l'erreur",
  "errors": {
    "field_name": ["Message d'erreur spécifique"]
  },
  "code": "ERROR_CODE"
}
```

### Modèles de Données Principaux

#### Project (Projet)
```json
{
  "id": 1,
  "code": "PRJ-2026-0001",
  "name": "Refonte Site Web",
  "description": "Refonte complète du site vitrine",
  "status": "en_cours",
  "priority": "haute",
  "start_date": "2026-01-15",
  "end_date": "2026-03-15",
  "budget": 15000.00,
  "progress_percentage": 65.5,
  "client_id": 12,
  "manager_id": 5,
  "created_by": 1,
  "created_at": "2026-01-10T10:00:00.000000Z",
  "updated_at": "2026-02-10T15:30:00.000000Z",
  "client": {
    "id": 12,
    "name": "Entreprise ABC",
    "email": "contact@abc.com"
  },
  "manager": {
    "id": 5,
    "name": "Marie Dupont",
    "email": "marie.dupont@targetdesk.com"
  },
  "tasks_count": 24,
  "completed_tasks_count": 16,
  "total_estimated_hours": 120.5,
  "total_actual_hours": 89.25
}
```

#### Task (Tâche)
```json
{
  "id": 1,
  "code": "TSK-2026-0001",
  "project_id": 1,
  "parent_task_id": null,
  "title": "Design de la page d'accueil",
  "description": "Créer le mockup de la nouvelle page d'accueil",
  "status": "en_cours",
  "priority": "haute",
  "type": "design",
  "estimated_hours": 8.0,
  "actual_hours": 5.5,
  "progress_percentage": 68.75,
  "due_date": "2026-02-15T17:00:00.000000Z",
  "created_by": 5,
  "updated_by": 3,
  "created_at": "2026-02-01T09:00:00.000000Z",
  "updated_at": "2026-02-10T11:30:00.000000Z",
  "project": {
    "id": 1,
    "name": "Refonte Site Web",
    "code": "PRJ-2026-0001"
  },
  "creator": {
    "id": 5,
    "name": "Marie Dupont"
  },
  "assignees": [
    {
      "id": 3,
      "name": "Jean Martin",
      "email": "jean.martin@targetdesk.com",
      "pivot": {
        "assigned_at": "2026-02-01T09:15:00.000000Z",
        "assigned_by": 5
      }
    }
  ],
  "tags": [
    {
      "id": 1,
      "name": "UI/UX",
      "color": "#3b82f6"
    },
    {
      "id": 2,
      "name": "Priorité",
      "color": "#ef4444"
    }
  ],
  "comments_count": 3,
  "files_count": 2,
  "time_entries_count": 4,
  "difficulties_count": 0,
  "is_overdue": false,
  "days_until_due": 5
}
```

#### TaskTimeEntry (Saisie de Temps)
```json
{
  "id": 1,
  "task_id": 1,
  "user_id": 3,
  "start_time": "2026-02-10T09:00:00.000000Z",
  "end_time": "2026-02-10T11:30:00.000000Z",
  "hours": 2.5,
  "description": "Travail sur les mockups responsive",
  "is_paused": false,
  "created_at": "2026-02-10T09:00:00.000000Z",
  "updated_at": "2026-02-10T11:30:00.000000Z",
  "task": {
    "id": 1,
    "title": "Design de la page d'accueil",
    "code": "TSK-2026-0001"
  },
  "user": {
    "id": 3,
    "name": "Jean Martin"
  }
}
```

#### TaskComment (Commentaire)
```json
{
  "id": 1,
  "task_id": 1,
  "user_id": 3,
  "content": "J'ai terminé la première version du design. @marie.dupont pouvez-vous valider ?",
  "mentions": [5],
  "is_edited": false,
  "created_at": "2026-02-10T14:00:00.000000Z",
  "updated_at": "2026-02-10T14:00:00.000000Z",
  "user": {
    "id": 3,
    "name": "Jean Martin",
    "avatar": "https://ui-avatars.com/api/?name=Jean+Martin"
  },
  "mentioned_users": [
    {
      "id": 5,
      "name": "Marie Dupont"
    }
  ]
}
```

#### TaskFile (Fichier Joint)
```json
{
  "id": 1,
  "task_id": 1,
  "user_id": 3,
  "name": "mockup-homepage-v1.pdf",
  "file_name": "1707562800_aBc123XyZ.pdf",
  "file_path": "task-files/1707562800_aBc123XyZ.pdf",
  "file_size": 2048576,
  "mime_type": "application/pdf",
  "description": "Premier mockup de la page d'accueil",
  "created_at": "2026-02-10T15:00:00.000000Z",
  "user": {
    "id": 3,
    "name": "Jean Martin"
  },
  "download_url": "https://your-domain.com/api/v1/task-files/1/download",
  "file_size_human": "2.0 MB"
}
```

#### TaskDifficulty (Difficulté)
```json
{
  "id": 1,
  "task_id": 1,
  "user_id": 3,
  "type": "technical",
  "description": "Problème de compatibilité avec les anciens navigateurs",
  "severity": "medium",
  "proposed_solution": "Utiliser des polyfills CSS",
  "status": "open",
  "resolved_at": null,
  "resolved_by": null,
  "resolution": null,
  "created_at": "2026-02-10T16:00:00.000000Z",
  "user": {
    "id": 3,
    "name": "Jean Martin"
  },
  "resolver": null
}
```

#### TaskView (Vue Personnalisée)
```json
{
  "id": 1,
  "user_id": 3,
  "name": "Mes tâches urgentes",
  "filters": {
    "status": ["en_cours", "a_faire"],
    "priority": ["haute", "critique"],
    "assigned_to_me": true,
    "due_within_days": 7
  },
  "sort_by": "due_date",
  "sort_direction": "asc",
  "is_default": false,
  "created_at": "2026-02-10T10:00:00.000000Z",
  "updated_at": "2026-02-10T10:00:00.000000Z"
}
```

#### Notification
```json
{
  "id": "9af4b2d1-1234-5678-9abc-def123456789",
  "type": "task_assigned",
  "title": "Nouvelle tâche assignée",
  "message": "Vous avez été assigné à la tâche 'Design de la page d'accueil'",
  "data": {
    "task_id": 1,
    "task_title": "Design de la page d'accueil",
    "assigned_by": "Marie Dupont",
    "action_url": "/tasks/1"
  },
  "read_at": null,
  "created_at": "2026-02-10T09:15:00.000000Z"
}
```

---

## 🛠 ENDPOINTS API COMPLETS

### 1. GESTION DES PROJETS

#### 1.1 Lister les projets
```
GET /api/v1/projects
```

**Paramètres de requête :**
```
?page=1              // Page (défaut: 1)
&per_page=20         // Éléments par page (défaut: 20, max: 100)
&status=en_cours     // Filtrer par statut
&client_id=12        // Filtrer par client
&manager_id=5        // Filtrer par chef de projet
&search=refonte      // Recherche textuelle
&sort_by=created_at  // Tri (name, created_at, end_date, progress)
&sort_direction=desc // Direction tri (asc, desc)
&with_stats=true     // Inclure statistiques
```

**Permissions :** ADMIN, MANAGER (ses projets), USER (projets assignés)

**Réponse :**
```json
{
  "success": true,
  "data": [
    // Tableau d'objets Project
  ],
  "meta": {
    "current_page": 1,
    "total": 25,
    "per_page": 20,
    "last_page": 2
  }
}
```

#### 1.2 Créer un projet
```
POST /api/v1/projects
```

**Permissions :** ADMIN, MANAGER

**Corps de la requête :**
```json
{
  "name": "Nouveau Projet",
  "description": "Description du projet",
  "client_id": 12,
  "start_date": "2026-02-15",
  "end_date": "2026-05-15",
  "budget": 25000.00,
  "priority": "normale",
  "status": "planifie"
}
```

**Validation :**
- `name` : requis, string, max 255 caractères, unique
- `description` : optionnel, text
- `client_id` : requis, exists:clients,id
- `start_date` : requis, date, après aujourd'hui
- `end_date` : requis, date, après start_date
- `budget` : optionnel, numeric, min 0
- `priority` : optionnel, enum(basse,normale,haute,critique)
- `status` : optionnel, enum(planifie,en_cours,en_pause,termine,annule)

#### 1.3 Voir les détails d'un projet
```
GET /api/v1/projects/{id}
```

**Paramètres de requête :**
```
?include=tasks,team,client,timeline,stats
```

**Permissions :** ADMIN, MANAGER (propriétaire), USER (membre équipe), CLIENT (si autorisé)

#### 1.4 Mettre à jour un projet
```
PUT /api/v1/projects/{id}
```

**Permissions :** ADMIN, MANAGER (propriétaire)

**Corps de la requête (tous champs optionnels) :**
```json
{
  "name": "Projet Modifié",
  "description": "Description mise à jour",
  "client_id": 15,
  "start_date": "2026-03-01",
  "end_date": "2026-06-01",
  "budget": 30000.00,
  "priority": "haute",
  "status": "en_cours"
}
```

**Validation :** Identique à la création

#### 1.5 Supprimer un projet
```
DELETE /api/v1/projects/{id}
```

**Permissions :** ADMIN, MANAGER (propriétaire)

**Note :** Suppression en soft delete, les tâches sont conservées

#### 1.6 Équipe du projet
```
GET /api/v1/projects/{id}/team
POST /api/v1/projects/{id}/team
PUT /api/v1/projects/{id}/team/{userId}
DELETE /api/v1/projects/{id}/team/{userId}
```

**Permissions :** ADMIN, MANAGER (propriétaire)

#### 1.7 Timeline et statistiques
```
GET /api/v1/projects/{id}/timeline
GET /api/v1/projects/{id}/stats
GET /api/v1/projects/{id}/time-summary
GET /api/v1/projects/{id}/time-entries
GET /api/v1/projects/{id}/time-analytics
```

---

### 2. GESTION DES TÂCHES (CRUD)

#### 2.1 Lister les tâches
```
GET /api/v1/tasks
```

**Paramètres de requête :**
```
?page=1                    // Pagination
&per_page=20              // Éléments par page
&project_id=1             // Filtrer par projet
&status=en_cours          // Filtrer par statut
&priority=haute           // Filtrer par priorité
&assigned_to=3            // Filtrer par assigné
&type=design              // Filtrer par type
&tags=1,2,3               // Filtrer par tags (IDs)
&search=homepage          // Recherche textuelle
&due_date_from=2026-02-10 // Date d'échéance (début)
&due_date_to=2026-02-20   // Date d'échéance (fin)
&overdue=true             // Tâches en retard
&my_tasks=true            // Mes tâches uniquement
&sort_by=priority         // Tri
&sort_direction=desc      // Direction tri
&include=project,assignees,tags,comments_count
```

**Permissions :** Selon le rôle et les tâches accessibles

#### 2.2 Créer une tâche
```
POST /api/v1/projects/{projectId}/tasks
```

**Permissions :** ADMIN, MANAGER (propriétaire projet), USER (si membre équipe)

**Corps de la requête :**
```json
{
  "title": "Nouvelle tâche",
  "description": "Description détaillée",
  "parent_task_id": null,
  "priority": "normale",
  "type": "dev",
  "estimated_hours": 8.0,
  "due_date": "2026-02-20T17:00:00Z",
  "assigned_users": [3, 4],
  "tags": [1, 2],
  "status": "a_faire"
}
```

**Validation :**
- `title` : requis, string, max 255
- `description` : optionnel, text
- `parent_task_id` : optionnel, exists:tasks,id (même projet)
- `priority` : optionnel, enum(basse,normale,haute,critique)
- `type` : optionnel, enum(dev,design,test,analyse,autre)
- `estimated_hours` : optionnel, numeric, min 0.1, max 999
- `due_date` : optionnel, date, après aujourd'hui
- `assigned_users` : optionnel, array, exists:users,id
- `tags` : optionnel, array, exists:task_tags,id

#### 2.3 Voir les détails d'une tâche
```
GET /api/v1/tasks/{id}
```

**Paramètres :**
```
?include=project,assignees,tags,comments,files,time_entries,difficulties,history
```

#### 2.4 Mettre à jour une tâche
```
PUT /api/v1/tasks/{id}
```

**Permissions :** ADMIN, MANAGER (propriétaire projet), USER (assigné)

#### 2.5 Supprimer une tâche
```
DELETE /api/v1/tasks/{id}
```

**Permissions :** ADMIN, MANAGER (propriétaire projet)

---

### 3. GESTION DES STATUTS

#### 3.1 Lister les statuts disponibles
```
GET /api/v1/task-statuses
```

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "value": "a_faire",
      "label": "À faire",
      "color": "#6b7280",
      "description": "Tâche prête à être démarrée"
    },
    {
      "value": "en_cours",
      "label": "En cours",
      "color": "#3b82f6",
      "description": "Tâche en cours de réalisation"
    },
    {
      "value": "bloque",
      "label": "Bloqué",
      "color": "#f59e0b",
      "description": "Tâche bloquée par une difficulté"
    },
    {
      "value": "test",
      "label": "En test",
      "color": "#8b5cf6",
      "description": "Tâche en phase de test"
    },
    {
      "value": "termine",
      "label": "Terminé",
      "color": "#10b981",
      "description": "Tâche complètement terminée"
    }
  ]
}
```

#### 3.2 Changer le statut d'une tâche
```
PUT /api/v1/tasks/{id}/status
```

**Permissions :** ADMIN, MANAGER, USER (assigné)

**Corps :**
```json
{
  "status": "en_cours",
  "comment": "Démarrage des travaux de design"
}
```

**Règles métier :**
- Statut "bloque" nécessite un commentaire obligatoire
- Statut "termine" déclenche calcul automatique du pourcentage projet
- Changement vers "termine" nécessite confirmation si pas de temps saisi

---

### 4. GESTION DES ASSIGNATIONS

#### 4.1 Modifier les assignations
```
POST /api/v1/tasks/{id}/assign
```

**Permissions :** ADMIN, MANAGER (propriétaire projet)

**Corps :**
```json
{
  "user_ids": [3, 4, 5],
  "notify": true,
  "message": "Vous avez été assigné à cette tâche importante"
}
```

#### 4.2 Lister les membres assignables
```
GET /api/v1/projects/{projectId}/users
```

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": 3,
      "name": "Jean Martin",
      "email": "jean.martin@targetdesk.com",
      "role": "developer",
      "current_tasks_count": 5,
      "availability": "available"
    }
  ]
}
```

---

### 5. GESTION DES ÉTIQUETTES

#### 5.1 Lister les étiquettes
```
GET /api/v1/tags
```

**Paramètres :**
```
?search=ui&sort_by=name&include_usage=true
```

#### 5.2 Créer une étiquette
```
POST /api/v1/tags
```

**Corps :**
```json
{
  "name": "UI/UX",
  "color": "#3b82f6",
  "description": "Tâches liées à l'interface utilisateur"
}
```

#### 5.3 Supprimer une étiquette
```
DELETE /api/v1/tags/{id}
```

---

### 6. SYSTÈME DE COMMENTAIRES

#### 6.1 Lister les commentaires d'une tâche
```
GET /api/v1/tasks/{id}/comments
```

**Paramètres :**
```
?page=1&per_page=10&sort_direction=desc
```

#### 6.2 Ajouter un commentaire
```
POST /api/v1/tasks/{id}/comments
```

**Corps :**
```json
{
  "content": "Voici mon retour sur le design. @marie.dupont @jean.martin",
  "mentions": [5, 3],
  "notify_assignees": true
}
```

#### 6.3 Modifier un commentaire
```
PUT /api/v1/comments/{id}
```

**Permissions :** Auteur du commentaire (dans les 15 minutes) ou ADMIN

**Corps :**
```json
{
  "content": "Commentaire modifié"
}
```

#### 6.4 Supprimer un commentaire
```
DELETE /api/v1/comments/{id}
```

**Permissions :** Auteur du commentaire ou ADMIN ou MANAGER (propriétaire projet)

---

### 7. GESTION DES FICHIERS

#### 7.1 Lister les fichiers d'une tâche
```
GET /api/v1/tasks/{id}/files
```

#### 7.2 Joindre un fichier
```
POST /api/v1/tasks/{id}/files
```

**Content-Type :** `multipart/form-data`

**Paramètres :**
```
file: (fichier, max 10MB)
description: "Description du fichier"
```

**Types autorisés :** pdf, doc, docx, xls, xlsx, ppt, pptx, jpg, jpeg, png, gif, zip

#### 7.3 Télécharger un fichier
```
GET /api/v1/task-files/{id}/download
```

**Réponse :** Fichier en binaire avec headers appropriés

#### 7.4 Supprimer un fichier
```
DELETE /api/v1/task-files/{id}
```

**Permissions :** Propriétaire du fichier ou ADMIN ou MANAGER (propriétaire projet)

---

### 8. GESTION DES DIFFICULTÉS

#### 8.1 Lister les difficultés d'une tâche
```
GET /api/v1/tasks/{id}/difficulties
```

#### 8.2 Déclarer une difficulté
```
POST /api/v1/tasks/{id}/difficulties
```

**Corps :**
```json
{
  "type": "technical",
  "description": "Problème de performance sur les gros datasets",
  "severity": "high",
  "proposed_solution": "Implémenter la pagination côté serveur"
}
```

**Types :** `technical`, `resource`, `external`, `other`
**Gravité :** `low`, `medium`, `high`, `critical`

**Action automatique :** Change le statut de la tâche en "bloque"

#### 8.3 Résoudre une difficulté
```
PUT /api/v1/difficulties/{id}
```

**Corps :**
```json
{
  "status": "resolved",
  "resolution": "Pagination implémentée avec succès",
  "resolved_by": 5
}
```

**Action automatique :** Si toutes difficultés résolues, remet tâche en "en_cours"

---

### 9. HISTORIQUE ET AUDIT

#### 9.1 Historique des modifications d'une tâche
```
GET /api/v1/tasks/{id}/history
```

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "action": "status_changed",
      "field_name": "status",
      "old_value": "a_faire",
      "new_value": "en_cours",
      "user": {
        "id": 3,
        "name": "Jean Martin"
      },
      "created_at": "2026-02-10T09:30:00.000000Z"
    }
  ]
}
```

---

### 10. VUES PERSONNALISÉES

#### 10.1 Mes vues sauvegardées
```
GET /api/v1/users/{userId}/task-views
```

#### 10.2 Sauvegarder une vue
```
POST /api/v1/users/{userId}/task-views
```

**Corps :**
```json
{
  "name": "Mes tâches urgentes",
  "filters": {
    "status": ["en_cours", "a_faire"],
    "priority": ["haute", "critique"],
    "assigned_to_me": true,
    "due_within_days": 7
  },
  "sort_by": "due_date",
  "sort_direction": "asc",
  "is_default": false
}
```

#### 10.3 Modifier une vue
```
PUT /api/v1/task-views/{id}
```

#### 10.4 Supprimer une vue
```
DELETE /api/v1/task-views/{id}
```

---

### 11. TIME TRACKING - SAISIES

#### 11.1 Saisir du temps
```
POST /api/v1/tasks/{id}/time-entries
```

**Corps :**
```json
{
  "date": "2026-02-10",
  "hours": 2.5,
  "description": "Développement de l'interface utilisateur",
  "start_time": "09:00:00",
  "end_time": "11:30:00"
}
```

**Validation :**
- `hours` : requis, numeric, entre 0.1 et 24
- Maximum 24h par utilisateur par jour
- Pas de chevauchement d'heures

#### 11.2 Lister les saisies de temps d'une tâche
```
GET /api/v1/tasks/{id}/time-entries
```

#### 11.3 Modifier une saisie de temps
```
PUT /api/v1/time-entries/{id}
```

**Permissions :** Propriétaire (dans les 7 jours) ou ADMIN ou MANAGER

#### 11.4 Supprimer une saisie de temps
```
DELETE /api/v1/time-entries/{id}
```

#### 11.5 Démarrer une session de temps
```
POST /api/v1/tasks/{id}/time/start
```

**Corps :**
```json
{
  "description": "Travail sur le design responsive"
}
```

**Réponse :**
```json
{
  "success": true,
  "message": "Session de temps démarrée",
  "data": {
    "id": 15,
    "task_id": 1,
    "user_id": 3,
    "start_time": "2026-02-10T14:00:00.000000Z",
    "end_time": null,
    "description": "Travail sur le design responsive"
  }
}
```

#### 11.6 Arrêter une session de temps
```
PUT /api/v1/time-entries/{id}/stop
```

**Réponse :** Calcul automatique des heures et mise à jour de la tâche

#### 11.7 Session de temps actuelle
```
GET /api/v1/time/current-session
```

**Réponse :** Session en cours de l'utilisateur ou null

#### 11.8 Mes sessions de temps
```
GET /api/v1/time/my-sessions
```

**Paramètres :**
```
?task_id=1&date_from=2026-02-01&date_to=2026-02-29
```

#### 11.9 Rapport de temps
```
GET /api/v1/time/report
```

**Réponse :** Rapport détaillé avec statistiques et répartition

---

### 12. TIME TRACKING - CONSULTATIONS

#### 12.1 Mon temps saisi
```
GET /api/v1/users/{userId}/time-entries
```

**Paramètres :**
```
?date_from=2026-02-01&date_to=2026-02-29&project_id=1
```

#### 12.2 Mes résumés et statistiques
```
GET /api/v1/users/{userId}/time-summary
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "total_hours": 89.5,
    "total_days": 12,
    "average_hours_per_day": 7.46,
    "by_project": {
      "Refonte Site Web": 45.5,
      "App Mobile": 32.0,
      "Support Client": 12.0
    },
    "by_type": {
      "dev": 65.5,
      "design": 15.0,
      "test": 9.0
    },
    "efficiency_metrics": {
      "tasks_completed": 8,
      "average_time_per_task": 11.19
    }
  }
}
```

#### 12.3 Export de mon temps
```
GET /api/v1/users/{userId}/time-entries/export
```

**Paramètres :**
```
?format=excel&date_from=2026-02-01&date_to=2026-02-29
```

**Formats :** `excel`, `pdf`, `csv`

---

### 13. TIME TRACKING - VUES TÂCHE

#### 13.1 Résumé temps d'une tâche
```
GET /api/v1/tasks/{id}/time-summary
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "estimated_hours": 8.0,
    "actual_hours": 5.5,
    "variance": -2.5,
    "progress_percentage": 68.75,
    "progress_color": "green",
    "time_by_user": [
      {
        "user": {"id": 3, "name": "Jean Martin"},
        "total_hours": 3.5,
        "entries_count": 3
      },
      {
        "user": {"id": 4, "name": "Lisa Chen"},
        "total_hours": 2.0,
        "entries_count": 1
      }
    ],
    "daily_breakdown": {
      "2026-02-08": 2.0,
      "2026-02-09": 1.5,
      "2026-02-10": 2.0
    }
  }
}
```

---

### 14. TIME TRACKING - VUES PROJET

#### 14.1 Résumé temps d'un projet
```
GET /api/v1/projects/{id}/time-summary
```

#### 14.2 Détails temps d'un projet
```
GET /api/v1/projects/{id}/time-entries
```

**Paramètres :**
```
?user_id=3&task_type=dev&date_from=2026-02-01
```

#### 14.3 Analytics temps d'un projet
```
GET /api/v1/projects/{id}/time-analytics
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "time_evolution": {
      "2026-02-01": 8.0,
      "2026-02-02": 6.5,
      "2026-02-03": 9.0
    },
    "cumulative_time": {
      "2026-02-01": 8.0,
      "2026-02-02": 14.5,
      "2026-02-03": 23.5
    },
    "user_distribution": {
      "Jean Martin": {
        "hours": 45.5,
        "percentage": 51.0
      },
      "Lisa Chen": {
        "hours": 32.0,
        "percentage": 36.0
      },
      "Marc Dubois": {
        "hours": 12.0,
        "percentage": 13.0
      }
    },
    "task_type_distribution": {
      "dev": {"hours": 65.5, "percentage": 73.0},
      "design": {"hours": 15.0, "percentage": 17.0},
      "test": {"hours": 9.0, "percentage": 10.0}
    },
    "productivity_metrics": {
      "average_hours_per_task": 3.73,
      "tasks_completed": 16,
      "tasks_in_progress": 8,
      "efficiency_ratio": 95.5
    }
  }
}
```

---

### 15. SYSTÈME DE NOTIFICATIONS

#### 15.1 Mes notifications
```
GET /api/v1/notifications
```

**Paramètres :**
```
?page=1&per_page=20&unread_only=true&type=task_assigned
```

#### 15.2 Marquer comme lue
```
PUT /api/v1/notifications/{id}/read
```

#### 15.3 Tout marquer comme lu
```
POST /api/v1/notifications/mark-all-read
```

#### 15.4 Types de notifications
- `task_assigned` : Tâche assignée
- `task_status_changed` : Statut modifié
- `task_comment_added` : Nouveau commentaire
- `task_comment_mention` : Mention dans commentaire
- `task_deadline_approaching` : Échéance proche
- `task_difficulty_reported` : Difficulté signalée
- `task_file_added` : Fichier ajouté
- `project_created` : Projet créé
- `project_status_changed` : Statut projet modifié

---

## 🔄 WORKFLOWS ET INTÉGRATIONS

### Workflow de Création de Tâche

1. **Créer la tâche** via `POST /api/v1/projects/{id}/tasks`
2. **Assigner des utilisateurs** (peut être fait dans l'étape 1 ou via `POST /api/v1/tasks/{id}/assign`)
3. **Ajouter des tags** (dans l'étape 1 ou séparément)
4. **Notifications automatiques** envoyées aux assignés

### Workflow de Suivi de Temps

1. **Démarrer session** via `POST /api/v1/tasks/{id}/time/start`
2. **Travail en cours** (optionnel: pause/reprise)
3. **Arrêter session** via `PUT /api/v1/time-entries/{id}/stop`
4. **Calcul automatique** des heures réelles de la tâche
5. **Mise à jour** du pourcentage de progression

### Workflow de Gestion des Difficultés

1. **Signaler difficulté** via `POST /api/v1/tasks/{id}/difficulties`
2. **Statut tâche** automatiquement changé en "bloque"
3. **Notifications** envoyées aux responsables
4. **Résolution** via `PUT /api/v1/difficulties/{id}`
5. **Déblocage automatique** si toutes difficultés résolues

---

## 🚨 CODES D'ERREUR

### Codes HTTP Standards
- **200** : Succès
- **201** : Créé avec succès
- **204** : Succès sans contenu
- **400** : Erreur de validation
- **401** : Non authentifié
- **403** : Non autorisé
- **404** : Resource non trouvée
- **409** : Conflit (ex: session temps déjà active)
- **422** : Erreur de validation détaillée
- **429** : Trop de requêtes
- **500** : Erreur serveur

### Codes d'Erreur Métier
- **TASK_001** : Tâche déjà terminée
- **TASK_002** : Dépassement limite heures/jour
- **TASK_003** : Session temps déjà active
- **TASK_004** : Difficulté non résolue
- **PROJ_001** : Projet archivé
- **PROJ_002** : Équipe complète
- **AUTH_001** : Token expiré
- **AUTH_002** : Permissions insuffisantes

---

## 📊 EXEMPLES D'INTÉGRATION

### Intégration Frontend (React/Vue)

#### Service API de base
```javascript
class TaskApiService {
  constructor(baseUrl, token) {
    this.baseUrl = baseUrl;
    this.token = token;
  }

  async request(endpoint, options = {}) {
    const response = await fetch(`${this.baseUrl}${endpoint}`, {
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...options.headers
      },
      ...options
    });

    if (!response.ok) {
      throw new Error(`API Error: ${response.status}`);
    }

    return response.json();
  }

  // Exemples de méthodes
  getTasks(filters = {}) {
    const params = new URLSearchParams(filters);
    return this.request(`/tasks?${params}`);
  }

  createTask(projectId, taskData) {
    return this.request(`/projects/${projectId}/tasks`, {
      method: 'POST',
      body: JSON.stringify(taskData)
    });
  }

  updateTaskStatus(taskId, status, comment = '') {
    return this.request(`/tasks/${taskId}/status`, {
      method: 'PUT',
      body: JSON.stringify({ status, comment })
    });
  }

  startTimeTracking(taskId, description = '') {
    return this.request(`/tasks/${taskId}/time/start`, {
      method: 'POST',
      body: JSON.stringify({ description })
    });
  }
}
```

### Intégration Mobile (Flutter/React Native)

#### Gestion de l'état offline
```javascript
class OfflineTaskManager {
  constructor(apiService) {
    this.api = apiService;
    this.pendingActions = [];
  }

  async addTimeEntry(taskId, hours, description) {
    const timeEntry = {
      task_id: taskId,
      hours,
      description,
      date: new Date().toISOString().split('T')[0]
    };

    try {
      return await this.api.request(`/tasks/${taskId}/time-entries`, {
        method: 'POST',
        body: JSON.stringify(timeEntry)
      });
    } catch (error) {
      // Mode offline : stocker localement
      this.pendingActions.push({
        type: 'TIME_ENTRY',
        data: timeEntry,
        timestamp: Date.now()
      });

      return { success: true, offline: true };
    }
  }

  async syncPendingActions() {
    for (const action of this.pendingActions) {
      try {
        switch (action.type) {
          case 'TIME_ENTRY':
            await this.api.addTimeEntry(action.data);
            break;
          // Autres types d'actions...
        }
      } catch (error) {
        console.error('Sync failed for action:', action, error);
      }
    }

    this.pendingActions = [];
  }
}
```

### Intégration Webhook

#### Configuration des webhooks
```javascript
// Endpoint de réception des webhooks
app.post('/webhooks/targetdesk', (req, res) => {
  const { event, data } = req.body;

  switch (event) {
    case 'task.created':
      // Intégrer avec Slack, Teams, etc.
      notifyTeam(`Nouvelle tâche: ${data.title} dans ${data.project.name}`);
      break;

    case 'task.completed':
      // Déclencher actions automatiques
      generateReport(data.project_id);
      break;

    case 'task.overdue':
      // Alertes automatiques
      sendAlert(data.assignees, `Tâche en retard: ${data.title}`);
      break;

    case 'time.entry.added':
      // Synchroniser avec système de facturation
      syncBillingSystem(data);
      break;
  }

  res.status(200).json({ received: true });
});
```

---

## 📈 MÉTRIQUES ET MONITORING

### Endpoints de Monitoring

#### Santé de l'API
```
GET /api/health
```

**Réponse :**
```json
{
  "status": "healthy",
  "version": "1.0.0",
  "timestamp": "2026-02-10T16:30:00.000000Z",
  "services": {
    "database": "healthy",
    "cache": "healthy",
    "storage": "healthy"
  },
  "metrics": {
    "response_time_avg": "250ms",
    "requests_per_minute": 1250,
    "active_sessions": 89
  }
}
```

#### Métriques d'usage
```
GET /api/v1/admin/metrics
```

**Permissions :** ADMIN uniquement

**Réponse :**
```json
{
  "success": true,
  "data": {
    "users": {
      "active_today": 45,
      "active_this_week": 78,
      "total": 156
    },
    "tasks": {
      "created_today": 12,
      "completed_today": 18,
      "total_active": 245
    },
    "time_tracking": {
      "hours_logged_today": 156.5,
      "average_hours_per_user": 6.2,
      "top_contributors": [
        {"user": "Jean Martin", "hours": 8.5},
        {"user": "Lisa Chen", "hours": 7.8}
      ]
    },
    "projects": {
      "active": 15,
      "completed_this_month": 3,
      "average_completion_time": "45 jours"
    }
  }
}
```

---

## 🔧 CONFIGURATION ET DÉPLOIEMENT

### Variables d'Environnement

```env
# Configuration API
API_VERSION=v1
API_RATE_LIMIT=60
API_TIMEOUT=30

# Base de données
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=targetdesk
DB_USERNAME=user
DB_PASSWORD=password

# Stockage fichiers
FILESYSTEM_DRIVER=public
MAX_FILE_SIZE=10240
ALLOWED_FILE_TYPES=pdf,doc,docx,xls,xlsx,jpg,jpeg,png

# Notifications
NOTIFICATION_DRIVER=database
MAIL_MAILER=smtp
PUSHER_APP_ID=your_pusher_id
PUSHER_APP_KEY=your_pusher_key

# Cache et Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=localhost
REDIS_PORT=6379

# Webhooks
WEBHOOK_SIGNING_SECRET=your_secret_key
WEBHOOK_TIMEOUT=5
```

### Configuration Nginx

```nginx
server {
    listen 80;
    server_name api.targetdesk.com;
    root /var/www/targetdesk/public;

    # Augmenter limite upload pour fichiers
    client_max_body_size 10M;

    # Headers sécurité
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gestion des assets
    location ~* \.(js|css|png|jpg|jpeg|gif|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # API routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;

        # Augmenter timeout pour exports
        fastcgi_read_timeout 300;
    }
}
```

---

## 🛡 SÉCURITÉ ET BONNES PRATIQUES

### Authentification
- Utiliser HTTPS en production
- Tokens avec expiration (TTL recommandé: 24h)
- Refresh tokens pour applications mobiles
- Rate limiting par utilisateur et par IP

### Autorisation
- Vérification des permissions à chaque endpoint
- Principe du moindre privilège
- Logs d'audit pour actions sensibles

### Validation des Données
- Validation côté serveur obligatoire
- Sanitisation des entrées utilisateur
- Protection contre injection SQL/XSS
- Validation taille et type des fichiers

### Performance
- Utiliser la pagination sur toutes les listes
- Cache Redis pour données fréquentes
- Index database optimisés
- Compression gzip activée

### Monitoring
- Logs structurés (format JSON)
- Métriques APM (temps de réponse, erreurs)
- Alertes sur seuils critiques
- Sauvegarde régulière des données

---

## 📞 SUPPORT ET CONTACT

### Équipe Technique
- **Lead Developer :** dev-team@targetdesk.com
- **DevOps :** devops@targetdesk.com
- **Support API :** api-support@targetdesk.com

### Documentation
- **API Docs Live :** https://api.targetdesk.com/docs
- **Postman Collection :** [Lien vers collection]
- **Changelog :** https://docs.targetdesk.com/changelog
- **Status Page :** https://status.targetdesk.com

### Communauté
- **GitHub :** https://github.com/targetdesk/api
- **Discord :** [Lien vers serveur Discord]
- **Forum :** https://community.targetdesk.com

---

**© 2026 TargetDesk - Documentation d'Intégration API v1.0**
*Dernière mise à jour : 10 février 2026*