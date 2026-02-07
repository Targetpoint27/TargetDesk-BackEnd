# API Appels Call Center EPIC-03 - Guide d'Intégration
## Gestion des Rappels & Appels Manqués

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

## Enregistrer un Appel Manqué

**POST** `/call-center/calls/missed`

Enregistre un appel entrant manqué pour rappel ultérieur. L'appel est automatiquement défini avec le statut "a_rappeler" et l'urgence "urgent".

**Exemple de requête:**
```bash
curl -X POST http://targetdesk-backend.test/api/v1/call-center/calls/missed \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "phone_number": "0612345678",
    "department_id": 1,
    "caller_name": "Client Inconnu",
    "notes": "A appelé pendant la pause déjeuner"
  }'
```

**Champs requis:**
- `phone_number` (string, requis) - Numéro de téléphone de l'appelant
- `department_id` (integer, requis) - ID du département cible

**Champs optionnels:**
- `caller_name` (string, optionnel) - Nom de l'appelant (défaut: "Inconnu")
- `notes` (string, optionnel) - Notes sur l'appel manqué
- `client_id` (integer, optionnel) - ID du client si identifié

### Réponse de succès (201 Created)
```json
{
  "success": true,
  "message": "Appel manqué enregistré avec succès",
  "data": {
    "id": 42,
    "call_id": "CALL-2026-0042",
    "type": "entrant",
    "phone_number": "0612345678",
    "caller_name": "Client Inconnu",
    "department_id": 1,
    "object": "Appel Manqué",
    "summary": "A appelé pendant la pause déjeuner",
    "urgency": "urgent",
    "status": "a_rappeler",
    "assigned_to": null,
    "created_by": 1,
    "scheduled_callback_date": "2026-02-08",
    "scheduled_callback_time": "14:30:00",
    "created_at": "2026-02-08T14:30:00.000000Z",
    "updated_at": "2026-02-08T14:30:00.000000Z",
    "department": {
      "id": 1,
      "name": "Support Technique",
      "code": "SUP"
    }
  }
}
```

### Valeurs automatiques définies

✅ **`call_id`** - Généré automatiquement (CALL-YYYY-####)  
✅ **`type`** - Défini automatiquement à "entrant"  
✅ **`status`** - Défini automatiquement à "a_rappeler"  
✅ **`urgency`** - Défini automatiquement à "urgent"  
✅ **`object`** - Défini automatiquement à "Appel Manqué"  
✅ **`assigned_to`** - Défini à `null` (appel non assigné, disponible pour l'équipe)  
✅ **`created_by`** - ID de l'agent connecté  
✅ **`scheduled_callback_date`** - Date du jour  
✅ **`scheduled_callback_time`** - Heure actuelle  
✅ **Audit logging** - Enregistrement loggé avec département et créateur

### Cas d'usage

- Enregistrer rapidement un appel manqué pendant une absence
- Créer un rappel pour l'équipe sans assignation spécifique
- Documenter les tentatives de contact des clients
- Alimenter la file des rappels à effectuer

### Erreurs possibles

**422 - Validation Error:**
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "phone_number": ["Le numéro de téléphone est obligatoire"],
    "department_id": ["Le département est obligatoire"]
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
  "message": "Erreur lors de l'enregistrement"
}
```

---

## Liste des Rappels à Effectuer

**GET** `/call-center/calls/callbacks`

Récupère la liste des appels avec le statut "a_rappeler" assignés à l'agent connecté OU non assignés dans son département. Triés par date de rappel (les plus anciens en premier).

**Exemple de requête:**
```bash
curl -X GET "http://targetdesk-backend.test/api/v1/call-center/calls/callbacks?filter[period]=overdue" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"
```

### Paramètres de requête (optionnels)

**Filtrage par période:**
- `filter[period]` (enum, optionnel):
  - `"overdue"` - Rappels en retard (date/heure passée)
  - `"today"` - Rappels prévus aujourd'hui
  - `"future"` - Rappels prévus dans le futur

### Réponse de succès (200 OK)
```json
{
  "success": true,
  "message": "Liste des rappels récupérée",
  "data": {
    "total": 8,
    "overdue_count": 3,
    "calls": [
      {
        "id": 42,
        "call_id": "CALL-2026-0042",
        "type": "entrant",
        "phone_number": "0612345678",
        "caller_name": "Jean Dupont",
        "object": "Appel Manqué",
        "summary": "Client a appelé pour problème facturation",
        "urgency": "urgent",
        "status": "a_rappeler",
        "assigned_to": 1,
        "created_by": 2,
        "scheduled_callback_date": "2026-02-07",
        "scheduled_callback_time": "10:00:00",
        "callback_reason": "Client en réunion",
        "callback_notes": "Préparer dossier facturation avant rappel",
        "callback_attempts": 2,
        "last_callback_at": "2026-02-07T16:30:00.000000Z",
        "created_at": "2026-02-07T09:00:00.000000Z",
        "updated_at": "2026-02-07T16:30:00.000000Z",
        "is_overdue": true,
        "time_until": "Il y a 1 jour",
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
          "id": 2,
          "name": "Pierre Martin",
          "email": "pierre@targetpoint.fr"
        }
      },
      {
        "id": 45,
        "call_id": "CALL-2026-0045",
        "type": "entrant",
        "phone_number": "0698765432",
        "caller_name": "Marie Dubois",
        "object": "Appel Manqué",
        "summary": "Demande de devis",
        "urgency": "urgent",
        "status": "a_rappeler",
        "assigned_to": null,
        "created_by": 3,
        "scheduled_callback_date": "2026-02-08",
        "scheduled_callback_time": "14:00:00",
        "callback_reason": null,
        "callback_notes": null,
        "callback_attempts": 0,
        "last_callback_at": null,
        "created_at": "2026-02-08T10:00:00.000000Z",
        "updated_at": "2026-02-08T10:00:00.000000Z",
        "is_overdue": false,
        "time_until": "Dans 2 heures",
        "department": {
          "id": 1,
          "name": "Support Technique",
          "code": "SUP"
        },
        "client": null,
        "contact": null,
        "creator": {
          "id": 3,
          "name": "Sophie Laurent",
          "email": "sophie@targetpoint.fr"
        }
      }
    ]
  }
}
```

### Structure de la réponse

**Statistiques:**
- `data.total` (integer) - Nombre total de rappels
- `data.overdue_count` (integer) - Nombre de rappels en retard

**Champs calculés par appel:**
- `is_overdue` (boolean) - `true` si la date/heure de rappel est passée
- `time_until` (string) - Temps relatif jusqu'au rappel ("Dans 2 heures" ou "Il y a 1 jour")

**Champs de rappel:**
- `scheduled_callback_date` - Date prévue du rappel
- `scheduled_callback_time` - Heure prévue du rappel
- `callback_reason` - Motif du rappel
- `callback_notes` - Notes pour le rappel
- `callback_attempts` - Nombre de tentatives de rappel effectuées
- `last_callback_at` - Date/heure de la dernière tentative

**Relations chargées:**
- department
- client
- contact
- creator

### Tri des résultats

✅ **Date de rappel** (ordre croissant) - Plus anciens en premier  
✅ **Heure de rappel** (ordre croissant) - Permet de prioriser les rappels urgents

### Logique de visibilité

L'agent voit:
- **Ses propres rappels** (`assigned_to` = agent connecté)
- **Rappels non assignés de son département** (`assigned_to` = null ET `department_id` = département de l'agent)

### Cas d'usage

- Afficher la liste des rappels à effectuer pour la journée
- Prioriser les rappels en retard
- Gérer sa charge de travail de rappels
- Voir les rappels disponibles (non assignés) à prendre

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
  "message": "Erreur serveur"
}
```

---

## Programmer un Rappel

**PUT** `/call-center/calls/{id}/schedule`

Programme une date et une heure de rappel pour un appel existant et passe automatiquement le statut à "a_rappeler".

**Exemple de requête:**
```bash
curl -X PUT http://targetdesk-backend.test/api/v1/call-center/calls/15/schedule \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "date": "2026-02-10",
    "time": "14:30",
    "reason": "Client en réunion toute la journée",
    "notes": "Préparer le dossier technique avant rappel"
  }'
```

**Paramètres d'URL:**
- `id` (integer, requis) - ID de l'appel à programmer

**Champs requis:**
- `date` (string, requis) - Date du rappel (format: YYYY-MM-DD)
- `time` (string, requis) - Heure du rappel (format: HH:MM)

**Champs optionnels:**
- `reason` (string, optionnel) - Motif du rappel programmé
- `notes` (string, optionnel) - Notes ou instructions pour le rappel

### Réponse de succès (200 OK)
```json
{
  "success": true,
  "message": "Rappel programmé avec succès",
  "data": {
    "id": 15,
    "call_id": "CALL-2026-0015",
    "type": "entrant",
    "phone_number": "0612345678",
    "caller_name": "Jean Dupont",
    "object": "Problème technique",
    "summary": "Client rencontre une erreur...",
    "urgency": "urgent",
    "status": "a_rappeler",
    "assigned_to": 1,
    "created_by": 1,
    "scheduled_callback_date": "2026-02-10",
    "scheduled_callback_time": "14:30:00",
    "callback_reason": "Client en réunion toute la journée",
    "callback_notes": "Préparer le dossier technique avant rappel",
    "callback_attempts": 0,
    "created_at": "2026-02-08T10:00:00.000000Z",
    "updated_at": "2026-02-08T15:00:00.000000Z"
  }
}
```

### Changements automatiques

✅ **`status`** - Forcé à "a_rappeler" (requis par l'US)  
✅ **`scheduled_callback_date`** - Date programmée  
✅ **`scheduled_callback_time`** - Heure programmée  
✅ **`callback_reason`** - Motif enregistré  
✅ **`callback_notes`** - Notes enregistrées  
✅ **`assigned_to`** - Conservé (l'agent garde la propriété de l'appel)  
✅ **Historique de statut** - Enregistré automatiquement avec commentaire détaillé  
✅ **Audit logging** - Action loggée avec date/heure et utilisateur

### Cas d'usage

- Reporter un rappel à une date ultérieure
- Planifier un rappel suite à une demande client
- Organiser sa charge de travail future
- Respecter les disponibilités du client

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
    "date": ["La date est obligatoire"],
    "time": ["L'heure est obligatoire"]
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
  "message": "Erreur serveur"
}
```

---

## Enregistrer Résultat de Rappel

**POST** `/call-center/calls/{id}/callback-result`

Enregistre le résultat d'une tentative de rappel. Selon le résultat, le statut de l'appel change automatiquement.

**Exemple de requête:**
```bash
curl -X POST http://targetdesk-backend.test/api/v1/call-center/calls/42/callback-result \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "call_result": "contacte",
    "summary": "Client joint. Problème résolu par réinitialisation du mot de passe.",
    "reschedule_date": null,
    "reschedule_time": null
  }'
```

**Exemple avec reprogrammation:**
```bash
curl -X POST http://targetdesk-backend.test/api/v1/call-center/calls/42/callback-result \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "call_result": "pas_de_reponse",
    "summary": "Pas de réponse. Client probablement en déplacement.",
    "reschedule_date": "2026-02-09",
    "reschedule_time": "10:00"
  }'
```

**Paramètres d'URL:**
- `id` (integer, requis) - ID de l'appel

**Champs requis:**
- `call_result` (enum, requis) - Résultat de la tentative:
  - `"contacte"` - Client contacté avec succès
  - `"messagerie"` - Message laissé sur répondeur
  - `"pas_de_reponse"` - Pas de réponse
- `summary` (string, requis) - Résumé de la tentative de rappel

**Champs optionnels (reprogrammation):**
- `reschedule_date` (string, optionnel) - Nouvelle date de rappel (format: YYYY-MM-DD)
- `reschedule_time` (string, optionnel) - Nouvelle heure de rappel (format: HH:MM)

### Réponse de succès (200 OK)
```json
{
  "success": true,
  "message": "Résultat enregistré avec succès",
  "data": {
    "id": 42,
    "call_id": "CALL-2026-0042",
    "type": "entrant",
    "phone_number": "0612345678",
    "caller_name": "Jean Dupont",
    "object": "Appel Manqué",
    "summary": "Client a appelé pour problème facturation",
    "urgency": "urgent",
    "status": "en_cours",
    "assigned_to": 1,
    "created_by": 2,
    "scheduled_callback_date": null,
    "scheduled_callback_time": null,
    "callback_attempts": 3,
    "last_callback_at": "2026-02-08T16:00:00.000000Z",
    "created_at": "2026-02-07T09:00:00.000000Z",
    "updated_at": "2026-02-08T16:00:00.000000Z"
  }
}
```

### Logique automatique

**1. Compteur de tentatives:**
✅ **`callback_attempts`** - Incrémenté automatiquement (+1)  
✅ **`last_callback_at`** - Mis à jour avec date/heure actuelle

**2. Changement de statut selon résultat:**

| Résultat | Nouveau statut | Actions automatiques |
|----------|----------------|----------------------|
| `contacte` | `en_cours` | - Passe en traitement<br>- Si non assigné → assigné à l'agent<br>- Efface la programmation de rappel |
| `messagerie` | `a_rappeler` (reste) | - Garde le statut actuel<br>- Si reprogrammé → nouvelle date/heure<br>- Sinon garde l'ancienne programmation |
| `pas_de_reponse` | `a_rappeler` (reste) | - Garde le statut actuel<br>- Si reprogrammé → nouvelle date/heure<br>- Sinon garde l'ancienne programmation |

**3. Reprogrammation (snooze):**
- Si `reschedule_date` et `reschedule_time` fournis → nouvelle programmation
- Statut reste "a_rappeler"
- L'appel réapparaît dans la liste des rappels à la nouvelle date

**4. Note automatique:**
✅ Une note est automatiquement créée avec le format:
```
Tentative de rappel: Contacte
[Summary fourni par l'agent]
```

**5. Historique de statut:**
✅ Enregistré si le statut change (ex: "a_rappeler" → "en_cours")

### Cas d'usage

**Scénario 1: Client contacté avec succès**
```json
{
  "call_result": "contacte",
  "summary": "Client joint. On avance sur le dossier."
}
```
→ Statut passe à "en_cours", appel assigné à l'agent, programmation effacée

**Scénario 2: Pas de réponse, reporter à demain**
```json
{
  "call_result": "pas_de_reponse",
  "summary": "Client ne répond pas. Probablement en congé.",
  "reschedule_date": "2026-02-09",
  "reschedule_time": "14:00"
}
```
→ Reste "a_rappeler", nouvelle tentative programmée demain 14h

**Scénario 3: Message laissé**
```json
{
  "call_result": "messagerie",
  "summary": "Message vocal laissé. En attente de retour client."
}
```
→ Reste "a_rappeler" avec même programmation

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
    "call_result": ["Le résultat est obligatoire"],
    "summary": ["Le résumé est obligatoire"]
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
  "message": "Erreur serveur"
}
```

---

## Documentation Swagger

La documentation interactive complète est disponible sur:
**http://targetdesk-backend.test/api/documentation**

Section: **Calls** → Tous les endpoints EPIC-03

---

## User Stories Implémentées (EPIC-03)

✅ **US-CC-017** - Enregistrer un appel manqué  
✅ **US-CC-018** - Consulter la liste des rappels à effectuer  
✅ **US-CC-019** - Programmer un rappel  
✅ **US-CC-020** - Enregistrer le résultat d'un rappel

---

## Résumé des Endpoints EPIC-03

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/call-center/calls/missed` | Enregistrer un appel manqué |
| GET | `/call-center/calls/callbacks` | Liste des rappels à effectuer |
| PUT | `/call-center/calls/{id}/schedule` | Programmer un rappel |
| POST | `/call-center/calls/{id}/callback-result` | Enregistrer résultat de rappel |

---

## Workflow Complet - Gestion d'un Appel Manqué

### Scénario: Depuis l'enregistrement jusqu'à la résolution

**Étape 1: Enregistrer l'appel manqué**
```bash
POST /call-center/calls/missed
Body: {
  "phone_number": "0612345678",
  "department_id": 1,
  "caller_name": "Jean Dupont",
  "notes": "A appelé pendant la pause déjeuner"
}
```
→ Appel créé avec statut "a_rappeler", non assigné, programmé pour aujourd'hui

**Étape 2: Agent consulte la liste des rappels**
```bash
GET /call-center/calls/callbacks?filter[period]=today
```
→ Voit l'appel manqué dans sa liste

**Étape 3: Agent s'auto-assigne l'appel**
```bash
POST /call-center/calls/42/assign-to-me
```
→ Appel maintenant assigné à l'agent

**Étape 4: Première tentative - pas de réponse**
```bash
POST /call-center/calls/42/callback-result
Body: {
  "call_result": "pas_de_reponse",
  "summary": "Client ne répond pas, probablement en réunion",
  "reschedule_date": "2026-02-08",
  "reschedule_time": "16:00"
}
```
→ Reprogrammé pour 16h, `callback_attempts` = 1

**Étape 5: Deuxième tentative - client contacté**
```bash
POST /call-center/calls/42/callback-result
Body: {
  "call_result": "contacte",
  "summary": "Client joint. Problème résolu."
}
```
→ Statut passe à "en_cours", programmation effacée, `callback_attempts` = 2

**Étape 6: Ajouter des notes de suivi**
```bash
POST /call-center/calls/42/notes
Body: {
  "note": "Problème résolu. Client satisfait.",
  "is_important": true
}
```

**Étape 7: Clôturer l'appel**
```bash
POST /call-center/calls/42/close
Body: {
  "resolution_summary": "Appel manqué traité avec succès.",
  "final_result": "resolu_satisfait"
}
```

---

## Intégration Frontend - Suggestions

### Composants suggérés

**Widget Rappels:**
```jsx
<CallbacksWidget>
  - Badge: {overdue_count} rappels en retard
  - Onglets: En retard | Aujourd'hui | À venir
  - Indicateur is_overdue avec couleur
  - Bouton "Appeler maintenant"
</CallbacksWidget>
```

**Modale Résultat Rappel:**
```jsx
<CallbackResultModal>
  <ResultSelector>
    - Radio: Contacté / Messagerie / Pas de réponse
  </ResultSelector>
  <SummaryTextarea />
  {result !== 'contacte' && (
    <RescheduleSection>
      - DatePicker
      - TimePicker
    </RescheduleSection>
  )}
  <SubmitButton />
</CallbackResultModal>
```

**Formulaire Appel Manqué:**
```jsx
<MissedCallForm>
  <PhoneInput required />
  <DepartmentSelect required />
  <CallerNameInput />
  <NotesTextarea />
  <QuickSaveButton />
</MissedCallForm>
```

---

**Version:** 2.0.0  
**Date:** 2026-02-08  
**Auteur:** Chi Samuel Apeng  
**EPIC:** EPIC-03 - Gestion des Rappels & Appels Manqués  
**Dernière mise à jour:** Documentation complète des endpoints de rappels