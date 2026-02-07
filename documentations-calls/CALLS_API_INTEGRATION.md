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

**POST** `/calls`

Enregistre un nouvel appel (entrant ou sortant) avec génération automatique d'identifiant unique.

### Appel Entrant (US-CC-001)

**Exemple de requête:**
```bash
curl -X POST http://targetdesk-backend.test/api/v1/calls \
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
curl -X POST http://targetdesk-backend.test/api/v1/calls \
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

## Documentation Swagger

La documentation interactive complète est disponible sur:
**http://targetdesk-backend.test/api/documentation**

Section: **Calls** → **POST /api/v1/calls**

---

## User Stories Implémentées

✅ **US-CC-001** - Enregistrer un appel entrant  
✅ **US-CC-002** - Enregistrer un appel sortant

---

**Version:** 1.0.0  
**Date:** 2026-02-07  
**Auteur:** Chi Samuel Apeng