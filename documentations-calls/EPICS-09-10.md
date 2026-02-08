# API Appels Call Center EPIC-09/10 - Guide d'Intégration
## Manager Reporting & Administration

## Base URL
```
http://targetdesk-backend.test/api/v1: local dev
http://localhost:8000/api/v1: local dev
```

## Authentification
Tous les endpoints nécessitent une authentification Bearer token.
```
Authorization: Bearer {token}
```

---

## EPIC-09: Manager Reporting

### Dashboard Département

**GET** `/call-center/manager/dashboard`

Affiche les indicateurs clés globaux du département, tendances d'activité et motifs d'appels les plus fréquents.

**Exemple de requête:**
```bash
curl -X GET "http://targetdesk-backend.test/api/v1/call-center/manager/dashboard?period=month" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"
```

### Paramètres de requête (optionnels)

- `period` (enum, optionnel) - Période d'analyse:
  - `"week"` - Cette semaine
  - `"month"` - Ce mois (défaut)
  - `"quarter"` - Ce trimestre
  - `"year"` - Cette année

### Réponse de succès (200 OK)
```json
{
  "success": true,
  "message": "Dashboard manager récupéré",
  "data": {
    "period": {
      "start": "2026-02-01",
      "end": "2026-02-08"
    },
    "kpis": {
      "total_volume": 156,
      "response_rate": "92.3%",
      "avg_treatment_time": "00:15:30",
      "complaint_count": 8,
      "resolution_rate": "85.9%"
    },
    "top_motifs": {
      "Problème technique": 45,
      "Demande devis": 32,
      "Facturation": 28,
      "Livraison retard": 22,
      "Information produit": 18
    },
    "graphs": {
      "evolution": {
        "2026-02-01": 20,
        "2026-02-02": 25,
        "2026-02-03": 18,
        "2026-02-04": 22,
        "2026-02-05": 28,
        "2026-02-06": 24,
        "2026-02-07": 19
      },
      "heatmap_hours": {
        "08": 5,
        "09": 12,
        "10": 18,
        "11": 22,
        "12": 8,
        "13": 6,
        "14": 20,
        "15": 25,
        "16": 18,
        "17": 12
      },
      "distribution_type": {
        "entrant": 120,
        "sortant": 36
      }
    }
  }
}
```

### Structure de la réponse

**Période:**
- `start` - Date de début de la période
- `end` - Date de fin de la période

**KPIs:**
- `total_volume` - Nombre total d'appels
- `response_rate` - Taux de réponse (%)
- `avg_treatment_time` - Temps moyen de traitement (HH:MM:SS)
- `complaint_count` - Nombre de réclamations
- `resolution_rate` - Taux de résolution (%)

**Top Motifs:**
- Top 5 des motifs d'appels les plus fréquents avec leur nombre d'occurrences

**Graphiques:**
- `evolution` - Évolution du volume d'appels par jour
- `heatmap_hours` - Distribution des appels par heure de la journée (0-23)
- `distribution_type` - Répartition entrant/sortant

### Cas d'usage

- Vue stratégique de l'activité du département
- Identification des tendances et pics d'activité
- Analyse des motifs récurrents
- Planification des ressources
- Reporting de direction

### Erreurs possibles

**500 - Server Error:**
```json
{
  "success": false,
  "message": "Erreur dashboard manager"
}
```

---

## EPIC-10: Administration

### Gestion des Utilisateurs

#### Lister tous les Utilisateurs

**GET** `/admin/users`

Récupère la liste complète des utilisateurs avec leurs rôles et statistiques.

**Exemple de requête:**
```bash
curl -X GET http://targetdesk-backend.test/api/v1/admin/users \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"
```

**Réponse de succès (200 OK):**
```json
{
  "success": true,
  "message": "Liste des utilisateurs récupérée",
  "data": [
    {
      "id": 1,
      "name": "Chi Samuel Apeng",
      "first_name": "Chi Samuel",
      "last_name": "Apeng",
      "email": "samuel@targetpoint.fr",
      "phone": "0612345678",
      "status": "active",
      "department_id": 1,
      "created_at": "2026-01-15T10:00:00.000000Z",
      "assigned_calls_count": 45,
      "created_calls_count": 38,
      "primary_department": {
        "id": 1,
        "name": "Support Technique",
        "code": "SUP"
      },
      "roles": [
        {
          "id": 1,
          "name": "agent",
          "display_name": "Agent"
        }
      ]
    }
  ]
}
```

---

#### Créer un Utilisateur

**POST** `/admin/users`

Crée un nouvel utilisateur dans le système.

**Exemple de requête:**
```bash
curl -X POST http://targetdesk-backend.test/api/v1/admin/users \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Marie Dupont",
    "first_name": "Marie",
    "last_name": "Dupont",
    "email": "marie.dupont@targetpoint.fr",
    "password": "password123",
    "phone": "0687654321",
    "department_id": 1
  }'
```

**Champs requis:**
- `name` (string, requis) - Nom complet
- `email` (string, requis) - Adresse email unique
- `password` (string, requis) - Mot de passe (min 6 caractères)

**Champs optionnels:**
- `first_name` (string) - Prénom
- `last_name` (string) - Nom de famille
- `phone` (string) - Téléphone
- `department_id` (integer) - ID du département

**Réponse de succès (201 Created):**
```json
{
  "success": true,
  "message": "Utilisateur créé avec succès",
  "data": {
    "id": 5,
    "name": "Marie Dupont",
    "first_name": "Marie",
    "last_name": "Dupont",
    "email": "marie.dupont@targetpoint.fr",
    "phone": "0687654321",
    "status": "active",
    "department_id": 1,
    "primary_department": {
      "id": 1,
      "name": "Support Technique",
      "code": "SUP"
    }
  }
}
```

---

#### Voir Détails d'un Utilisateur

**GET** `/admin/users/{id}`

**Réponse de succès (200 OK):**
```json
{
  "success": true,
  "message": "Utilisateur trouvé",
  "data": {
    "id": 1,
    "name": "Chi Samuel Apeng",
    "email": "samuel@targetpoint.fr",
    "assigned_calls_count": 45,
    "created_calls_count": 38,
    "closed_calls_count": 32,
    "primary_department": { },
    "roles": [ ]
  }
}
```

---

#### Modifier un Utilisateur

**PUT** `/admin/users/{id}`

**Exemple de requête:**
```bash
curl -X PUT http://targetdesk-backend.test/api/v1/admin/users/5 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Marie Dubois",
    "phone": "0698765432",
    "department_id": 2
  }'
```

---

#### Assigner un Rôle

**PUT** `/admin/users/{id}/assign-role`

**Exemple de requête:**
```bash
curl -X PUT http://targetdesk-backend.test/api/v1/admin/users/5/assign-role \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "role": "supervisor"
  }'
```

**Champs requis:**
- `role` (enum, requis) - Rôle à assigner:
  - `"agent"` - Agent
  - `"supervisor"` - Superviseur
  - `"manager"` - Manager
  - `"admin"` - Administrateur

**Réponse de succès (200 OK):**
```json
{
  "success": true,
  "message": "Rôle assigné avec succès",
  "data": {
    "id": 5,
    "name": "Marie Dupont",
    "roles": [
      {
        "id": 2,
        "name": "supervisor",
        "display_name": "Superviseur"
      }
    ]
  }
}
```

---

#### Activer/Désactiver un Utilisateur

**PUT** `/admin/users/{id}/toggle-status`

**Réponse de succès (200 OK):**
```json
{
  "success": true,
  "message": "Utilisateur désactivé avec succès",
  "data": {
    "id": 5,
    "name": "Marie Dupont",
    "status": "inactive"
  }
}
```

---

#### Réinitialiser le Mot de Passe

**POST** `/admin/users/{id}/reset-password`

**Exemple de requête:**
```bash
curl -X POST http://targetdesk-backend.test/api/v1/admin/users/5/reset-password \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "new_password": "newpassword123"
  }'
```

**Champs requis:**
- `new_password` (string, requis) - Nouveau mot de passe (min 6 caractères)

---

### Gestion des Départements

#### Lister tous les Départements

**GET** `/admin/departments`

**Réponse de succès (200 OK):**
```json
{
  "success": true,
  "message": "Liste des départements récupérée",
  "data": [
    {
      "id": 1,
      "name": "Support Technique",
      "code": "SUP",
      "description": "Support technique niveau 1",
      "is_active": true,
      "calls_count": 156,
      "manager": {
        "id": 2,
        "name": "Pierre Martin"
      },
      "created_at": "2026-01-10T10:00:00.000000Z"
    }
  ]
}
```

---

#### Créer un Département

**POST** `/admin/departments`

**Exemple de requête:**
```bash
curl -X POST http://targetdesk-backend.test/api/v1/admin/departments \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Support Niveau 2",
    "code": "SUP2",
    "description": "Support technique niveau 2",
    "manager_id": 3
  }'
```

**Champs requis:**
- `name` (string, requis) - Nom unique du département
- `code` (string, requis) - Code unique (max 10 caractères)

**Champs optionnels:**
- `description` (string) - Description
- `manager_id` (integer) - ID du responsable

**Réponse de succès (201 Created):**
```json
{
  "success": true,
  "message": "Département créé avec succès",
  "data": {
    "id": 4,
    "name": "Support Niveau 2",
    "code": "SUP2",
    "description": "Support technique niveau 2",
    "is_active": true,
    "manager": {
      "id": 3,
      "name": "Sophie Laurent"
    }
  }
}
```

---

#### Voir Détails d'un Département

**GET** `/admin/departments/{id}`

---

#### Modifier un Département

**PUT** `/admin/departments/{id}`

---

#### Supprimer un Département

**DELETE** `/admin/departments/{id}`

**Réponse de succès (200 OK):**
```json
{
  "success": true,
  "message": "Département supprimé avec succès",
  "data": null
}
```

---

#### Activer/Désactiver un Département

**PUT** `/admin/departments/{id}/toggle-status`

---

### Gestion des Motifs d'Appel

#### Lister tous les Motifs

**GET** `/admin/call-motifs`

**Paramètres de requête:**
- `tree` (boolean, optionnel) - Retourner en structure arborescente (défaut: false)

**Réponse plate (tree=false):**
```json
{
  "success": true,
  "message": "Liste des motifs récupérée",
  "data": [
    {
      "id": 1,
      "label": "Problème technique",
      "category": "support",
      "parent_id": null,
      "department_id": 1,
      "sla_hours": 24,
      "suggested_script": "Bonjour, je comprends que vous rencontrez un problème technique...",
      "display_order": 1,
      "is_active": true,
      "parent": null,
      "department": {
        "id": 1,
        "name": "Support Technique"
      }
    }
  ]
}
```

**Réponse arborescente (tree=true):**
```json
{
  "success": true,
  "message": "Liste des motifs récupérée",
  "data": [
    {
      "id": 1,
      "label": "Problème technique",
      "category": "support",
      "children": [
        {
          "id": 2,
          "label": "Problème de connexion",
          "category": "support",
          "children": []
        },
        {
          "id": 3,
          "label": "Problème d'installation",
          "category": "support",
          "children": []
        }
      ]
    }
  ]
}
```

---

#### Créer un Motif

**POST** `/admin/call-motifs`

**Exemple de requête:**
```bash
curl -X POST http://targetdesk-backend.test/api/v1/admin/call-motifs \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "label": "Problème de facturation",
    "category": "reclamation",
    "parent_id": null,
    "department_id": 4,
    "sla_hours": 24,
    "suggested_script": "Bonjour, concernant votre facture...",
    "display_order": 1
  }'
```

**Champs requis:**
- `label` (string, requis) - Libellé du motif
- `category` (enum, requis) - Catégorie:
  - `"info"` - Information
  - `"reclamation"` - Réclamation
  - `"support"` - Support technique
  - `"commercial"` - Commercial
  - `"autre"` - Autre

**Champs optionnels:**
- `parent_id` (integer) - ID du motif parent (pour hiérarchie)
- `department_id` (integer) - Département concerné
- `sla_hours` (integer) - SLA en heures
- `suggested_script` (string) - Script suggéré pour l'agent
- `display_order` (integer) - Ordre d'affichage

**Réponse de succès (201 Created):**
```json
{
  "success": true,
  "message": "Motif créé avec succès",
  "data": {
    "id": 8,
    "label": "Problème de facturation",
    "category": "reclamation",
    "parent_id": null,
    "department_id": 4,
    "sla_hours": 24,
    "suggested_script": "Bonjour, concernant votre facture...",
    "display_order": 1,
    "is_active": true
  }
}
```

---

#### Voir Détails d'un Motif

**GET** `/admin/call-motifs/{id}`

**Réponse inclut parent, enfants et département:**
```json
{
  "success": true,
  "message": "Motif trouvé",
  "data": {
    "id": 1,
    "label": "Problème technique",
    "category": "support",
    "parent": null,
    "children": [
      {
        "id": 2,
        "label": "Problème de connexion"
      }
    ],
    "department": {
      "id": 1,
      "name": "Support Technique"
    }
  }
}
```

---

#### Modifier un Motif

**PUT** `/admin/call-motifs/{id}`

---

#### Supprimer un Motif

**DELETE** `/admin/call-motifs/{id}`

---

#### Activer/Désactiver un Motif

**PUT** `/admin/call-motifs/{id}/toggle-status`

---

## Documentation Swagger

La documentation interactive complète est disponible sur:
**http://targetdesk-backend.test/api/documentation**

Sections:
- **Manager** → Dashboard département
- **Admin - Users** → Gestion utilisateurs
- **Admin - Departments** → Gestion départements
- **Admin - Call Motifs** → Gestion motifs d'appel

---

## User Stories Implémentées

### EPIC-09: Manager Reporting
✅ **US-CC-050** - Dashboard département avec KPIs et tendances

### EPIC-10: Administration
✅ **US-CC-051** - Gestion des utilisateurs (CRUD + rôles)  
✅ **US-CC-052** - Gestion des départements (CRUD)  
✅ **US-CC-053** - Gestion des motifs d'appel hiérarchiques

---

## Résumé des Endpoints

| Méthode | Endpoint | Description | EPIC |
|---------|----------|-------------|------|
| GET | `/call-center/manager/dashboard` | Dashboard département | 09 |
| GET | `/admin/users` | Lister utilisateurs | 10 |
| POST | `/admin/users` | Créer utilisateur | 10 |
| GET | `/admin/users/{id}` | Détails utilisateur | 10 |
| PUT | `/admin/users/{id}` | Modifier utilisateur | 10 |
| PUT | `/admin/users/{id}/assign-role` | Assigner rôle | 10 |
| PUT | `/admin/users/{id}/toggle-status` | Activer/Désactiver | 10 |
| POST | `/admin/users/{id}/reset-password` | Reset mot de passe | 10 |
| GET | `/admin/departments` | Lister départements | 10 |
| POST | `/admin/departments` | Créer département | 10 |
| GET | `/admin/departments/{id}` | Détails département | 10 |
| PUT | `/admin/departments/{id}` | Modifier département | 10 |
| DELETE | `/admin/departments/{id}` | Supprimer département | 10 |
| PUT | `/admin/departments/{id}/toggle-status` | Activer/Désactiver | 10 |
| GET | `/admin/call-motifs` | Lister motifs | 10 |
| POST | `/admin/call-motifs` | Créer motif | 10 |
| GET | `/admin/call-motifs/{id}` | Détails motif | 10 |
| PUT | `/admin/call-motifs/{id}` | Modifier motif | 10 |
| DELETE | `/admin/call-motifs/{id}` | Supprimer motif | 10 |
| PUT | `/admin/call-motifs/{id}/toggle-status` | Activer/Désactiver | 10 |

---

## Intégration Frontend - Suggestions

### Dashboard Manager
```jsx
<ManagerDashboard>
  <PeriodSelector options={["week", "month", "quarter", "year"]} />
  
  <KPICards>
    - Volume total
    - Taux de réponse
    - Temps moyen traitement
    - Nombre réclamations
    - Taux de résolution
  </KPICards>
  
  <ChartsSection>
    <EvolutionChart data={evolution} />
    <HeatmapHours data={heatmap_hours} />
    <TypeDistribution data={distribution_type} />
  </ChartsSection>
  
  <TopMotifsTable data={top_motifs} />
</ManagerDashboard>
```

### Administration
```jsx
<AdminPanel>
  <UserManagement>
    <UserList />
    <CreateUserForm />
    <RoleAssignment />
    <PasswordReset />
  </UserManagement>
  
  <DepartmentManagement>
    <DepartmentList />
    <DepartmentForm />
    <ManagerAssignment />
  </DepartmentManagement>
  
  <CallMotifManagement>
    <MotifTree mode="hierarchical" />
    <MotifForm />
    <ScriptEditor />
  </CallMotifManagement>
</AdminPanel>
```

---

**Version:** 2.0.0  
**Date:** 2026-02-08  
**Auteur:** Chi Samuel Apeng  
**EPICS:** EPIC-09 (Manager Reporting), EPIC-10 (Administration)  
**Dernière mise à jour:** Documentation complète des endpoints Manager et Administration