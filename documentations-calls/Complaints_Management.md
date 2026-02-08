# API Appels Call Center EPIC-04 - Guide d'Intégration
## Gestion des Réclamations

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

## Créer une Réclamation

**POST** `/call-center/complaints`

Enregistre une nouvelle réclamation liée à un appel avec calcul automatique du SLA (Service Level Agreement) basé sur la gravité.

**Exemple de requête:**
```bash
curl -X POST http://targetdesk-backend.test/api/v1/call-center/complaints \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "call_id": 1,
    "client_id": 5,
    "category": "produit_defectueux",
    "severity": "critique",
    "description": "Le client a reçu le mauvais produit et le livreur était impoli. Demande remboursement immédiat."
  }'
```

**Champs requis:**
- `call_id` (integer, requis) - ID de l'appel lié à la réclamation
- `category` (enum, requis) - Catégorie de la réclamation:
  - `"produit_defectueux"` - Produit défectueux
  - `"service_insatisfaisant"` - Service insatisfaisant
  - `"livraison_retard"` - Livraison en retard
  - `"facturation_erronee"` - Facturation erronée
  - `"comportement_personnel"` - Comportement du personnel
  - `"autre"` - Autre motif
- `severity` (enum, requis) - Niveau de gravité:
  - `"critique"` - Critique (SLA: 4 heures)
  - `"eleve"` - Élevé (SLA: 24 heures)
  - `"moyen"` - Moyen (SLA: 48 heures)
  - `"faible"` - Faible (SLA: 5 jours)
- `description` (string, requis) - Description détaillée de la réclamation

**Champs optionnels:**
- `client_id` (integer, optionnel) - ID du client (sinon récupéré depuis l'appel)

### Réponse de succès (201 Created)
```json
{
  "success": true,
  "message": "Réclamation enregistrée avec succès. SLA calculé.",
  "data": {
    "id": 1,
    "complaint_id": "REC-2026-0001",
    "call_id": 1,
    "client_id": 5,
    "category": "produit_defectueux",
    "severity": "critique",
    "description": "Le client a reçu le mauvais produit et le livreur était impoli. Demande remboursement immédiat.",
    "status": "ouverte",
    "sla_deadline": "2026-02-08T18:30:00.000000Z",
    "assigned_to": null,
    "created_by": 1,
    "root_cause": null,
    "actions_taken": null,
    "proposed_solution": null,
    "resolution_summary": null,
    "client_satisfaction": null,
    "compensation_details": null,
    "resolved_at": null,
    "resolved_by": null,
    "closed_at": null,
    "closed_by": null,
    "closing_comment": null,
    "created_at": "2026-02-08T14:30:00.000000Z",
    "updated_at": "2026-02-08T14:30:00.000000Z"
  }
}
```

### Calcul automatique du SLA

Le délai de traitement (SLA) est calculé automatiquement selon la gravité:

| Gravité | Délai SLA | Calcul |
|---------|-----------|--------|
| `critique` | 4 heures | `now() + 4h` |
| `eleve` | 24 heures | `now() + 24h` |
| `moyen` | 48 heures | `now() + 48h` |
| `faible` | 5 jours | `now() + 5 jours` |

### Valeurs automatiques définies

✅ **`complaint_id`** - Généré automatiquement (REC-YYYY-####)  
✅ **`status`** - Défini automatiquement à "ouverte"  
✅ **`sla_deadline`** - Calculé selon la gravité  
✅ **`created_by`** - ID de l'agent connecté  
✅ **`assigned_to`** - Défini à `null` (en attente d'assignation par un superviseur)  
✅ **Audit logging** - Création loggée avec gravité et SLA

### Cas d'usage

- Escalader un appel en réclamation formelle
- Enregistrer une plainte client nécessitant un suivi structuré
- Déclencher un processus de traitement avec SLA
- Tracer les problèmes récurrents pour analyse

### Erreurs possibles

**404 - Not Found (appel inexistant):**
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
    "call_id": ["L'ID de l'appel est obligatoire"],
    "severity": ["La gravité est obligatoire"]
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
  "message": "Erreur serveur lors de la création"
}
```

---

## Lister les Réclamations

**GET** `/call-center/complaints`

Récupère la liste des réclamations avec filtres multiples (statut, gravité, SLA dépassé, assignation).

**Exemple de requête:**
```bash
curl -X GET "http://targetdesk-backend.test/api/v1/call-center/complaints?filter[severity]=critique&filter[is_overdue]=true" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"
```

### Paramètres de requête (optionnels)

**Filtres disponibles:**
- `filter[status]` (string, optionnel) - Filtrer par statut:
  - `"ouverte"` - Réclamation ouverte
  - `"en_analyse"` - En cours d'analyse
  - `"en_attente_client"` - En attente de retour client
  - `"resolue"` - Résolue
  - `"cloture"` - Clôturée définitivement

- `filter[severity]` (string, optionnel) - Filtrer par gravité:
  - `"critique"`, `"eleve"`, `"moyen"`, `"faible"`

- `filter[assigned_to]` (string, optionnel) - Filtrer par assignation:
  - `"me"` - Mes réclamations assignées
  - `"unassigned"` - Réclamations non assignées

- `filter[is_overdue]` (boolean, optionnel) - Filtrer par dépassement SLA:
  - `true` - Uniquement les réclamations en retard

### Réponse de succès (200 OK)
```json
{
  "success": true,
  "message": "Liste des réclamations récupérée",
  "data": {
    "total": 15,
    "overdue_count": 3,
    "complaints": [
      {
        "id": 1,
        "complaint_id": "REC-2026-0001",
        "call_id": 1,
        "client_id": 5,
        "category": "produit_defectueux",
        "severity": "critique",
        "description": "Le client a reçu le mauvais produit...",
        "status": "en_analyse",
        "sla_deadline": "2026-02-08T18:30:00.000000Z",
        "assigned_to": 1,
        "created_by": 1,
        "root_cause": "Erreur de picking à l'entrepôt",
        "actions_taken": "Contacté l'entrepôt pour vérification",
        "proposed_solution": "Envoi d'un nouveau produit en express",
        "created_at": "2026-02-08T14:30:00.000000Z",
        "updated_at": "2026-02-08T16:00:00.000000Z",
        "is_overdue": true,
        "sla_text": "En retard de 2 heures",
        "client": {
          "id": 5,
          "name": "ACME Corporation",
          "code": "ACME-001"
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
        "complaint_id": "REC-2026-0005",
        "call_id": 8,
        "client_id": 12,
        "category": "facturation_erronee",
        "severity": "moyen",
        "description": "Facture avec montant incorrect",
        "status": "ouverte",
        "sla_deadline": "2026-02-10T14:30:00.000000Z",
        "assigned_to": null,
        "created_by": 2,
        "root_cause": null,
        "actions_taken": null,
        "proposed_solution": null,
        "created_at": "2026-02-08T14:30:00.000000Z",
        "updated_at": "2026-02-08T14:30:00.000000Z",
        "is_overdue": false,
        "sla_text": "Reste 1 jour 23 heures",
        "client": {
          "id": 12,
          "name": "TechCorp Solutions",
          "code": "TECH-012"
        },
        "assigned_agent": null,
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
- `data.total` (integer) - Nombre total de réclamations
- `data.overdue_count` (integer) - Nombre de réclamations en retard (SLA dépassé)

**Champs calculés par réclamation:**
- `is_overdue` (boolean) - `true` si le SLA est dépassé et statut != "resolue"/"cloture"
- `sla_text` (string) - Texte descriptif du délai:
  - `"Traité"` - Si statut = "resolue" ou "cloture"
  - `"En retard de X heures/jours"` - Si SLA dépassé
  - `"Reste X heures/jours"` - Si dans les délais

**Relations chargées:**
- client
- assigned_agent
- creator

### Tri des résultats

Les réclamations sont triées par:
1. **Gravité** (priorité décroissante): critique > élevé > moyen > faible
2. **SLA deadline** (ordre croissant): Plus urgent d'abord

### Cas d'usage

- Tableau de bord des réclamations urgentes
- Suivi des SLA et retards
- Répartition de la charge de travail
- Identification des réclamations non assignées

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

## Traiter une Réclamation

**PUT** `/call-center/complaints/{id}`

Met à jour les informations de traitement d'une réclamation: statut, assignation, cause racine, actions et solution proposée.

**Exemple de requête:**
```bash
curl -X PUT http://targetdesk-backend.test/api/v1/call-center/complaints/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "en_analyse",
    "assigned_to": 1,
    "root_cause": "Erreur de picking à l'entrepôt de Lyon",
    "actions_taken": "Contacté le responsable logistique. Vérification du stock en cours.",
    "proposed_solution": "Envoi d'un nouveau produit en express + bon d'achat 20€"
  }'
```

**Paramètres d'URL:**
- `id` (integer, requis) - ID de la réclamation

**Champs modifiables:**
- `status` (enum, optionnel) - Nouveau statut:
  - `"en_analyse"` - Analyse en cours
  - `"en_attente_client"` - En attente de retour client
- `assigned_to` (integer, optionnel) - ID de l'agent assigné
- `root_cause` (string, optionnel) - Cause racine identifiée
- `actions_taken` (string, optionnel) - Actions déjà entreprises
- `proposed_solution` (string, optionnel) - Solution proposée au client

### Réponse de succès (200 OK)
```json
{
  "success": true,
  "message": "Traitement mis à jour avec succès",
  "data": {
    "id": 1,
    "complaint_id": "REC-2026-0001",
    "call_id": 1,
    "client_id": 5,
    "category": "produit_defectueux",
    "severity": "critique",
    "description": "Le client a reçu le mauvais produit...",
    "status": "en_analyse",
    "sla_deadline": "2026-02-08T18:30:00.000000Z",
    "assigned_to": 1,
    "created_by": 1,
    "root_cause": "Erreur de picking à l'entrepôt de Lyon",
    "actions_taken": "Contacté le responsable logistique. Vérification du stock en cours.",
    "proposed_solution": "Envoi d'un nouveau produit en express + bon d'achat 20€",
    "created_at": "2026-02-08T14:30:00.000000Z",
    "updated_at": "2026-02-08T17:00:00.000000Z",
    "assigned_agent": {
      "id": 1,
      "name": "Chi Samuel Apeng",
      "email": "samuel@targetpoint.fr"
    }
  }
}
```

### Fonctionnalités automatiques

✅ **Détection de changements** - Seuls les champs modifiés sont mis à jour  
✅ **Audit logging** - Toutes les modifications sont loggées avec détails des changements  
✅ **Relations rafraîchies** - `assigned_agent` rechargé dans la réponse

### Cas d'usage

- Assigner une réclamation à un agent spécifique
- Documenter l'analyse et les actions en cours
- Proposer une solution avant résolution finale
- Mettre à jour le statut selon l'avancement

### Erreurs possibles

**404 - Not Found:**
```json
{
  "success": false,
  "message": "Réclamation non trouvée"
}
```

**422 - Validation Error:**
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "status": ["Le statut est invalide"]
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

## Résoudre une Réclamation

**POST** `/call-center/complaints/{id}/resolve`

Marque la réclamation comme résolue avec le résumé de la solution appliquée et le niveau de satisfaction client.

**Exemple de requête:**
```bash
curl -X POST http://targetdesk-backend.test/api/v1/call-center/complaints/1/resolve \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "resolution_summary": "Produit remplacé et livré en express. Client remboursé des frais de port. Bon d'achat de 20€ offert en compensation.",
    "client_satisfaction": "satisfait",
    "compensation_details": "Bon d'achat de 20€ + remboursement frais de port (15€)"
  }'
```

**Paramètres d'URL:**
- `id` (integer, requis) - ID de la réclamation

**Champs requis:**
- `resolution_summary` (string, requis) - Résumé détaillé de la résolution
- `client_satisfaction` (enum, requis) - Niveau de satisfaction du client:
  - `"satisfait"` - Client satisfait de la résolution
  - `"partiellement_satisfait"` - Client partiellement satisfait
  - `"non_satisfait"` - Client non satisfait

**Champs optionnels:**
- `compensation_details` (string, optionnel) - Détails de la compensation offerte

### Réponse de succès (200 OK)
```json
{
  "success": true,
  "message": "Réclamation résolue avec succès",
  "data": {
    "id": 1,
    "complaint_id": "REC-2026-0001",
    "call_id": 1,
    "client_id": 5,
    "category": "produit_defectueux",
    "severity": "critique",
    "description": "Le client a reçu le mauvais produit...",
    "status": "resolue",
    "sla_deadline": "2026-02-08T18:30:00.000000Z",
    "assigned_to": 1,
    "created_by": 1,
    "root_cause": "Erreur de picking à l'entrepôt de Lyon",
    "actions_taken": "Contacté le responsable logistique...",
    "proposed_solution": "Envoi d'un nouveau produit en express...",
    "resolution_summary": "Produit remplacé et livré en express. Client remboursé des frais de port. Bon d'achat de 20€ offert en compensation.",
    "client_satisfaction": "satisfait",
    "compensation_details": "Bon d'achat de 20€ + remboursement frais de port (15€)",
    "resolved_at": "2026-02-08T19:00:00.000000Z",
    "resolved_by": 1,
    "closed_at": null,
    "closed_by": null,
    "closing_comment": null,
    "created_at": "2026-02-08T14:30:00.000000Z",
    "updated_at": "2026-02-08T19:00:00.000000Z"
  }
}
```

### Changements automatiques

✅ **`status`** - Passé à "resolue"  
✅ **`resolved_at`** - Date/heure actuelle  
✅ **`resolved_by`** - ID de l'agent connecté  
✅ **Audit logging** - Résolution loggée avec niveau de satisfaction

### Règles métier

⚠️ **Validation:** La réclamation ne doit PAS déjà être clôturée  
⚠️ **Statut requis:** Aucun prérequis de statut pour résoudre (peut être ouverte, en_analyse, etc.)

### Cas d'usage

- Finaliser le traitement d'une réclamation
- Enregistrer la satisfaction client
- Documenter la compensation offerte
- Préparer la clôture par un superviseur

### Erreurs possibles

**400 - Bad Request (déjà clôturée):**
```json
{
  "success": false,
  "message": "Cette réclamation est déjà clôturée."
}
```

**404 - Not Found:**
```json
{
  "success": false,
  "message": "Réclamation non trouvée"
}
```

**422 - Validation Error:**
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "resolution_summary": ["Le résumé de résolution est obligatoire"],
    "client_satisfaction": ["Le niveau de satisfaction est obligatoire"]
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

## Clôturer une Réclamation

**POST** `/call-center/complaints/{id}/close`

Validation finale et clôture définitive par un superviseur. La réclamation doit être résolue avant de pouvoir être clôturée.

**Exemple de requête:**
```bash
curl -X POST http://targetdesk-backend.test/api/v1/call-center/complaints/1/close \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "closing_comment": "Dossier validé. Client satisfait. Procédures respectées."
  }'
```

**Paramètres d'URL:**
- `id` (integer, requis) - ID de la réclamation

**Champs optionnels:**
- `closing_comment` (string, optionnel) - Commentaire final du superviseur (défaut: "Clôturé par le superviseur.")

### Réponse de succès (200 OK)
```json
{
  "success": true,
  "message": "Réclamation clôturée définitivement",
  "data": {
    "id": 1,
    "complaint_id": "REC-2026-0001",
    "call_id": 1,
    "client_id": 5,
    "category": "produit_defectueux",
    "severity": "critique",
    "description": "Le client a reçu le mauvais produit...",
    "status": "cloture",
    "sla_deadline": "2026-02-08T18:30:00.000000Z",
    "assigned_to": 1,
    "created_by": 1,
    "root_cause": "Erreur de picking à l'entrepôt de Lyon",
    "actions_taken": "Contacté le responsable logistique...",
    "proposed_solution": "Envoi d'un nouveau produit en express...",
    "resolution_summary": "Produit remplacé et livré en express...",
    "client_satisfaction": "satisfait",
    "compensation_details": "Bon d'achat de 20€ + remboursement frais de port (15€)",
    "resolved_at": "2026-02-08T19:00:00.000000Z",
    "resolved_by": 1,
    "closed_at": "2026-02-09T10:00:00.000000Z",
    "closed_by": 2,
    "closing_comment": "Dossier validé. Client satisfait. Procédures respectées.",
    "created_at": "2026-02-08T14:30:00.000000Z",
    "updated_at": "2026-02-09T10:00:00.000000Z"
  }
}
```

### Changements automatiques

✅ **`status`** - Passé à "cloture"  
✅ **`closed_at`** - Date/heure actuelle  
✅ **`closed_by`** - ID du superviseur connecté  
✅ **`closing_comment`** - Commentaire final enregistré  
✅ **Audit logging** - Clôture loggée avec identité du superviseur

### Règles métier

⚠️ **VALIDATION CRITIQUE:** La réclamation DOIT être dans le statut "resolue"  
⚠️ **Rôle recommandé:** Typiquement réservé aux superviseurs/managers

### Workflow de clôture
```
ouverte → en_analyse → en_attente_client → resolue → cloture
                                              ↑          ↑
                                         (agent)  (superviseur)
```

### Cas d'usage

- Validation finale du dossier par un superviseur
- Archivage des réclamations traitées
- Analyse statistique des réclamations clôturées
- Audit qualité du processus de traitement

### Erreurs possibles

**400 - Bad Request (non résolue):**
```json
{
  "success": false,
  "message": "La réclamation doit être résolue avant d'être clôturée."
}
```

**404 - Not Found:**
```json
{
  "success": false,
  "message": "Réclamation non trouvée"
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

Section: **Complaints** → Tous les endpoints EPIC-04

---

## User Stories Implémentées (EPIC-04)

✅ **US-CC-021** - Créer une réclamation avec calcul SLA automatique  
✅ **US-CC-022** - Lister et filtrer les réclamations  
✅ **US-CC-023** - Traiter une réclamation (assignation, analyse, actions)  
✅ **US-CC-024** - Résoudre une réclamation avec satisfaction client  
✅ **US-CC-025** - Clôturer définitivement une réclamation

---

## Résumé des Endpoints EPIC-04

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/call-center/complaints` | Créer une réclamation |
| GET | `/call-center/complaints` | Lister les réclamations |
| PUT | `/call-center/complaints/{id}` | Traiter une réclamation |
| POST | `/call-center/complaints/{id}/resolve` | Résoudre une réclamation |
| POST | `/call-center/complaints/{id}/close` | Clôturer une réclamation |

---

## Workflow Complet - Cycle de Vie d'une Réclamation

### Scénario: De la création à la clôture

**Étape 1: Création de la réclamation (Agent)**
```bash
POST /call-center/complaints
Body: {
  "call_id": 42,
  "category": "produit_defectueux",
  "severity": "critique",
  "description": "Produit défectueux reçu, client mécontent"
}
```
→ Statut: "ouverte", SLA: 4h (critique), non assignée

**Étape 2: Assignation et analyse (Superviseur)**
```bash
PUT /call-center/complaints/1
Body: {
  "status": "en_analyse",
  "assigned_to": 5,
  "root_cause": "Erreur de production lot n°12345"
}
```
→ Assignée à l'agent 5, analyse en cours

**Étape 3: Actions et solution proposée (Agent assigné)**
```bash
PUT /call-center/complaints/1
Body: {
  "actions_taken": "Contacté le service production. Lot défectueux identifié et rappelé.",
  "proposed_solution": "Remplacement produit + bon d'achat 30€"
}
```
→ Actions documentées

**Étape 4: Attente validation client (Agent)**
```bash
PUT /call-center/complaints/1
Body: {
  "status": "en_attente_client"
}
```
→ En attente de retour client sur la solution

**Étape 5: Résolution (Agent)**
```bash
POST /call-center/complaints/1/resolve
Body: {
  "resolution_summary": "Produit remplacé. Bon d'achat 30€ envoyé. Client satisfait.",
  "client_satisfaction": "satisfait",
  "compensation_details": "Bon d'achat 30€"
}
```
→ Statut: "resolue", résolution documentée

**Étape 6: Clôture finale (Superviseur)**
```bash
POST /call-center/complaints/1/close
Body: {
  "closing_comment": "Dossier validé. Procédures respectées. Aucune action corrective nécessaire."
}
```
→ Statut: "cloture", dossier archivé

---

## Indicateurs SLA & Statistiques

### Métriques importantes à suivre

**Taux de respect du SLA:**
```
(Réclamations résolues dans les délais / Total réclamations) × 100
```

**Temps moyen de résolution:**
```
Moyenne(resolved_at - created_at)
```

**Distribution par gravité:**
- Critiques: X%
- Élevées: Y%
- Moyennes: Z%
- Faibles: W%

**Taux de satisfaction client:**
- Satisfait: X%
- Partiellement satisfait: Y%
- Non satisfait: Z%

---

## Intégration Frontend - Suggestions

### Composants suggérés

**Dashboard Réclamations:**
```jsx
<ComplaintsOverview>
  <SLAWidget>
    - Badge rouge: {overdue_count} en retard
    - Graphique: Respect SLA sur 7 jours
  </SLAWidget>
  
  <ComplaintsList>
    - Tri par gravité + SLA
    - Indicateur couleur: is_overdue
    - Badge SLA: sla_text
    - Filtres: Statut, Gravité, Assignation
  </ComplaintsList>
</ComplaintsOverview>
```

**Formulaire Traitement:**
```jsx
<ComplaintTreatmentForm>
  <StatusSelect />
  <AgentAssignment />
  <TextArea label="Cause racine" field="root_cause" />
  <TextArea label="Actions entreprises" field="actions_taken" />
  <TextArea label="Solution proposée" field="proposed_solution" />
</ComplaintTreatmentForm>
```

**Modale Résolution:**
```jsx
<ResolveComplaintModal>
  <TextArea label="Résumé de résolution" required />
  <SatisfactionRadio>
    - Satisfait
    - Partiellement satisfait
    - Non satisfait
  </SatisfactionRadio>
  <TextArea label="Détails compensation" optional />
  <SubmitButton>Marquer comme résolue</SubmitButton>
</ResolveComplaintModal>
```

---

**Version:** 2.0.0  
**Date:** 2026-02-08  
**Auteur:** Chi Samuel Apeng  
**EPIC:** EPIC-04 - Gestion des Réclamations  
**Dernière mise à jour:** Documentation complète du système de réclamations avec SLA