# API Appels Call Center EPIC-02 - Guide d'Intégration
## Gestion des Files d'Attente & Notes

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

## Consulter Ma File d'Appels Personnelle

**GET** `/call-center/calls/my-queue`

Récupère tous les appels assignés à l'agent connecté avec statut "à traiter" ou "en cours". Les appels sont triés par urgence (critique > urgent > normal) puis par date de création (plus anciens d'abord).

**Exemple de requête:**
```bash
curl -X GET http://targetdesk-backend.test/api/v1/call-center/calls/my-queue \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"
```

### Réponse de succès (200 OK)
```json
{
  "success": true,
  "message": "File personnelle récupérée",
  "data": {
    "total": 5,
    "urgent_count": 2,
    "calls": [
      {
        "id": 3,
        "call_id": "CALL-2026-0003",
        "type": "entrant",
        "phone_number": "0698765432",
        "caller_name": "Marie Dubois",
        "object": "Système bloqué",
        "summary": "Le système ne répond plus depuis ce matin",
        "urgency": "critique",
        "status": "a_traiter",
        "assigned_to": 1,
        "created_by": 2,
        "created_at": "2026-02-07T08:30:00.000000Z",
        "updated_at": "2026-02-07T08:30:00.000000Z",
        "time_elapsed": "Il y a 5 heures",
        "department": {
          "id": 1,
          "name": "Support Technique",
          "code": "SUP"
        },
        "client": null,
        "contact": null,
        "creator": {
          "id": 2,
          "name": "Pierre Martin",
          "email": "pierre@targetpoint.fr"
        }
      },
      {
        "id": 1,
        "call_id": "CALL-2026-0001",
        "type": "entrant",
        "phone_number": "0612345678",
        "caller_name": "Jean Dupont",
        "object": "Problème technique",
        "summary": "Le client rencontre une erreur...",
        "urgency": "urgent",
        "status": "en_cours",
        "assigned_to": 1,
        "created_by": 1,
        "created_at": "2026-02-07T10:30:00.000000Z",
        "updated_at": "2026-02-07T11:00:00.000000Z",
        "time_elapsed": "Il y a 2 heures",
        "department": {
          "id": 1,
          "name": "Support Technique",
          "code": "SUP"
        },
        "client": {
          "id": 5,
          "name": "ACME Corporation",
          "code": "ACME-001"
        },
        "contact": null,
        "creator": {
          "id": 1,
          "name": "Chi Samuel Apeng",
          "email": "samuel@targetpoint.fr"
        }
      },
      {
        "id": 7,
        "call_id": "CALL-2026-0007",
        "type": "entrant",
        "phone_number": "0676543210",
        "caller_name": "Sophie Laurent",
        "object": "Question facturation",
        "summary": "Demande d'information sur la dernière facture",
        "urgency": "normal",
        "status": "a_traiter",
        "assigned_to": 1,
        "created_by": 1,
        "created_at": "2026-02-07T09:15:00.000000Z",
        "updated_at": "2026-02-07T09:15:00.000000Z",
        "time_elapsed": "Il y a 4 heures",
        "department": {
          "id": 1,
          "name": "Support Technique",
          "code": "SUP"
        },
        "client": null,
        "contact": {
          "id": 12,
          "name": "Sophie Laurent",
          "email": "sophie.laurent@example.com"
        },
        "creator": {
          "id": 1,
          "name": "Chi Samuel Apeng",
          "email": "samuel@targetpoint.fr"
        }
      }
    ]
  }
}
```

### Structure de la réponse

**`data.total`** (integer) - Nombre total d'appels dans ma file  
**`data.urgent_count`** (integer) - Nombre d'appels urgents ou critiques  
**`data.calls`** (array) - Liste des appels avec:
- **Relations chargées:** department, client, contact, creator
- **Champ calculé:** `time_elapsed` - Temps écoulé depuis la création

### Tri des résultats

Les appels sont automatiquement triés par:
1. **Urgence** (priorité décroissante):
   - critique
   - urgent
   - normal
2. **Date de création** (ordre croissant) - Les plus anciens d'abord

### Filtres automatiques appliqués

✅ **Assignation** - Uniquement les appels assignés à l'agent connecté  
✅ **Statut** - Uniquement "a_traiter" ou "en_cours"  
✅ **Tri intelligent** - Par urgence puis ancienneté

### Cas d'usage

Cette endpoint est idéale pour:
- Afficher le tableau de bord personnel de l'agent
- Prioriser les appels à traiter en fonction de l'urgence
- Voir le nombre d'appels urgents en attente
- Gérer sa charge de travail quotidienne

### Erreurs possibles

**401 - Unauthenticated:**
```json
{
  "message": "Unauthenticated."
}
```

**500 - Server Error:**
```json
{
  "success": false,
  "message": "Erreur lors de la récupération de la file personnelle"
}
```

---

## Consulter la File du Département

**GET** `/call-center/calls/department-queue`

Récupère tous les appels du département de l'agent connecté avec statut "à traiter" ou "en cours". Permet de voir les appels non assignés, ses propres appels et ceux des collègues.

**Exemple de requête:**
```bash
curl -X GET http://targetdesk-backend.test/api/v1/call-center/calls/department-queue \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"
```

### Réponse de succès (200 OK)
```json
{
  "success": true,
  "message": "File du département récupérée",
  "data": {
    "total": 10,
    "unassigned_count": 3,
    "my_calls_count": 2,
    "others_calls_count": 5,
    "calls": [
      {
        "id": 8,
        "call_id": "CALL-2026-0008",
        "type": "entrant",
        "phone_number": "0687654321",
        "caller_name": "Thomas Bernard",
        "object": "Panne système critique",
        "summary": "Production arrêtée, système indisponible",
        "urgency": "critique",
        "status": "a_traiter",
        "assigned_to": null,
        "created_by": 3,
        "created_at": "2026-02-07T12:00:00.000000Z",
        "updated_at": "2026-02-07T12:00:00.000000Z",
        "time_elapsed": "Il y a 1 heure",
        "is_mine": false,
        "is_unassigned": true,
        "department": {
          "id": 1,
          "name": "Support Technique",
          "code": "SUP"
        },
        "assigned_agent": null,
        "creator": {
          "id": 3,
          "name": "Marie Dubois",
          "email": "marie@targetpoint.fr"
        }
      },
      {
        "id": 1,
        "call_id": "CALL-2026-0001",
        "type": "entrant",
        "phone_number": "0612345678",
        "caller_name": "Jean Dupont",
        "object": "Problème technique",
        "summary": "Le client rencontre une erreur...",
        "urgency": "urgent",
        "status": "en_cours",
        "assigned_to": 1,
        "created_by": 1,
        "created_at": "2026-02-07T10:30:00.000000Z",
        "updated_at": "2026-02-07T11:00:00.000000Z",
        "time_elapsed": "Il y a 2 heures",
        "is_mine": true,
        "is_unassigned": false,
        "department": {
          "id": 1,
          "name": "Support Technique",
          "code": "SUP"
        },
        "assigned_agent": {
          "id": 1,
          "name": "Chi Samuel Apeng",
          "email": "samuel@targetpoint.fr"
        },
        "creator": {
          "id": 1,
          "name": "Chi Samuel Apeng",
          "email": "samuel@targetpoint.fr"
        }
      },
      {
        "id": 5,
        "call_id": "CALL-2026-0005",
        "type": "entrant",
        "phone_number": "0698123456",
        "caller_name": "Claire Martin",
        "object": "Configuration VPN",
        "summary": "Besoin d'aide pour configurer le VPN",
        "urgency": "normal",
        "status": "en_cours",
        "assigned_to": 2,
        "created_by": 2,
        "created_at": "2026-02-07T11:15:00.000000Z",
        "updated_at": "2026-02-07T11:30:00.000000Z",
        "time_elapsed": "Il y a 1 heure",
        "is_mine": false,
        "is_unassigned": false,
        "department": {
          "id": 1,
          "name": "Support Technique",
          "code": "SUP"
        },
        "assigned_agent": {
          "id": 2,
          "name": "Pierre Martin",
          "email": "pierre@targetpoint.fr"
        },
        "creator": {
          "id": 2,
          "name": "Pierre Martin",
          "email": "pierre@targetpoint.fr"
        }
      }
    ]
  }
}
```

### Structure de la réponse

**Statistiques:**
- `data.total` (integer) - Nombre total d'appels du département
- `data.unassigned_count` (integer) - Nombre d'appels non assignés (disponibles)
- `data.my_calls_count` (integer) - Nombre de mes appels en cours
- `data.others_calls_count` (integer) - Nombre d'appels de mes collègues

**Champs calculés par appel:**
- `is_mine` (boolean) - `true` si l'appel est assigné à l'agent connecté
- `is_unassigned` (boolean) - `true` si l'appel n'est assigné à personne
- `time_elapsed` (string) - Temps écoulé depuis la création

**Relations chargées:**
- department
- client
- contact
- assigned_agent (peut être `null` si non assigné)
- creator

### Tri des résultats

Identique à la file personnelle:
1. **Urgence** (critique > urgent > normal)
2. **Date de création** (plus anciens d'abord)

### Filtres automatiques appliqués

✅ **Département** - Uniquement les appels du département de l'agent  
✅ **Statut** - Uniquement "a_traiter" ou "en_cours"  
✅ **Indicateurs visuels** - `is_mine` et `is_unassigned` pour le frontend

### Cas d'usage

Cette endpoint permet de:
- Voir tous les appels du département en temps réel
- Identifier les appels disponibles (non assignés) à prendre
- Suivre la charge de travail des collègues
- Répartir équitablement les appels entre agents
- Prioriser les appels critiques non pris en charge

### Erreurs possibles

**404 - Not Found (agent sans département):**
```json
{
  "success": false,
  "message": "Vous n'êtes pas assigné à un département"
}
```

**401 - Unauthenticated:**
```json
{
  "message": "Unauthenticated."
}
```

**500 - Server Error:**
```json
{
  "success": false,
  "message": "Erreur lors de la récupération de la file du département"
}
```

---

## S'Auto-Assigner un Appel

**POST** `/call-center/calls/{id}/assign-to-me`

Permet à un agent de s'auto-assigner un appel non assigné. Le statut de l'appel passe automatiquement à "en_cours".

**Exemple de requête:**
```bash
curl -X POST http://targetdesk-backend.test/api/v1/call-center/calls/8/assign-to-me \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"
```

**Paramètres d'URL:**
- `id` (integer, requis) - ID de l'appel à s'assigner

**Corps de la requête:**
Aucun corps requis - l'endpoint utilise l'agent authentifié automatiquement.

### Réponse de succès (200 OK)
```json
{
  "success": true,
  "message": "Appel assigné avec succès",
  "data": {
    "id": 8,
    "call_id": "CALL-2026-0008",
    "type": "entrant",
    "phone_number": "0687654321",
    "caller_name": "Thomas Bernard",
    "object": "Panne système critique",
    "summary": "Production arrêtée, système indisponible",
    "urgency": "critique",
    "status": "en_cours",
    "assigned_to": 1,
    "created_by": 3,
    "created_at": "2026-02-07T12:00:00.000000Z",
    "updated_at": "2026-02-07T13:30:00.000000Z",
    "department": {
      "id": 1,
      "name": "Support Technique",
      "code": "SUP"
    },
    "client": null,
    "contact": null,
    "assigned_agent": {
      "id": 1,
      "name": "Chi Samuel Apeng",
      "email": "samuel@targetpoint.fr"
    },
    "creator": {
      "id": 3,
      "name": "Marie Dubois",
      "email": "marie@targetpoint.fr"
    }
  }
}
```

### Changements automatiques

✅ **`assigned_to`** - Défini à l'ID de l'agent connecté  
✅ **`status`** - Changé automatiquement vers "en_cours"  
✅ **`updated_at`** - Mis à jour automatiquement  
✅ **Audit logging** - Action loggée avec ancien et nouveau statut

### Workflow typique

1. Agent consulte `/calls/department-queue`
2. Agent identifie un appel non assigné (`is_unassigned: true`)
3. Agent clique "Prendre l'appel"
4. Frontend appelle `POST /calls/{id}/assign-to-me`
5. L'appel apparaît maintenant dans sa file personnelle

### Cas d'usage

- Permettre aux agents de prendre des appels dans la file commune
- Répartition naturelle de la charge de travail
- Priorisation par les agents eux-mêmes
- Self-service pour la gestion des appels

### Erreurs possibles

**400 - Bad Request (appel déjà assigné):**
```json
{
  "success": false,
  "message": "Cet appel est déjà assigné à Pierre Martin"
}
```

**404 - Not Found:**
```json
{
  "success": false,
  "message": "Appel non trouvé"
}
```

**401 - Unauthenticated:**
```json
{
  "message": "Unauthenticated."
}
```

**500 - Server Error:**
```json
{
  "success": false,
  "message": "Erreur lors de l'assignation"
}
```

---

## Ajouter une Note à un Appel

**POST** `/call-center/calls/{id}/notes`

Ajoute une note textuelle à un appel existant. Les notes permettent de suivre l'historique des actions et discussions.

**Exemple de requête:**
```bash
curl -X POST http://targetdesk-backend.test/api/v1/call-center/calls/1/notes \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "note": "Client rappelé. Problème identifié : configuration firewall incorrecte. Solution proposée et acceptée.",
    "is_important": true
  }'
```

**Paramètres d'URL:**
- `id` (integer, requis) - ID de l'appel

**Champs requis:**
- `note` (string, requis) - Contenu de la note (texte)

**Champs optionnels:**
- `is_important` (boolean, optionnel) - Marquer la note comme importante (défaut: `false`)

### Réponse de succès (201 Created)
```json
{
  "success": true,
  "message": "Note ajoutée avec succès",
  "data": {
    "id": 15,
    "call_id": 1,
    "note": "Client rappelé. Problème identifié : configuration firewall incorrecte. Solution proposée et acceptée.",
    "is_important": true,
    "created_by": 1,
    "created_at": "2026-02-07T14:30:00.000000Z",
    "updated_at": "2026-02-07T14:30:00.000000Z",
    "creator": {
      "id": 1,
      "name": "Chi Samuel Apeng",
      "email": "samuel@targetpoint.fr"
    }
  }
}
```

### Fonctionnalités automatiques

✅ **`created_by`** - Défini automatiquement à l'ID de l'agent connecté  
✅ **Horodatage** - `created_at` et `updated_at` générés automatiquement  
✅ **Relation creator** - Informations de l'auteur chargées dans la réponse  
✅ **Audit logging** - Création de note loggée

### Bonnes pratiques pour les notes

**Notes importantes (`is_important: true`):**
- Décisions critiques prises
- Informations essentielles pour le suivi
- Problèmes majeurs identifiés
- Solutions appliquées

**Notes normales (`is_important: false`):**
- Tentatives de contact
- Informations complémentaires
- Étapes intermédiaires
- Communications générales

### Cas d'usage

- Documenter les échanges avec le client
- Enregistrer les actions effectuées
- Partager des informations avec l'équipe
- Créer un historique détaillé de la résolution
- Faciliter le transfert d'appels entre agents

### Erreurs possibles

**404 - Not Found:**
```json
{
  "success": false,
  "message": "Appel non trouvé"
}
```

**422 - Validation Error:**
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "note": ["Le contenu de la note est obligatoire"]
  }
}
```

**401 - Unauthenticated:**
```json
{
  "message": "Unauthenticated."
}
```

**500 - Server Error:**
```json
{
  "success": false,
  "message": "Erreur lors de l'ajout de la note"
}
```

---

## Lister les Notes d'un Appel

**GET** `/call-center/calls/{id}/notes`

Récupère toutes les notes associées à un appel spécifique, triées de la plus récente à la plus ancienne.

**Exemple de requête:**
```bash
curl -X GET http://targetdesk-backend.test/api/v1/call-center/calls/1/notes \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"
```

**Paramètres d'URL:**
- `id` (integer, requis) - ID de l'appel

### Réponse de succès (200 OK)
```json
{
  "success": true,
  "message": "Notes récupérées avec succès",
  "data": [
    {
      "id": 15,
      "call_id": 1,
      "note": "Client rappelé. Problème identifié : configuration firewall incorrecte. Solution proposée et acceptée.",
      "is_important": true,
      "created_by": 1,
      "created_at": "2026-02-07T14:30:00.000000Z",
      "updated_at": "2026-02-07T14:30:00.000000Z",
      "creator": {
        "id": 1,
        "name": "Chi Samuel Apeng",
        "email": "samuel@targetpoint.fr"
      }
    },
    {
      "id": 12,
      "call_id": 1,
      "note": "Premier contact établi. Client explique le problème. Analyse en cours.",
      "is_important": false,
      "created_by": 1,
      "created_at": "2026-02-07T11:15:00.000000Z",
      "updated_at": "2026-02-07T11:15:00.000000Z",
      "creator": {
        "id": 1,
        "name": "Chi Samuel Apeng",
        "email": "samuel@targetpoint.fr"
      }
    },
    {
      "id": 8,
      "call_id": 1,
      "note": "Appel reçu et enregistré. En attente de traitement.",
      "is_important": false,
      "created_by": 2,
      "created_at": "2026-02-07T10:30:00.000000Z",
      "updated_at": "2026-02-07T10:30:00.000000Z",
      "creator": {
        "id": 2,
        "name": "Pierre Martin",
        "email": "pierre@targetpoint.fr"
      }
    }
  ]
}
```

### Structure de la réponse

**Format:** Tableau de notes (`array`)

**Champs par note:**
- `id` - ID unique de la note
- `call_id` - ID de l'appel parent
- `note` - Contenu textuel de la note
- `is_important` - Indicateur d'importance
- `created_by` - ID de l'auteur
- `created_at` - Date/heure de création
- `updated_at` - Date/heure de dernière modification
- `creator` - Objet contenant les infos de l'auteur (id, name, email)

### Tri des résultats

✅ **Ordre chronologique inverse** - Plus récente en premier  
✅ **Relations chargées** - Informations de l'auteur pour chaque note

### Cas d'usage

- Afficher l'historique complet des notes dans le détail d'un appel
- Timeline des actions effectuées
- Partage d'informations entre agents
- Audit trail des communications
- Contexte pour reprendre un appel

### Erreurs possibles

**404 - Not Found:**
```json
{
  "success": false,
  "message": "Appel non trouvé"
}
```

**401 - Unauthenticated:**
```json
{
  "message": "Unauthenticated."
}
```

**500 - Server Error:**
```json
{
  "success": false,
  "message": "Erreur lors de la récupération des notes"
}
```

---

## Documentation Swagger

La documentation interactive complète est disponible sur:
**http://targetdesk-backend.test/api/documentation**

Section: **Calls** → Tous les endpoints EPIC-02

---

## User Stories Implémentées (EPIC-02)

✅ **US-CC-009** - Consulter ma file d'appels personnelle  
✅ **US-CC-010** - Consulter la file d'appels du département  
✅ **US-CC-011** - S'auto-assigner un appel non assigné  
✅ **US-CC-012** - Ajouter une note à un appel  
✅ **US-CC-013** - Consulter les notes d'un appel

---

## Résumé des Endpoints EPIC-02

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/call-center/calls/my-queue` | Consulter ma file d'appels personnelle |
| GET | `/call-center/calls/department-queue` | Consulter la file du département |
| POST | `/call-center/calls/{id}/assign-to-me` | S'auto-assigner un appel |
| POST | `/call-center/calls/{id}/notes` | Ajouter une note à un appel |
| GET | `/call-center/calls/{id}/notes` | Lister les notes d'un appel |

---

## Workflow Complet - Exemple d'Utilisation

### Scénario: Agent prend un appel non assigné du département

**Étape 1: Consulter la file du département**
```bash
GET /call-center/calls/department-queue
```
→ Retourne 10 appels, dont 3 non assignés

**Étape 2: S'auto-assigner un appel critique**
```bash
POST /call-center/calls/8/assign-to-me
```
→ Appel assigné à l'agent, statut → "en_cours"

**Étape 3: Ajouter une première note**
```bash
POST /call-center/calls/8/notes
Body: {
  "note": "Contact établi avec le client. Analyse de la panne en cours.",
  "is_important": false
}
```

**Étape 4: Travailler sur l'appel...**

**Étape 5: Ajouter une note importante**
```bash
POST /call-center/calls/8/notes
Body: {
  "note": "Problème résolu. Redémarrage serveur effectué. Production restaurée.",
  "is_important": true
}
```

**Étape 6: Consulter l'historique complet**
```bash
GET /call-center/calls/8/notes
```
→ Retourne toutes les notes avec leurs auteurs

**Étape 7: Clôturer l'appel**
```bash
POST /call-center/calls/8/close
Body: {
  "resolution_summary": "Panne résolue par redémarrage du serveur de production.",
  "final_result": "resolu_satisfait"
}
```

---

## Intégration Frontend - Suggestions

### Composants suggérés

**Dashboard Agent:**
```jsx
<MyQueue>
  - Badge: {urgent_count} appels urgents
  - Liste triée par urgence
  - Actions rapides: Voir détails, Ajouter note
</MyQueue>
```

**File Département:**
```jsx
<DepartmentQueue>
  - Onglets: Non assignés ({unassigned_count}) | Mes appels | Collègues
  - Bouton "Prendre l'appel" sur appels non assignés
  - Indicateurs visuels: is_mine, is_unassigned
</DepartmentQueue>
```

**Détail Appel:**
```jsx
<CallDetails>
  <CallInfo />
  <NotesTimeline>
    - Formulaire ajout note
    - Liste notes chronologique
    - Badge "Important" sur notes importantes
    - Avatar + nom de l'auteur
  </NotesTimeline>
</CallDetails>
```

---

**Version:** 2.0.0  
**Date:** 2026-02-07  
**Auteur:** Chi Samuel Apeng  
**EPIC:** EPIC-02 - Gestion des Files & Notes  
**Dernière mise à jour:** Documentation complète des endpoints de gestion de files d'attente