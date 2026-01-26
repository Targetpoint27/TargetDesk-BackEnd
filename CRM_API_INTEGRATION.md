# Documentation d'intégration API CRM - TargetDesk

## Vue d'ensemble
Cette documentation présente l'intégration complète des fonctionnalités CRM de TargetDesk : notes client, journal d'appels, rendez-vous et timeline unifiée.

**✅ Dernière mise à jour :** 21 janvier 2026
**🚀 Version API :** v1.1
**📊 Endpoints disponibles :** 35+ endpoints CRM complets

## ⚡ Statut des tests
- ✅ **Notes client** : Tous les endpoints testés et fonctionnels
- ✅ **Journal d'appels** : CRUD complet implémenté
- ✅ **Rendez-vous** : Endpoint corrigé, création/modification/statuts OK
- ✅ **Timeline unifiée** : Auto-synchronisation validée + 6 types d'interactions
- ✅ **Dashboard** : Statistiques et vues d'ensemble opérationnelles
- ✅ **Export timeline** : CSV/Excel avec liens détails automatiques
- 🆕 **Emails** : Modèles et relations créés (endpoints à venir)
- 🆕 **Opportunités** : Structure complète avec pipeline commercial
- 🆕 **Audit logs** : Traçabilité automatique des modifications

## Authentification
Toutes les requêtes nécessitent un token Bearer dans l'en-tête Authorization :
```
Authorization: Bearer {your_token}
```

---

## 📝 GESTION DES NOTES CLIENT

### 1. Lister les notes d'un client
```http
GET /api/v1/clients/{clientId}/notes
```

**Paramètres de requête :**
- `type` (optionnel) : `normal`, `important`, `private`
- `pinned_only` (optionnel) : `true`/`false`
- `per_page` (optionnel) : nombre d'éléments par page (défaut: 20)

**Exemple de réponse :**
```json
{
  "success": true,
  "message": "Notes récupérées",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "title": "Note de test",
        "content": "Contenu de la note",
        "type": "normal",
        "is_pinned": false,
        "attachments_count": 0,
        "created_at": "2026-01-20T07:59:04.000000Z",
        "user": {
          "id": 3,
          "name": "Bescovic rochnel Tegomo"
        },
        "attachments": []
      }
    ],
    "total": 1
  }
}
```

### 2. Créer une note
```http
POST /api/v1/clients/{clientId}/notes
```

**Body :**
```json
{
  "title": "Titre de la note",
  "content": "Contenu de la note (HTML supporté)",
  "type": "normal", // normal, important, private
  "is_pinned": false
}
```

### 3. Voir une note spécifique
```http
GET /api/v1/notes/{noteId}
```

### 4. Modifier une note
```http
PUT /api/v1/notes/{noteId}
```

### 5. Supprimer une note
```http
DELETE /api/v1/notes/{noteId}
```

### 6. Épingler/désépingler une note
```http
POST /api/v1/notes/{noteId}/pin
```

### 7. Ajouter un fichier joint
```http
POST /api/v1/notes/{noteId}/attachments
Content-Type: multipart/form-data
```

**Form data :**
- `file` : fichier (max 10MB)
- `description` (optionnel) : description du fichier

### 8. Supprimer un fichier joint
```http
DELETE /api/v1/notes/attachments/{attachmentId}
```

---

## 📞 JOURNAL D'APPELS

### 1. Lister les appels d'un client
```http
GET /api/v1/clients/{clientId}/calls
```

**Paramètres de requête :**
- `type` (optionnel) : `incoming`, `outgoing`, `missed`
- `outcome` (optionnel) : `positive`, `neutral`, `negative`, `no_answer`
- `follow_up_required` (optionnel) : `true`/`false`
- `date_from` (optionnel) : date de début (YYYY-MM-DD)
- `date_to` (optionnel) : date de fin (YYYY-MM-DD)

**Exemple de réponse avec statistiques :**
```json
{
  "success": true,
  "message": "Appels récupérés",
  "data": {
    "calls": {
      "data": [...]
    },
    "stats": {
      "total_calls": 5,
      "outgoing_calls": 3,
      "positive_calls": 4,
      "pending_follow_ups": 1,
      "total_duration": 150
    }
  }
}
```

### 2. Enregistrer un appel
```http
POST /api/v1/clients/{clientId}/calls
```

**Body :**
```json
{
  "contact_id": 1,
  "phone_number": "0123456789",
  "type": "outgoing", // incoming, outgoing, missed
  "called_at": "2026-01-20T14:30:00", // optionnel, défaut: maintenant
  "duration": 15, // en minutes
  "subject": "Objet de l'appel",
  "summary": "Résumé de la conversation",
  "outcome": "positive", // positive, neutral, negative, no_answer
  "follow_up_required": true,
  "follow_up_date": "2026-01-25" // si suivi requis
}
```

### 3. Voir un appel spécifique
```http
GET /api/v1/calls/{callId}
```

### 4. Modifier un appel
```http
PUT /api/v1/calls/{callId}
```

### 5. Supprimer un appel
```http
DELETE /api/v1/calls/{callId}
```

### 6. Marquer le suivi comme terminé
```http
PUT /api/v1/calls/{callId}/complete-follow-up
```

### 7. Appels nécessitant un suivi (dashboard)
```http
GET /api/v1/dashboard/calls/follow-ups
```

---

## 📅 GESTION DES RENDEZ-VOUS

### 1. Lister les rendez-vous d'un client
```http
GET /api/v1/clients/{clientId}/appointments
```

**Paramètres de requête :**
- `status` (optionnel) : `planned`, `confirmed`, `completed`, `cancelled`, `postponed`
- `date_from` (optionnel) : date de début
- `date_to` (optionnel) : date de fin

**Exemple de réponse avec statistiques :**
```json
{
  "success": true,
  "data": {
    "appointments": {
      "data": [...]
    },
    "stats": {
      "total_appointments": 10,
      "upcoming": 3,
      "completed": 6,
      "cancelled": 1,
      "postponed": 0,
      "this_month": 4
    }
  }
}
```

### 2. Planifier un rendez-vous
```http
POST /api/v1/clients/{clientId}/appointments
```

**Body :**
```json
{
  "title": "RDV démo produit",
  "description": "Présentation de notre solution",
  "scheduled_at": "2026-01-25T14:00:00",
  "duration": 60,
  "location": "Bureau client",
  "meeting_url": "https://meet.google.com/xyz",
  "type": "demo", // commercial, support, demo, negotiation, closing, other
  "timezone": "Europe/Paris",
  "reminder_minutes": 15,
  "participants": [
    {
      "contact_id": 1
    },
    {
      "name": "Jean Dupont",
      "email": "jean@example.com"
    }
  ]
}
```

### 3. Voir un rendez-vous spécifique
```http
GET /api/v1/appointments/{appointmentId}
```

### 4. Modifier un rendez-vous
```http
PUT /api/v1/appointments/{appointmentId}
```

### 5. Supprimer un rendez-vous
```http
DELETE /api/v1/appointments/{appointmentId}
```

### 6. Changer le statut d'un rendez-vous
```http
PUT /api/v1/appointments/{appointmentId}/status
```

**Body :**
```json
{
  "status": "completed", // planned, confirmed, completed, cancelled, postponed
  "completion_notes": "Le rendez-vous s'est bien déroulé",
  "completion_outcome": "positive" // positive, negative, neutral, follow_up
}
```

### 7. Rendez-vous du jour (dashboard)
```http
GET /api/v1/dashboard/appointments/today
```

### 8. Prochains rendez-vous (dashboard)
```http
GET /api/v1/dashboard/appointments/upcoming?days=7&limit=20
```

---

## 🕒 TIMELINE UNIFIÉE

### 1. Timeline chronologique d'un client
```http
GET /api/v1/clients/{clientId}/timeline
```

**Paramètres de requête :**
- `type` (optionnel) : `note`, `call`, `appointment`, `email`, `opportunity`, `modification`
- `user_id` (optionnel) : filtrer par utilisateur
- `date_from` (optionnel) : date de début
- `date_to` (optionnel) : date de fin
- `per_page` (optionnel) : défaut 20

**Exemple de réponse :**
```json
{
  "success": true,
  "message": "Timeline récupérée",
  "data": {
    "timeline": {
      "data": [
        {
          "id": 1,
          "type": "note",
          "title": "Note de test",
          "summary": "Ceci est une note de test pour le client ACME",
          "importance_level": "normal",
          "privacy_level": "public",
          "occurred_at": "2026-01-20T07:59:04.000000Z",
          "metadata": {
            "attachments_count": 0,
            "is_pinned": false
          },
          "user": {
            "id": 3,
            "name": "Bescovic rochnel Tegomo"
          }
        }
      ]
    },
    "stats": {
      "total_interactions": 1,
      "by_type": {
        "note": 1
      },
      "by_month": {
        "2026-01": 1
      }
    }
  }
}
```

### 2. Dashboard des interactions récentes
```http
GET /api/v1/dashboard/interactions?days=7&limit=50
```

### 3. Export de la timeline (NOUVEAU)
```http
GET /api/v1/clients/{clientId}/timeline/export?format=csv
```

**Paramètres de requête :**
- `format` (optionnel) : `csv`, `xlsx` (défaut: csv)

**Fonctionnalités d'export :**
- ✅ Export CSV/Excel complet de l'historique
- ✅ Métadonnées d'export (date, utilisateur, client)
- ✅ Aperçu des données avant téléchargement
- ✅ Liens détails automatiques pour chaque interaction
- ✅ Gestion des timelines vides
- ✅ Validation des formats et gestion d'erreurs
- ✅ Structure de colonnes standardisée

**Réponse export réussi :**
```json
{
  "success": true,
  "message": "Export préparé",
  "data": {
    "filename": "timeline_client_1_2026-01-22_09-18-47.csv",
    "format": "csv",
    "records_count": 11,
    "download_url": "http://localhost/exports/timeline_client_1_2026-01-22_09-18-47.csv",
    "metadata": {
      "exported_at": "2026-01-22 09:18:47",
      "exported_by": "Nom Utilisateur",
      "client_info": {
        "id": 1,
        "client_id": "CLI-E76E2DC4GQ",
        "name": "Entreprise ACME SARL"
      },
      "date_range": {
        "from": "2026-01-20 14:36:40",
        "to": "2026-01-22 10:00:00"
      },
      "types_included": ["appointment", "call", "note"],
      "total_by_type": {
        "appointment": 6,
        "call": 1,
        "note": 4
      }
    },
    "preview_data": [...],
    "columns": ["Date", "Type", "Titre", "Résumé", "Utilisateur", "Importance", "Confidentialité", "Lien détail"]
  }
}
```

**📋 Documentation complète :** Voir [EXPORT_TIMELINE_DOCUMENTATION.md](./EXPORT_TIMELINE_DOCUMENTATION.md) pour tests complets, exemples d'intégration et cas d'usage.

---

## 🔧 CODES D'ERREUR COMMUNS

- **400** : Requête malformée
- **401** : Non authentifié
- **403** : Non autorisé
- **404** : Ressource non trouvée
- **422** : Erreurs de validation
- **500** : Erreur serveur

**Format des erreurs de validation :**
```json
{
  "success": false,
  "message": "Erreur de validation",
  "errors": {
    "title": ["Le champ titre est obligatoire."],
    "type": ["Le type sélectionné est invalide."]
  }
}
```

---

## 📊 FONCTIONNALITÉS AVANCÉES

### ✨ NOUVEAUTÉS - Janvier 2026

#### 📧 Types d'interactions étendus
- **Emails** : Gestion des emails entrants/sortants avec pièces jointes
- **Opportunités** : Suivi du pipeline commercial avec montants et probabilités
- **Modifications** : Journal d'audit automatique des changements

#### 🔗 Liens détails automatiques
- Chaque entrée timeline inclut un lien direct vers l'API détail
- Format : `http://localhost/api/v1/{type}/{id}`
- Support pour tous les types d'interactions

#### 📊 Export avancé
- Export CSV/Excel de la timeline complète
- Aperçu des données avant téléchargement
- Statistiques d'export (nombre d'enregistrements, format)

### Synchronisation automatique avec la timeline
- Toutes les notes, appels, rendez-vous, emails et opportunités sont automatiquement ajoutés à la timeline
- Les modifications sont répercutées en temps réel
- La suppression d'un élément supprime aussi son entrée timeline
- Journal d'audit automatique pour traçabilité complète

### Niveaux de confidentialité
- **public** : Visible par tous les utilisateurs
- **private** : Visible uniquement par le créateur
- **team** : Visible par l'équipe (futur)

### Niveaux d'importance
- **low** : Éléments de faible priorité
- **normal** : Importance standard
- **high** : Éléments prioritaires (notes importantes, rendez-vous de démo)
- **critical** : Urgences et alertes

### Filtres et recherche
- Filtrage par type (6 types supportés), utilisateur, date
- Recherche textuelle (titre, contenu)
- Pagination pour de gros volumes
- Export sélectif selon les filtres appliqués

---

## 🚀 EXEMPLES D'INTÉGRATION

### Exemple complet avec curl

```bash
# 1. Créer une note
curl -X POST "http://localhost:8000/api/v1/clients/1/notes" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Contact initial","content":"Premier échange avec le client","type":"important"}'

# 2. Enregistrer un appel
curl -X POST "http://localhost:8000/api/v1/clients/1/calls" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"phone_number":"0123456789","type":"outgoing","subject":"Suivi commercial","duration":20,"outcome":"positive"}'

# 3. Planifier un rendez-vous (CORRIGÉ)
curl -X POST "http://localhost:8000/api/v1/clients/1/appointments" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"RDV commercial","scheduled_at":"2026-01-25T14:00:00","duration":60,"type":"negotiation","location":"Bureau","meeting_url":"https://meet.google.com/xyz"}'

# 4. Modifier le statut d'un rendez-vous
curl -X PUT "http://localhost:8000/api/v1/appointments/1/status" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status":"confirmed"}'

# 5. Consulter la timeline
curl -X GET "http://localhost:8000/api/v1/clients/1/timeline" \
  -H "Authorization: Bearer YOUR_TOKEN"

# 6. Dashboard des interactions récentes
curl -X GET "http://localhost:8000/api/v1/dashboard/interactions" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Exemple JavaScript/TypeScript

```typescript
class CrmApi {
  private baseUrl = 'http://localhost:8000/api/v1';
  private token: string;

  constructor(token: string) {
    this.token = token;
  }

  private async request(method: string, url: string, data?: any) {
    const response = await fetch(`${this.baseUrl}${url}`, {
      method,
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: data ? JSON.stringify(data) : undefined
    });
    return response.json();
  }

  // Notes
  async createNote(clientId: number, note: CreateNoteData) {
    return this.request('POST', `/clients/${clientId}/notes`, note);
  }

  async getClientNotes(clientId: number, params?: FilterParams) {
    const query = new URLSearchParams(params);
    return this.request('GET', `/clients/${clientId}/notes?${query}`);
  }

  // Appels
  async createCall(clientId: number, call: CreateCallData) {
    return this.request('POST', `/clients/${clientId}/calls`, call);
  }

  // Rendez-vous
  async createAppointment(clientId: number, appointment: CreateAppointmentData) {
    return this.request('POST', `/clients/${clientId}/appointments`, appointment);
  }

  // Timeline
  async getClientTimeline(clientId: number, params?: TimelineParams) {
    const query = new URLSearchParams(params);
    return this.request('GET', `/clients/${clientId}/timeline?${query}`);
  }
}
```

---

## 🔧 CORRECTIONS APPORTÉES

### Endpoint Appointments Corrigé
**Problème résolu :** L'endpoint `POST /api/v1/clients/{client}/appointments` retournait une erreur 500.

**✅ Corrections :**
- `duration_minutes` → `duration` (alignement avec la DB)
- Statuts : `scheduled` → `planned`, ajout de `postponed`
- Types : Alignement avec enum DB (`commercial`, `support`, `demo`, `negotiation`, `closing`, `other`)
- Ajout champs : `organizer_id`, `meeting_url`, `timezone`, `reminder_minutes`
- Support complet des participants avec contacts et invités externes

**🧪 Tests validés :**
```bash
# ✅ Création avec tous les champs
POST /api/v1/clients/1/appointments
{
  "title": "RDV commercial",
  "description": "Négociation contrat",
  "scheduled_at": "2026-01-26T10:00:00",
  "duration": 90,
  "type": "negotiation",
  "location": "Bureau",
  "meeting_url": "https://meet.google.com/abc",
  "participants": [{"name": "Jean Dupont", "email": "jean@example.com"}]
}

# ✅ Modification de statut
PUT /api/v1/appointments/1/status
{"status": "confirmed"}

# ✅ Timeline automatique
GET /api/v1/clients/1/timeline
```

---

## 📝 NOTES IMPORTANTES

1. **Authentification** : Tous les endpoints nécessitent une authentification Bearer token
2. **Pagination** : Les listes sont paginées par défaut (20 éléments par page)
3. **Dates** : Format ISO 8601 (YYYY-MM-DDTHH:mm:ss)
4. **Files upload** : Max 10MB pour les pièces jointes
5. **Timeline** : Mise à jour automatique lors des CRUD sur notes/appels/RDV
6. **Permissions** : Respect des niveaux de confidentialité (privé/public)
7. **⚠️ Appointments** : Utiliser `duration` (pas `duration_minutes`) et les nouveaux statuts

## 🌐 Accès aux outils

- **Documentation Swagger** : `http://localhost:8000/api/documentation`
- **Tests en ligne** : Interface Swagger intégrée
- **Logs** : `/storage/logs/laravel.log` pour le debugging

---

*Documentation mise à jour - TargetDesk CRM API v1.0*
*✅ Endpoint appointments corrigé et testé - 20/01/2026*