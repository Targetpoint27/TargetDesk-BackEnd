# Guide d'Intégration Client - Système de Notifications Email

## Vue d'ensemble

Le système de notifications email de TargetDesk permet aux commerciaux de recevoir des rappels automatiques avant leurs rendez-vous. Cette documentation présente les endpoints API disponibles pour intégrer ce système côté client (frontend).

## Configuration Préalable

### Variables d'environnement Backend
```bash
MAIL_MAILER=smtp                           # smtp, log, mailgun, etc.
MAIL_FROM_ADDRESS=notifications@targetdesk.com
MAIL_FROM_NAME=TargetDesk

# Exemple configuration Gmail
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

### Scheduler Laravel (Production)
```bash
# Crontab à ajouter sur le serveur
* * * * * cd /path/to/targetdesk && php artisan schedule:run >> /dev/null 2>&1
```

## Endpoints API

### 1. Gestion des Préférences Utilisateur

#### Récupérer les préférences
```typescript
GET /api/v1/users/{userId}/email-preferences
Authorization: Bearer {token}

// Réponse
{
  "success": true,
  "data": {
    "user_id": 1,
    "appointment_reminder": {
      "timing": [15, 60, 1440],           // Minutes avant RDV
      "email_enabled": true,
      "is_active": true,
      "formatted_timing": ["15 minutes", "1 heure", "1 jour"]
    }
  }
}
```

#### Mettre à jour les préférences
```typescript
PUT /api/v1/users/{userId}/email-preferences
Authorization: Bearer {token}
Content-Type: application/json

{
  "appointment_reminder": {
    "timing": [15, 60, 1440],              // 1-5 valeurs, 1-10080 min
    "email_enabled": true
  }
}
```

**Validation :**
- `timing` : Array de 1 à 5 valeurs entre 1 et 10080 minutes
- `email_enabled` : Boolean

### 2. Consultation des Rappels

#### Rappels en attente
```typescript
GET /api/v1/email-reminders/pending?limit=20
Authorization: Bearer {token}

// Réponse
{
  "success": true,
  "data": {
    "reminders": [
      {
        "id": 1,
        "appointment_id": 123,
        "type": "reminder_60m",
        "formatted_type": "1 heure avant",
        "scheduled_for": "2026-02-04T07:30:00.000000Z",
        "email_to": "commercial@test.com",
        "appointment": {
          "id": 123,
          "title": "Rendez-vous Client ABC",
          "scheduled_at": "2026-02-04T08:30:00.000000Z",
          "client_name": "Entreprise ABC",
          "status": "planned"
        }
      }
    ],
    "count": 1
  }
}
```

#### Historique des rappels envoyés
```typescript
GET /api/v1/email-reminders/sent?limit=50&days=30
Authorization: Bearer {token}

// Paramètres optionnels
// limit: Nombre de résultats (défaut: 50)
// days: Période en jours (défaut: 30)
```

#### Statistiques des rappels
```typescript
GET /api/v1/email-reminders/statistics?days=30
Authorization: Bearer {token}

// Réponse
{
  "success": true,
  "data": {
    "period_days": 30,
    "total_reminders": 25,
    "sent_reminders": 23,
    "failed_reminders": 1,
    "pending_reminders": 1,
    "success_rate": 92.0,
    "failure_rate": 4.0
  }
}
```

## Composants Frontend Recommandés

### 1. Composant de Configuration des Préférences

```typescript
interface NotificationPreferences {
  timing: number[];
  email_enabled: boolean;
  is_active: boolean;
  formatted_timing: string[];
}

interface PreferencesFormData {
  timing: number[];
  email_enabled: boolean;
}

// Valeurs prédéfinies courantes
const TIMING_PRESETS = [
  { label: "15 minutes", value: 15 },
  { label: "30 minutes", value: 30 },
  { label: "1 heure", value: 60 },
  { label: "2 heures", value: 120 },
  { label: "1 jour", value: 1440 },
  { label: "1 semaine", value: 10080 }
];
```

### 2. Composant Dashboard des Rappels

```typescript
interface PendingReminder {
  id: number;
  appointment_id: number;
  type: string;
  formatted_type: string;
  scheduled_for: string;
  email_to: string;
  appointment: {
    id: number;
    title: string;
    scheduled_at: string;
    client_name: string;
    status: string;
  };
}

interface ReminderStatistics {
  period_days: number;
  total_reminders: number;
  sent_reminders: number;
  failed_reminders: number;
  pending_reminders: number;
  success_rate: number;
  failure_rate: number;
}
```

### 3. Hooks React/Vue Exemple

```typescript
// Hook React pour les préférences
const useNotificationPreferences = (userId: number) => {
  const [preferences, setPreferences] = useState<NotificationPreferences | null>(null);
  const [loading, setLoading] = useState(false);

  const fetchPreferences = async () => {
    setLoading(true);
    try {
      const response = await api.get(`/users/${userId}/email-preferences`);
      setPreferences(response.data.data.appointment_reminder);
    } catch (error) {
      console.error('Erreur lors du chargement des préférences:', error);
    } finally {
      setLoading(false);
    }
  };

  const updatePreferences = async (data: PreferencesFormData) => {
    try {
      const response = await api.put(`/users/${userId}/email-preferences`, {
        appointment_reminder: data
      });
      setPreferences(response.data.data.preferences.appointment_reminder);
      return { success: true };
    } catch (error) {
      console.error('Erreur lors de la mise à jour:', error);
      return { success: false, error };
    }
  };

  useEffect(() => {
    if (userId) fetchPreferences();
  }, [userId]);

  return { preferences, loading, updatePreferences, refetch: fetchPreferences };
};
```

## Gestion des Erreurs

### Codes d'erreur API
- **401** : Non authentifié
- **403** : Accès refusé (utilisateur ne peut pas modifier ces préférences)
- **422** : Erreur de validation
- **500** : Erreur serveur

### Validation côté client
```typescript
const validateTiming = (timing: number[]): string[] => {
  const errors: string[] = [];

  if (timing.length === 0) {
    errors.push("Au moins un timing doit être sélectionné");
  }

  if (timing.length > 5) {
    errors.push("Maximum 5 timings autorisés");
  }

  timing.forEach(time => {
    if (time < 1 || time > 10080) {
      errors.push("Les timings doivent être entre 1 minute et 1 semaine");
    }
  });

  return errors;
};
```

## Fonctionnalités Automatiques

### Création de Rendez-vous
Lors de la création/modification d'un rendez-vous via l'API, les rappels sont automatiquement programmés selon les préférences utilisateur.

```typescript
// Aucune action supplémentaire requise côté client
POST /api/v1/clients/{clientId}/appointments
{
  "title": "Rendez-vous important",
  "scheduled_at": "2026-02-05T14:00:00",
  "user_id": 1
  // ... autres champs
}
// → Les rappels sont automatiquement créés
```

### Gestion des Statuts
- **Annulation** : Les rappels en attente sont automatiquement supprimés
- **Reprogrammation** : Nouveaux rappels créés automatiquement
- **Changement d'assigné** : Rappels recalculés avec les préférences du nouvel utilisateur

## Interface Utilisateur Recommandée

### Page de Configuration
```
┌─ Préférences de Notifications ─────────────────────┐
│                                                    │
│ 📧 Notifications Email                             │
│ ☑️ Activer les rappels email                       │
│                                                    │
│ ⏰ Programmer les rappels avant le rendez-vous :   │
│ ☑️ 15 minutes                                      │
│ ☑️ 1 heure                                         │
│ ☑️ 1 jour                                          │
│ ☐ 1 semaine                                       │
│                                                    │
│ [Personnaliser...] [Sauvegarder]                  │
└────────────────────────────────────────────────────┘
```

### Dashboard des Rappels
```
┌─ Mes Rappels à Venir ─────────────────────────────┐
│                                                   │
│ 📧 Rendez-vous Client ABC                         │
│    📅 Demain 14h00 - Rappel dans 1 heure         │
│                                                   │
│ 📧 Présentation Produit XYZ                      │
│    📅 Vendredi 10h30 - Rappel dans 2 jours       │
│                                                   │
│ 📊 Cette semaine: 5 rappels envoyés (100% succès) │
└───────────────────────────────────────────────────┘
```

## Tests d'Intégration

### Configuration de test
```bash
# Backend - Mode log pour développement
MAIL_MAILER=log
MAIL_FROM_ADDRESS=notifications@targetdesk.com

# Les emails seront visibles dans storage/logs/laravel.log
```

### Scénario de test complet
1. Créer un utilisateur test
2. Configurer ses préférences de notification
3. Créer un rendez-vous à venir
4. Vérifier que les rappels sont programmés
5. Traiter manuellement les rappels : `php artisan appointments:process-email-reminders`
6. Vérifier les logs ou emails reçus

### Endpoints de monitoring
```typescript
// Vérifier l'état du système
GET /api/v1/email-reminders/statistics

// Vérifier les rappels en attente
GET /api/v1/email-reminders/pending?limit=5

// Historique récent
GET /api/v1/email-reminders/sent?days=1&limit=10
```

## Support et Maintenance

### Logs utiles
```bash
# Logs application
tail -f storage/logs/laravel.log

# Logs spécifiques aux emails
grep "Email reminder" storage/logs/laravel.log

# Logs des tâches programmées
tail -f storage/logs/schedule-reminders.log
tail -f storage/logs/email-reminders.log
```

### Commandes de maintenance
```bash
# Nettoyer les anciens rappels
php artisan appointments:process-email-reminders --clean-old

# Programmer de nouveaux rappels
php artisan appointments:schedule-reminders --days=7

# Vérifier la configuration email
php artisan tinker
>>> Mail::raw('Test', fn($m) => $m->to('test@example.com')->subject('Test'));
```

## Sécurité

### Autorisation
- Les utilisateurs ne peuvent voir/modifier que leurs propres préférences
- Les admins peuvent accéder à toutes les préférences
- Validation stricte des données côté serveur

### Données sensibles
- Les emails ne contiennent pas d'informations confidentielles
- Les logs sont purgés automatiquement
- Les tokens d'API ont une expiration

---

**Version du système :** 1.0
**Dernière mise à jour :** Février 2026
**Documentation API complète :** http://localhost:8000/api/documentation