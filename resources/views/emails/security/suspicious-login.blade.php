@component('mail::message')
# Connexion suspecte détectée

Bonjour **{{ $user->name }}**,

Une connexion inhabituelle à votre compte TargetDesk CRM a été détectée.

## Détails de la connexion

@component('mail::panel')
**Compte :** {{ $user->email }}
**Date et heure :** {{ $loginAttempt['timestamp'] ?? now()->format('d/m/Y à H:i') }}
**Adresse IP :** {{ $loginAttempt['ip'] ?? 'Non disponible' }}
**Localisation estimée :** {{ $loginAttempt['location'] ?? 'Non disponible' }}
**Navigateur :** {{ $loginAttempt['user_agent'] ?? 'Non disponible' }}
**Statut :** {{ $loginAttempt['status'] ?? 'Bloquée' }}
@endcomponent

## Si c'était vous

Si vous reconnaissez cette connexion, aucune action n'est nécessaire. Nous surveillons votre compte pour votre sécurité.

## Si ce n'était pas vous

@component('mail::button', ['url' => config('app.frontend_url') . '/security/change-password', 'color' => 'error'])
Changer mon mot de passe immédiatement
@endcomponent

**Actions recommandées :**
- Changez votre mot de passe immédiatement
- Vérifiez vos sessions actives et fermez celles que vous ne reconnaissez pas
- Activez l'authentification à deux facteurs si disponible
- Contactez notre support si vous avez des inquiétudes

## Mesures de sécurité prises

- Cette tentative de connexion a été {{ $loginAttempt['blocked'] ? 'bloquée automatiquement' : 'surveillée' }}
- Nous avons renforcé la surveillance de votre compte
- Vous recevrez une alerte pour toute activité suspecte

---

**L'équipe TargetDesk**
Email: support@targetdesk.fr
Site: [targetdesk.fr](https://targetdesk.fr)

@component('mail::subcopy')
Cette alerte est envoyée automatiquement pour votre sécurité. En cas de doute, contactez immédiatement notre support à support@targetdesk.fr
@endcomponent
@endcomponent