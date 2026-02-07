# API Appels Call Center - Guide d'Intégration

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

## Créer un Appel (Entrant ou Sortant)

**POST** `/call-center/calls`

Enregistre un nouvel appel (entrant ou sortant) avec génération automatique d'identifiant unique.

### Appel Entrant (US-CC-001)

**Exemple de requête:**
```bash
curl -X POST http://targetdesk-backend.test/api/v1/call-center/calls \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "entrant",
    "phone_number": "0612345678",
    "caller_name": "Jean Dupont",
    "department_id": 1,
    "object": "Problème technique avec le logiciel",
    "summary": "Le client rencontre une erreur lors de la connexion au système.",
    "urgency": "urgent"
  }'
```

**Champs requis:**
- `type` (string) - "entrant"
- `phone_number` (string) - Numéro de téléphone
- `department_id` (integer) - ID du département cible
- `object` (string) - Objet/motif de l'appel
- `summary` (string) - Résumé détaillé

**Champs optionnels:**
- `caller_name` (string) - Nom de l'appelant
- `urgency` (enum) - "normal" | "urgent" | "critique" (défaut: "normal")
- `client_id` (integer) - Lien vers un client existant
- `contact_id` (integer) - Lien vers un contact existant
- `related_to_type` (enum) - "prospect" | "client" | "project" | "command"
- `related_to_id` (integer) - ID de l'entité liée

### Appel Sortant (US-CC-002)

**Exemple de requête:**
```bash
curl -X POST http://targetdesk-backend.test/api/v1/call-center/calls \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "sortant",
    "phone_number": "0687654321",
    "caller_name": "Marie Martin",
    "department_id": 2,
    "object": "Rappel client pour satisfaction",
    "summary": "L'\''agent appelle le client pour s'\''assurer que le problème a été résolu.",
    "urgency": "normal",
    "outbound_reason": "rappel_client",
    "call_result": "contacte",
    "call_duration_seconds": 180
  }'
```

**Champs requis (en plus des champs d'appel entrant):**
- `outbound_reason` (enum) - Motif de l'appel sortant:
  - "rappel_client"
  - "prospection"
  - "suivi_commande"
  - "enquete_satisfaction"
  - "relance_paiement"

**Champs optionnels spécifiques:**
- `call_result` (enum) - Résultat de l'appel:
  - "contacte"
  - "messagerie"
  - "pas_de_reponse"
  - "numero_errone"
  - "refuse"
- `call_duration_seconds` (integer) - Durée en secondes

### Réponse de succès (201 Created)
```json
{
  "success": true,
  "message": "Appel créé avec succès",
  "data": {
    "id": 1,
    "call_id": "CALL-2026-0001",
    "type": "entrant",
    "phone_number": "0612345678",
    "caller_name": "Jean Dupont",
    "department_id": 1,
    "object": "Problème technique",
    "summary": "Le client rencontre une erreur...",
    "urgency": "urgent",
    "status": "a_traiter",
    "assigned_to": 1,
    "created_by": 1,
    "created_at": "2026-02-07T13:42:43.000000Z",
    "updated_at": "2026-02-07T13:42:43.000000Z",
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
      "name": "Chi Samuel Apeng"
    }
  }
}
```

### Fonctionnalités automatiques

✅ **Génération automatique de call_id** - Format: `CALL-YYYY-####`  
✅ **Assignation automatique** - L'appel est assigné à l'agent connecté  
✅ **Statut initial** - Automatiquement défini à "a_traiter"  
✅ **Audit logging** - Toutes les créations sont loggées  
✅ **Relations chargées** - département, agent assigné, créateur inclus dans la réponse

### Erreurs possibles

**422 - Validation Error:**
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "phone_number": ["Le numéro de téléphone est obligatoire"],
    "department_id": ["Le département cible est obligatoire"]
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
  "message": "Erreur lors de la création de l'appel"
}
```

---

## Consulter les Détails d'un Appel

**GET** `/call-center/calls/{id}`

Récupère toutes les informations détaillées d'un appel spécifique avec ses relations et notes associées.

**Exemple de requête:**
```bash
curl -X GET http://targetdesk-backend.test/api/v1/call-center/calls/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"
```

**Paramètres d'URL:**
- `id` (integer, requis) - ID de l'appel

### Réponse de succès (200 OK)
```json
{
  "success": true,
  "message": "Appel trouvé",
  "data": {
    "id": 1,
    "call_id": "CALL-2026-0001",
    "type": "entrant",
    "phone_number": "0612345678",
    "caller_name": "Jean Dupont",
    "department_id": 1,
    "object": "Problème technique avec le logiciel",
    "summary": "Le client rencontre une erreur lors de la connexion au système.",
    "urgency": "urgent",
    "status": "en_cours",
    "assigned_to": 1,
    "created_by": 1,
    "closed_by": null,
    "closed_at": null,
    "resolution_summary": null,
    "final_result": null,
    "treatment_time_seconds": null,
    "created_at": "2026-02-07T10:30:00.000000Z",
    "updated_at": "2026-02-07T11:15:00.000000Z",
    "time_elapsed": "Il y a 2 heures",
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
      "id": 1,
      "name": "Chi Samuel Apeng",
      "email": "samuel@targetpoint.fr"
    },
    "closer": null,
    "notes": [
      {
        "id": 1,
        "call_id": 1,
        "note": "Client rappelé, problème identifié",
        "is_important": true,
        "created_by": 1,
        "created_at": "2026-02-07T11:00:00.000000Z",
        "creator": {
          "id": 1,
          "name": "Chi Samuel Apeng"
        }
      }
    ]
  }
}
```

### Champs retournés

**Informations de base:**
- `id` - ID de l'appel
- `call_id` - Identifiant unique généré (CALL-YYYY-####)
- `type` - "entrant" ou "sortant"
- `phone_number` - Numéro de téléphone
- `caller_name` - Nom de l'appelant
- `object` - Objet de l'appel
- `summary` - Résumé détaillé
- `urgency` - Niveau d'urgence
- `status` - Statut actuel
- `time_elapsed` - Temps écoulé depuis la création (calculé dynamiquement)

**Relations:**
- `department` - Département cible
- `client` - Client lié (si applicable)
- `contact` - Contact lié (si applicable)
- `assigned_agent` - Agent assigné
- `creator` - Créateur de l'appel
- `closer` - Agent ayant clôturé (si applicable)
- `notes` - Liste des notes avec leurs créateurs

**Informations de clôture:**
- `closed_at` - Date de clôture
- `closed_by` - ID de l'agent ayant clôturé
- `resolution_summary` - Résumé de résolution
- `final_result` - Résultat final
- `treatment_time_seconds` - Temps de traitement en secondes

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
  "message": "Erreur lors de la récupération de l'appel"
}
```

---

## Modifier un Appel

**PUT** `/call-center/calls/{id}`

Met à jour les informations d'un appel existant. Tous les changements sont loggés automatiquement.

**Exemple de requête:**
```bash
curl -X PUT http://targetdesk-backend.test/api/v1/call-center/calls/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "phone_number": "0623456789",
    "caller_name": "Jean Dupont Modifié",
    "object": "Problème résolu",
    "summary": "Le problème a été corrigé après intervention",
    "urgency": "normal",
    "department_id": 2,
    "assigned_to": 3
  }'
```

**Paramètres d'URL:**
- `id` (integer, requis) - ID de l'appel à modifier

**Champs modifiables:**
- `phone_number` (string, optionnel) - Numéro de téléphone
- `caller_name` (string, optionnel) - Nom de l'appelant
- `object` (string, optionnel) - Objet de l'appel
- `summary` (string, optionnel) - Résumé détaillé
- `urgency` (enum, optionnel) - "normal" | "urgent" | "critique"
- `department_id` (integer, optionnel) - ID du département
- `assigned_to` (integer, optionnel) - ID de l'agent assigné
- `client_id` (integer, optionnel) - ID du client lié
- `contact_id` (integer, optionnel) - ID du contact lié

### Réponse de succès (200 OK)
```json
{
  "success": true,
  "message": "Appel mis à jour avec succès",
  "data": {
    "id": 1,
    "call_id": "CALL-2026-0001",
    "type": "entrant",
    "phone_number": "0623456789",
    "caller_name": "Jean Dupont Modifié",
    "department_id": 2,
    "object": "Problème résolu",
    "summary": "Le problème a été corrigé après intervention",
    "urgency": "normal",
    "status": "en_cours",
    "assigned_to": 3,
    "created_by": 1,
    "created_at": "2026-02-07T10:30:00.000000Z",
    "updated_at": "2026-02-07T13:45:00.000000Z",
    "department": {
      "id": 2,
      "name": "Service Commercial",
      "code": "COM"
    },
    "assigned_agent": {
      "id": 3,
      "name": "Marie Dubois",
      "email": "marie@targetpoint.fr"
    },
    "creator": {
      "id": 1,
      "name": "Chi Samuel Apeng",
      "email": "samuel@targetpoint.fr"
    }
  }
}
```

### Fonctionnalités automatiques

✅ **Audit logging** - Tous les changements sont loggés avec détails  
✅ **Horodatage** - `updated_at` mis à jour automatiquement  
✅ **Relations chargées** - département, agent assigné, créateur inclus dans la réponse

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
    "urgency": ["L'urgence doit être: normal, urgent ou critique"]
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
  "message": "Erreur lors de la modification de l'appel"
}
```

---

## Changer le Statut d'un Appel

**PUT** `/call-center/calls/{id}/status`

Met à jour le statut d'un appel et enregistre automatiquement l'historique des changements de statut.

**Exemple de requête:**
```bash
curl -X PUT http://targetdesk-backend.test/api/v1/call-center/calls/1/status \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "en_cours",
    "comment": "Agent commence le traitement du problème technique"
  }'
```

**Paramètres d'URL:**
- `id` (integer, requis) - ID de l'appel

**Champs requis:**
- `status` (enum, requis) - Nouveau statut:
  - "a_traiter" - À traiter
  - "en_cours" - En cours de traitement
  - "en_attente" - En attente (client ou info)
  - "a_rappeler" - À rappeler
  - "resolu" - Résolu
  - "cloture" - Clôturé
  - "annule" - Annulé

**Champs optionnels:**
- `comment` (string, optionnel) - Commentaire expliquant le changement de statut

### Réponse de succès (200 OK)
```json
{
  "success": true,
  "message": "Statut changé avec succès",
  "data": {
    "id": 1,
    "call_id": "CALL-2026-0001",
    "type": "entrant",
    "phone_number": "0612345678",
    "caller_name": "Jean Dupont",
    "department_id": 1,
    "object": "Problème technique",
    "summary": "Le client rencontre une erreur...",
    "urgency": "urgent",
    "status": "en_cours",
    "assigned_to": 1,
    "created_by": 1,
    "created_at": "2026-02-07T10:30:00.000000Z",
    "updated_at": "2026-02-07T14:00:00.000000Z",
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
      "name": "Chi Samuel Apeng"
    }
  }
}
```

### Fonctionnalités automatiques

✅ **Historique de statut** - Chaque changement est enregistré dans `call_status_histories`  
✅ **Validation** - Empêche le changement vers le même statut  
✅ **Audit logging** - Tous les changements sont loggés avec ancien/nouveau statut  
✅ **Horodatage** - `updated_at` mis à jour automatiquement

### Erreurs possibles

**400 - Bad Request (même statut):**
```json
{
  "success": false,
  "message": "Le statut est déjà \"en_cours\""
}
```

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
    "status": ["Le statut sélectionné est invalide"]
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
  "message": "Erreur lors du changement de statut"
}
```

---

## Clôturer un Appel

**POST** `/call-center/calls/{id}/close`

Marque un appel comme clôturé avec résumé de résolution et calcul automatique du temps de traitement.

**Exemple de requête:**
```bash
curl -X POST http://targetdesk-backend.test/api/v1/call-center/calls/1/close \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "resolution_summary": "Problème résolu par réinitialisation du mot de passe. Client peut maintenant se connecter sans erreur.",
    "final_result": "resolu_satisfait"
  }'
```

**Paramètres d'URL:**
- `id` (integer, requis) - ID de l'appel à clôturer

**Champs requis:**
- `resolution_summary` (string, requis) - Résumé détaillé de la résolution
- `final_result` (enum, requis) - Résultat final:
  - "resolu_satisfait" - Résolu avec client satisfait
  - "resolu_insatisfait" - Résolu mais client insatisfait
  - "transfere" - Transféré à un autre service
  - "non_resolu" - Non résolu

### Réponse de succès (200 OK)
```json
{
  "success": true,
  "message": "Appel clôturé avec succès",
  "data": {
    "id": 1,
    "call_id": "CALL-2026-0001",
    "type": "entrant",
    "phone_number": "0612345678",
    "caller_name": "Jean Dupont",
    "department_id": 1,
    "object": "Problème technique",
    "summary": "Le client rencontre une erreur...",
    "urgency": "urgent",
    "status": "cloture",
    "assigned_to": 1,
    "created_by": 1,
    "closed_by": 1,
    "closed_at": "2026-02-07T15:30:00.000000Z",
    "resolution_summary": "Problème résolu par réinitialisation du mot de passe. Client peut maintenant se connecter sans erreur.",
    "final_result": "resolu_satisfait",
    "treatment_time_seconds": 18000,
    "created_at": "2026-02-07T10:30:00.000000Z",
    "updated_at": "2026-02-07T15:30:00.000000Z",
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
      "name": "Chi Samuel Apeng"
    },
    "closer": {
      "id": 1,
      "name": "Chi Samuel Apeng",
      "email": "samuel@targetpoint.fr"
    }
  }
}
```

### Fonctionnalités automatiques

✅ **Statut automatique** - Statut changé vers "cloture"  
✅ **Calcul du temps** - `treatment_time_seconds` calculé automatiquement (différence entre `created_at` et `closed_at`)  
✅ **Métadonnées** - `closed_by` et `closed_at` enregistrés automatiquement  
✅ **Audit logging** - Clôture loggée avec résultat final et temps de traitement  
✅ **Relations chargées** - Toutes les relations incluant `closer`

### Erreurs possibles

**400 - Bad Request (déjà clôturé):**
```json
{
  "success": false,
  "message": "Cet appel est déjà clôturé"
}
```

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
    "resolution_summary": ["Le résumé de résolution est obligatoire"],
    "final_result": ["Le résultat final est obligatoire"]
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
  "message": "Erreur lors de la clôture de l'appel"
}
```

---

## Rechercher des Appels

**GET** `/call-center/calls/search`

Recherche globale dans les appels par ID, téléphone, nom d'appelant, objet ou résumé. Limite de 20 résultats.

**Exemple de requête:**
```bash
curl -X GET "http://targetdesk-backend.test/api/v1/call-center/calls/search?q=Jean" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"
```

**Paramètres de requête:**
- `q` (string, requis) - Terme de recherche (minimum 1 caractère)

### Champs recherchés automatiquement:
- `call_id` - Identifiant unique de l'appel
- `phone_number` - Numéro de téléphone
- `caller_name` - Nom de l'appelant
- `object` - Objet de l'appel
- `summary` - Résumé de l'appel

### Réponse de succès (200 OK)
```json
{
  "success": true,
  "message": "3 résultat(s) trouvé(s)",
  "data": [
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
      "updated_at": "2026-02-07T14:00:00.000000Z",
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
        "name": "Chi Samuel Apeng"
      }
    },
    {
      "id": 5,
      "call_id": "CALL-2026-0005",
      "type": "sortant",
      "phone_number": "0698765432",
      "caller_name": "Jean Martin",
      "object": "Rappel satisfaction",
      "summary": "Rappel pour enquête de satisfaction",
      "urgency": "normal",
      "status": "cloture",
      "assigned_to": 2,
      "created_by": 2,
      "created_at": "2026-02-06T15:00:00.000000Z",
      "updated_at": "2026-02-06T16:30:00.000000Z",
      "department": {
        "id": 2,
        "name": "Service Commercial",
        "code": "COM"
      },
      "assigned_agent": {
        "id": 2,
        "name": "Marie Dubois",
        "email": "marie@targetpoint.fr"
      },
      "creator": {
        "id": 2,
        "name": "Marie Dubois"
      }
    }
  ]
}
```

### Fonctionnalités automatiques

✅ **Recherche insensible à la casse** - Recherche LIKE avec %  
✅ **Recherche multi-champs** - Cherche dans 5 champs simultanément  
✅ **Tri par date** - Résultats triés du plus récent au plus ancien  
✅ **Limite de résultats** - Maximum 20 résultats pour performances  
✅ **Relations chargées** - département, agent assigné, créateur inclus  
✅ **Audit logging** - Toutes les recherches sont loggées

### Erreurs possibles

**422 - Validation Error (terme manquant):**
```json
{
  "success": false,
  "message": "Le terme de recherche est obligatoire"
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
  "message": "Erreur lors de la recherche"
}
```

---

## Lister et Filtrer les Appels

**GET** `/call-center/calls`

Récupère la liste des appels avec filtres optionnels multiples. Permet de combiner plusieurs critères de filtrage.

**Exemple de requête (filtrage multiple):**
```bash
curl -X GET "http://targetdesk-backend.test/api/v1/call-center/calls?filter[type]=entrant&filter[status]=en_cours&filter[urgency]=urgent&filter[assigned_to]=me&filter[period]=today" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"
```

### Filtres disponibles

**Par type d'appel:**
- `filter[type]` (enum, optionnel) - "entrant" | "sortant"

**Par statut:**
- `filter[status]` (enum, optionnel) - "a_traiter" | "en_cours" | "en_attente" | "a_rappeler" | "resolu" | "cloture" | "annule"

**Par département:**
- `filter[department_id]` (integer, optionnel) - ID du département

**Par urgence:**
- `filter[urgency]` (enum, optionnel) - "normal" | "urgent" | "critique"

**Par assignation:**
- `filter[assigned_to]` (string/integer, optionnel):
  - `"me"` - Mes appels assignés
  - `"unassigned"` - Appels non assignés
  - `{agent_id}` - Appels assignés à un agent spécifique (integer)

**Par période:**
- `filter[period]` (enum, optionnel):
  - `"today"` - Appels d'aujourd'hui
  - `"week"` - Appels de la semaine en cours
  - `"month"` - Appels du mois en cours
  - `"custom"` - Période personnalisée (nécessite `date_from` et/ou `date_to`)

**Période personnalisée (si `filter[period]=custom`):**
- `filter[date_from]` (date, optionnel) - Date de début (format: YYYY-MM-DD)
- `filter[date_to]` (date, optionnel) - Date de fin (format: YYYY-MM-DD)

### Exemples de requêtes

**Mes appels urgents d'aujourd'hui:**
```bash
curl -X GET "http://targetdesk-backend.test/api/v1/call-center/calls?filter[assigned_to]=me&filter[urgency]=urgent&filter[period]=today" \
  -H "Authorization: Bearer {token}"
```

**Appels entrants non assignés:**
```bash
curl -X GET "http://targetdesk-backend.test/api/v1/call-center/calls?filter[type]=entrant&filter[assigned_to]=unassigned" \
  -H "Authorization: Bearer {token}"
```

**Appels du département Support entre deux dates:**
```bash
curl -X GET "http://targetdesk-backend.test/api/v1/call-center/calls?filter[department_id]=1&filter[period]=custom&filter[date_from]=2026-02-01&filter[date_to]=2026-02-07" \
  -H "Authorization: Bearer {token}"
```

### Réponse de succès (200 OK)
```json
{
  "success": true,
  "message": "Liste des appels récupérée",
  "data": {
    "total": 5,
    "filters_applied": {
      "type": "entrant",
      "status": "en_cours",
      "urgency": "urgent",
      "assigned_to": "me",
      "period": "today"
    },
    "calls": [
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
        "updated_at": "2026-02-07T14:00:00.000000Z",
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
          "name": "Chi Samuel Apeng"
        }
      },
      {
        "id": 3,
        "call_id": "CALL-2026-0003",
        "type": "entrant",
        "phone_number": "0676543210",
        "caller_name": "Sophie Laurent",
        "object": "Bug critique",
        "summary": "Système ne répond plus",
        "urgency": "urgent",
        "status": "en_cours",
        "assigned_to": 1,
        "created_by": 1,
        "created_at": "2026-02-07T12:15:00.000000Z",
        "updated_at": "2026-02-07T13:30:00.000000Z",
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
          "name": "Chi Samuel Apeng"
        }
      }
    ]
  }
}
```

### Structure de la réponse

**`data.total`** (integer) - Nombre total de résultats  
**`data.filters_applied`** (object) - Filtres qui ont été appliqués  
**`data.calls`** (array) - Liste des appels avec relations chargées

### Fonctionnalités automatiques

✅ **Combinaison de filtres** - Tous les filtres peuvent être combinés  
✅ **Tri chronologique** - Résultats triés du plus récent au plus ancien  
✅ **Relations chargées** - département, agent assigné, créateur inclus  
✅ **Audit logging** - Toutes les requêtes filtrées sont loggées  
✅ **Filtres intelligents** - Validation automatique des valeurs de filtres

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
  "message": "Erreur lors de la récupération des appels"
}
```

---

## Documentation Swagger

La documentation interactive complète est disponible sur:
**http://targetdesk-backend.test/api/documentation**

Section: **Calls** → Tous les endpoints listés ci-dessus

---

## User Stories Implémentées

✅ **US-CC-001** - Enregistrer un appel entrant  
✅ **US-CC-002** - Enregistrer un appel sortant  
✅ **US-CC-003** - Consulter les détails d'un appel  
✅ **US-CC-004** - Modifier un appel  
✅ **US-CC-005** - Changer le statut d'un appel  
✅ **US-CC-006** - Clôturer un appel  
✅ **US-CC-007** - Rechercher des appels  
✅ **US-CC-008** - Lister et filtrer les appels

---

## Résumé des Endpoints

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/call-center/calls` | Créer un appel (entrant ou sortant) |
| GET | `/call-center/calls/{id}` | Consulter les détails d'un appel |
| PUT | `/call-center/calls/{id}` | Modifier un appel |
| PUT | `/call-center/calls/{id}/status` | Changer le statut d'un appel |
| POST | `/call-center/calls/{id}/close` | Clôturer un appel |
| GET | `/call-center/calls/search` | Rechercher des appels |
| GET | `/call-center/calls` | Lister et filtrer les appels |

---

**Version:** 2.0.0  
**Date:** 2026-02-07  
**Auteur:** Chi Samuel Apeng  
**Dernière mise à jour:** Documentation complète de tous les endpoints Call Center