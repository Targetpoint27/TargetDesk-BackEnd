@component('mail::message')
@if($recipientType === 'participant')
# 📧 Rappel - Invitation à un Rendez-vous

Bonjour **{{ $participantName }}**,

Vous êtes invité(e) à un rendez-vous prévu **{{ $timeUntil }}** organisé par **{{ $organizerName }}** avec :
@else
# 🕐 Rappel de Rendez-vous

Bonjour **{{ $organizerName }}**,

Vous avez un rendez-vous prévu **{{ $timeUntil }}** avec :
@endif

@component('mail::panel')
## 👤 {{ $client->name }}
**Client ID :** {{ $client->client_id ?? 'N/A' }}
@if($client->industry)
**Secteur :** {{ $client->industry }}
@endif
@endcomponent

---

## 📅 Détails du Rendez-vous

@component('mail::table')
| | |
|:--|:--|
| **📅 Date & Heure** | {{ $appointment->scheduled_at->format('d/m/Y à H:i') }} |
| **📍 Lieu** | {{ $appointment->location ?? 'Non spécifié' }} |
| **⏱️ Durée** | {{ $appointment->duration }} minutes |
| **🏷️ Type** | {{ ucfirst($appointment->type) }} |
| **🌍 Fuseau** | {{ $appointment->timezone }} |
@endcomponent

@if($appointment->description)
### 📋 Description
{{ $appointment->description }}
@endif

@if($participants->count() > 0)
### 👥 Participants
@foreach($participants as $participant)
- **{{ $participant->name }}**{{ $participant->email ? " - {$participant->email}" : '' }}
@endforeach
@endif

@if($appointment->meeting_url)
@component('mail::button', ['url' => $appointment->meeting_url])
🎥 Rejoindre la visioconférence
@endcomponent
@endif

@component('mail::button', ['url' => $clientUrl])
📋 Voir la fiche client
@endcomponent

---

@if($recipientType === 'organizer')
@component('mail::panel')
### 💡 Conseils pour votre Rendez-vous

✅ **Préparez vos documents** et questions à l'avance
✅ **Vérifiez votre matériel** (si visio)
✅ **Arrivez 5 minutes** avant l'heure
✅ **Consultez l'historique** des interactions client
@endcomponent
@else
@component('mail::panel')
### 💡 Informations importantes

✅ **Préparez-vous** pour ce rendez-vous
✅ **Vérifiez votre agenda** et disponibilité
✅ **Contactez l'organisateur** si besoin : {{ $organizerName }}
✅ **Confirmez votre présence** si nécessaire
@endcomponent
@endif

@switch($reminderType)
    @case('reminder_1440m')
        🗓️ **Rappel J-1** : Rendez-vous demain !
        @break
    @case('reminder_60m')
        ⏰ **Rappel dernière heure** : Préparez-vous !
        @break
    @case('reminder_15m')
        🚨 **Rappel urgent** : Rendez-vous dans 15 minutes !
        @break
@endswitch

---

Excellente réunion !

**L'équipe {{ config('app.name') }}**

@component('mail::subcopy')
Cet email est un rappel automatique généré par TargetDesk CRM.
Si vous ne souhaitez plus recevoir ces rappels, vous pouvez modifier vos préférences dans votre profil utilisateur.
@endcomponent

@endcomponent
