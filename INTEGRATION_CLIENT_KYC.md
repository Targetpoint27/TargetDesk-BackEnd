# 📋 INTÉGRATION CLIENT KYC - MODÈLES D'ENVOI ET DE RÉPONSE

**Version :** 1.0
**Date :** 2026-02-13
**Endpoints :** Création et mise à jour de clients avec champs KYC

---

## 🎯 VUE D'ENSEMBLE

Cette documentation présente les modèles de données pour l'intégration des fonctionnalités KYC (Know Your Customer) lors de la création et mise à jour des clients via l'API TargetDesk.

### Endpoints concernés
- `POST /api/v1/clients` - Création d'un client
- `PUT /api/v1/clients/{id}` - Mise à jour d'un client

---

## 📤 MODÈLES D'ENVOI (REQUEST)

### 🆕 Création d'un client (POST)

#### Headers requis
```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

#### Modèle de données
```json
{
  // CHAMPS OBLIGATOIRES
  "name": "string",           // Nom du client (obligatoire)
  "type": "string",           // Type: "particulier" ou "entreprise" (obligatoire)
  "email": "string",          // Email valide (obligatoire)

  // CHAMPS OPTIONNELS EXISTANTS
  "phone": "string|null",             // Téléphone (max 20 caractères)
  "address": "string|null",           // Adresse complète
  "siret": "string|null",             // SIRET (14 caractères exactement)
  "sector": "string|null",            // Secteur d'activité (max 255)
  "website": "string|null",           // Site web (URL valide)
  "notes": "string|null",             // Notes libres
  "is_active": "boolean",             // Statut actif (défaut: true)

  // NOUVEAUX CHAMPS KYC (TOUS OPTIONNELS)
  "brand_workshop": "string|null",                    // Marque/Atelier (max 255)
  "legal_form": "string|null",                        // Forme juridique (max 255)
  "legal_representative_first_name": "string|null",   // Prénom représentant légal (max 255)
  "legal_representative_last_name": "string|null",    // Nom représentant légal (max 255)
  "beneficial_owner_first_name": "string|null",       // Prénom bénéficiaire effectif (max 255)
  "beneficial_owner_last_name": "string|null",        // Nom bénéficiaire effectif (max 255)
  "bank": "string|null",                              // Nom de la banque (max 255)
  "bank_account_type": "string|null",                 // Type de compte bancaire (max 255)
  "payment_moment": "string|null",                    // Moment de paiement (max 255)
  "payment_in_foreign_currency": "boolean|null",      // Paiement en devise ? (défaut: false)
  "has_bank_identity_statement": "boolean|null"       // Relevé d'identité bancaire ? (défaut: false)
}
```

#### Exemple complet - Création client entreprise
```json
{
  "name": "Entreprise ACME SARL",
  "type": "entreprise",
  "email": "contact@acme-sarl.fr",
  "phone": "0123456789",
  "address": "123 Rue de la Innovation, 75001 Paris",
  "siret": "12345678901234",
  "sector": "Technologie",
  "website": "https://acme-sarl.fr",
  "notes": "Client important - secteur innovation",
  "is_active": true,

  "brand_workshop": "ACME Innovation Lab",
  "legal_form": "SARL",
  "legal_representative_first_name": "Jean",
  "legal_representative_last_name": "Dupont",
  "beneficial_owner_first_name": "Marie",
  "beneficial_owner_last_name": "Martin",
  "bank": "Crédit Agricole",
  "bank_account_type": "Compte professionnel",
  "payment_moment": "Fin de mois",
  "payment_in_foreign_currency": false,
  "has_bank_identity_statement": true
}
```

#### Exemple minimal - Création client particulier
```json
{
  "name": "Martin Pierre",
  "type": "particulier",
  "email": "pierre.martin@email.com"
}
```

---

### ✏️ Mise à jour d'un client (PUT)

#### Headers requis
```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

#### Modèle de données
```json
{
  // TOUS LES CHAMPS SONT OPTIONNELS LORS D'UNE MISE À JOUR
  // Seuls les champs fournis seront mis à jour

  // Champs existants (optionnels avec 'sometimes')
  "name": "string",                   // Si fourni, doit être non vide
  "type": "string",                   // Si fourni: "particulier" ou "entreprise"
  "email": "string",                  // Si fourni, doit être email valide
  "phone": "string|null",
  "address": "string|null",
  "siret": "string|null",             // Si fourni, exactement 14 caractères
  "sector": "string|null",
  "website": "string|null",           // Si fourni, doit être URL valide
  "notes": "string|null",
  "is_active": "boolean",

  // Nouveaux champs KYC (tous optionnels)
  "brand_workshop": "string|null",
  "legal_form": "string|null",
  "legal_representative_first_name": "string|null",
  "legal_representative_last_name": "string|null",
  "beneficial_owner_first_name": "string|null",
  "beneficial_owner_last_name": "string|null",
  "bank": "string|null",
  "bank_account_type": "string|null",
  "payment_moment": "string|null",
  "payment_in_foreign_currency": "boolean",
  "has_bank_identity_statement": "boolean"
}
```

#### Exemple - Mise à jour partielle (seulement KYC)
```json
{
  "legal_form": "SAS",
  "legal_representative_first_name": "Jean-Pierre",
  "legal_representative_last_name": "Dubois",
  "bank": "BNP Paribas",
  "payment_in_foreign_currency": true,
  "has_bank_identity_statement": true
}
```

---

## 📥 MODÈLES DE RÉPONSE (RESPONSE)

### ✅ Réponse de succès - Création

```json
{
  "success": true,
  "message": "Client créé avec succès",
  "data": {
    "id": 123,
    "client_id": "CLI-ABC123DEF4",        // ID unique généré automatiquement
    "name": "Entreprise ACME SARL",
    "type": "entreprise",
    "email": "contact@acme-sarl.fr",
    "phone": "0123456789",
    "address": "123 Rue de la Innovation, 75001 Paris",
    "siret": "12345678901234",
    "sector": "Technologie",
    "website": "https://acme-sarl.fr",
    "notes": "Client important - secteur innovation",
    "is_active": true,
    "created_by": 1,

    // Nouveaux champs KYC
    "brand_workshop": "ACME Innovation Lab",
    "legal_form": "SARL",
    "legal_representative_first_name": "Jean",
    "legal_representative_last_name": "Dupont",
    "beneficial_owner_first_name": "Marie",
    "beneficial_owner_last_name": "Martin",
    "bank": "Crédit Agricole",
    "bank_account_type": "Compte professionnel",
    "payment_moment": "Fin de mois",
    "payment_in_foreign_currency": false,
    "has_bank_identity_statement": true,

    // Métadonnées
    "created_at": "2026-02-13T13:45:00.000000Z",
    "updated_at": "2026-02-13T13:45:00.000000Z",

    // Relations incluses
    "creator": {
      "id": 1,
      "name": "Admin User"
    },
    "categories": [],
    "kyc_documents": []
  }
}
```

### ✅ Réponse de succès - Mise à jour

```json
{
  "success": true,
  "message": "Client mis à jour avec succès",
  "data": {
    "id": 123,
    "client_id": "CLI-ABC123DEF4",
    "name": "Entreprise ACME SARL",
    // ... tous les champs client mis à jour
    "updated_at": "2026-02-13T14:30:00.000000Z",

    // Relations incluses
    "creator": {
      "id": 1,
      "name": "Admin User"
    },
    "categories": [
      {
        "id": 1,
        "name": "Client Premium",
        "type": "qualification"
      }
    ],
    "kyc_documents": [
      {
        "id": 1,
        "document_type": "kbis",
        "original_name": "kbis_acme.pdf",
        "file_size": 524288,
        "can_preview": true,
        "uploaded_at": "2026-02-13T13:50:00.000000Z"
      }
    ]
  }
}
```

### ❌ Réponse d'erreur - Validation

```json
{
  "success": false,
  "message": "Données invalides",
  "errors": {
    "name": ["Le nom du client est obligatoire."],
    "email": ["L'adresse email doit être valide."],
    "siret": ["Le numéro SIRET ne peut pas dépasser 14 caractères."],
    "website": ["Le site web doit être une URL valide."],
    "payment_in_foreign_currency": ["Le champ \"paiement en devise\" doit être true ou false."]
  }
}
```

### ❌ Réponse d'erreur - Client non trouvé

```json
{
  "success": false,
  "message": "Client non trouvé"
}
```

---

## 🔍 DÉTAILS DES CHAMPS KYC

### Champs texte libres
| Champ | Description | Longueur max | Exemple |
|-------|-------------|--------------|---------|
| `brand_workshop` | Marque commerciale ou nom d'atelier | 255 | "ACME Innovation Lab" |
| `legal_form` | Forme juridique de l'entreprise | 255 | "SARL", "SAS", "Auto-entrepreneur" |
| `legal_representative_first_name` | Prénom du représentant légal | 255 | "Jean-Pierre" |
| `legal_representative_last_name` | Nom du représentant légal | 255 | "Dupont-Martin" |
| `beneficial_owner_first_name` | Prénom du bénéficiaire effectif | 255 | "Marie" |
| `beneficial_owner_last_name` | Nom du bénéficiaire effectif | 255 | "Martin" |
| `bank` | Nom de la banque | 255 | "Crédit Agricole", "BNP Paribas" |
| `bank_account_type` | Type de compte bancaire | 255 | "Compte professionnel", "Compte courant" |
| `payment_moment` | Moment/modalité de paiement | 255 | "Fin de mois", "À réception", "30 jours" |

### Champs booléens
| Champ | Description | Valeurs | Défaut |
|-------|-------------|---------|---------|
| `payment_in_foreign_currency` | Paiement en devise étrangère ? | `true`, `false`, `null` | `false` |
| `has_bank_identity_statement` | Possède un RIB ? | `true`, `false`, `null` | `false` |

---

## 🧪 EXEMPLES D'INTÉGRATION

### Création d'un client minimal
```bash
curl -X POST "https://api.targetdesk.com/v1/clients" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "Nouveau Client",
    "type": "entreprise",
    "email": "client@example.com"
  }'
```

### Création d'un client complet avec KYC
```bash
curl -X POST "https://api.targetdesk.com/v1/clients" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name": "ACME Corporation",
    "type": "entreprise",
    "email": "contact@acme.com",
    "phone": "0123456789",
    "siret": "12345678901234",
    "legal_form": "SARL",
    "legal_representative_first_name": "Jean",
    "legal_representative_last_name": "Dupont",
    "bank": "Crédit Agricole",
    "payment_in_foreign_currency": false,
    "has_bank_identity_statement": true
  }'
```

### Mise à jour partielle d'un client
```bash
curl -X PUT "https://api.targetdesk.com/v1/clients/123" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "legal_form": "SAS",
    "bank": "BNP Paribas",
    "payment_in_foreign_currency": true
  }'
```

---

## ⚠️ NOTES IMPORTANTES

### Validation
- **Création** : Seuls `name`, `type` et `email` sont obligatoires
- **Mise à jour** : Tous les champs sont optionnels
- Les champs KYC sont **toujours optionnels** pour permettre une saisie progressive
- Les champs booléens acceptent `true`, `false` ou `null`

### Évolutivité
- Nouveaux champs KYC peuvent être ajoutés sans casser l'existant
- Les anciens clients sans données KYC continuent de fonctionner
- Les documents KYC sont gérés séparément via d'autres endpoints

### Sécurité
- Tous les endpoints nécessitent une authentification Bearer Token
- Les données sensibles sont stockées de manière sécurisée
- Logs d'audit automatiques sur toutes les modifications

---

## 📞 SUPPORT TECHNIQUE

Pour toute question sur l'intégration KYC :
- **Documentation API complète** : `/api/documentation`
- **Endpoints de test** : Environnement sandbox disponible
- **Support technique** : Via les issues GitHub du projet