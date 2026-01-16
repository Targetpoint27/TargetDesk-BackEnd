# API Clients TargetDesk - Guide d'intégration

## Base URL
```
http://localhost:8000/api/v1
```

## Authentification
Tous les endpoints clients nécessitent une authentification Bearer token.

```
Authorization: Bearer {token}
```

---

## Endpoints disponibles

### 1. Créer une fiche client

**POST** `/clients`

Créer une nouvelle fiche client avec génération automatique d'identifiant unique.

```bash
curl -X POST http://localhost:8000/api/v1/clients \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Entreprise ACME",
    "type": "entreprise",
    "email": "contact@acme.com",
    "phone": "0123456789",
    "address": "123 Rue de la Paix, 75001 Paris",
    "siret": "12345678901234",
    "sector": "Technologie",
    "website": "https://acme.com",
    "notes": "Client important"
  }'
```

**Champs requis :**
- `name` (string) - Nom/Raison sociale
- `type` (enum) - "particulier" ou "entreprise"
- `email` (email) - Email principal (unique)

**Champs optionnels :**
- `phone` (string) - Numéro de téléphone
- `address` (text) - Adresse complète
- `siret` (string) - SIRET (14 caractères, unique)
- `sector` (string) - Secteur d'activité
- `website` (url) - Site web
- `notes` (text) - Notes libres

**Réponse (201 Created) :**
```json
{
  "success": true,
  "message": "Client créé avec succès",
  "data": {
    "id": 1,
    "client_id": "CLI-ABC123XYZ4",
    "name": "Entreprise ACME",
    "type": "entreprise",
    "email": "contact@acme.com",
    "phone": "0123456789",
    "address": "123 Rue de la Paix, 75001 Paris",
    "siret": "12345678901234",
    "sector": "Technologie",
    "website": "https://acme.com",
    "notes": "Client important",
    "is_active": true,
    "created_at": "2026-01-09T18:00:00.000000Z"
  }
}
```

**Erreurs possibles :**
- `422` - Erreur de validation (email déjà utilisé, SIRET déjà utilisé, etc.)

---

### 2. Lister les clients

**GET** `/clients`

Récupérer la liste paginée de tous les clients actifs.

```bash
curl -X GET "http://localhost:8000/api/v1/clients?page=1&per_page=10" \
  -H "Authorization: Bearer {token}"
```

**Paramètres de requête :**
- `page` (int, optionnel) - Numéro de page (défaut: 1)
- `per_page` (int, optionnel) - Éléments par page (défaut: 15, max: 100)

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Clients récupérés avec succès",
  "data": {
    "clients": [
      {
        "id": 1,
        "client_id": "CLI-ABC123XYZ4",
        "name": "Entreprise ACME",
        "type": "entreprise",
        "email": "contact@acme.com",
        "phone": "0123456789",
        "address": "123 Rue de la Paix, 75001 Paris",
        "siret": "12345678901234",
        "sector": "Technologie",
        "website": "https://acme.com",
        "notes": "Client important",
        "is_active": true,
        "created_by": 1,
        "created_at": "2026-01-09T18:00:00.000000Z",
        "updated_at": "2026-01-09T18:00:00.000000Z",
        "categories_count": 3,
        "categories_summary": [
          {
            "type": "secteur",
            "count": 1,
            "categories": [
              {
                "id": 1,
                "name": "Industrie Automobile",
                "color": "#FF6B6B"
              }
            ]
          },
          {
            "type": "taille",
            "count": 1,
            "categories": [
              {
                "id": 2,
                "name": "PME",
                "color": "#4ECDC4"
              }
            ]
          },
          {
            "type": "priorite",
            "count": 1,
            "categories": [
              {
                "id": 3,
                "name": "Client VIP",
                "color": "#FFD93D"
              }
            ]
          }
        ],
        "categories": [
          {
            "id": 1,
            "name": "Industrie Automobile",
            "color": "#FF6B6B",
            "type": "secteur",
            "pivot": {
              "client_id": 1,
              "category_id": 1,
              "assigned_by": 1,
              "assigned_at": "2026-01-15 12:59:12"
            }
          },
          {
            "id": 2,
            "name": "PME",
            "color": "#4ECDC4",
            "type": "taille",
            "pivot": {
              "client_id": 1,
              "category_id": 2,
              "assigned_by": 1,
              "assigned_at": "2026-01-15 12:59:12"
            }
          },
          {
            "id": 3,
            "name": "Client VIP",
            "color": "#FFD93D",
            "type": "priorite",
            "pivot": {
              "client_id": 1,
              "category_id": 3,
              "assigned_by": 1,
              "assigned_at": "2026-01-15 12:59:12"
            }
          }
        ],
        "creator": {
          "id": 1,
          "name": "John Doe"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 1,
      "total_items": 1,
      "per_page": 15
    }
  }
}
```

**Nouvelles propriétés ajoutées :**
- `categories_count` (int) - Nombre total de catégories assignées au client
- `categories_summary` (array) - Résumé des catégories groupées par type avec compteurs
  - `type` (string) - Type de catégorie (secteur, taille, priorite, origine, personnalisee)
  - `count` (int) - Nombre de catégories de ce type
  - `categories` (array) - Liste des catégories avec ID, nom et couleur
- `categories` (array) - Liste complète des catégories assignées au client
  - Inclut les informations pivot (assigned_by, assigned_at)
  - Informations complètes de chaque catégorie (nom, couleur, type)

---

### 3. Détails d'un client

**GET** `/clients/{id}`

Récupérer les détails complets d'un client spécifique.

```bash
curl -X GET http://localhost:8000/api/v1/clients/1 \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Client trouvé",
  "data": {
    "id": 1,
    "client_id": "CLI-ABC123XYZ4",
    "name": "Entreprise ACME",
    "type": "entreprise",
    "email": "contact@acme.com",
    "phone": "0123456789",
    "address": "123 Rue de la Paix, 75001 Paris",
    "siret": "12345678901234",
    "sector": "Technologie",
    "website": "https://acme.com",
    "notes": "Client important",
    "is_active": true,
    "created_by": 1,
    "created_at": "2026-01-09T18:00:00.000000Z",
    "updated_at": "2026-01-09T18:00:00.000000Z",
    "creator": {
      "id": 1,
      "name": "John Doe"
    }
  }
}
```

**Erreurs possibles :**
- `404` - Client non trouvé

---

### 4. Modifier un client

**PUT** `/clients/{id}`

Mettre à jour les informations d'un client existant.

```bash
curl -X PUT http://localhost:8000/api/v1/clients/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Entreprise ACME Modifiée",
    "phone": "0987654321",
    "notes": "Notes mises à jour"
  }'
```

**Champs modifiables :**
- Tous les champs sauf `client_id` et `created_by`
- Les champs non fournis restent inchangés
- Validation d'unicité pour `email` et `siret`

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Client mis à jour avec succès",
  "data": {
    // Client modifié avec nouvelles valeurs
  }
}
```

**Erreurs possibles :**
- `404` - Client non trouvé
- `422` - Erreur de validation

---

### 5. Supprimer un client

**DELETE** `/clients/{id}`

Supprimer (désactiver) un client. Il s'agit d'une suppression logique (soft delete).

```bash
curl -X DELETE http://localhost:8000/api/v1/clients/1 \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Client supprimé avec succès",
  "data": null
}
```

**Note importante :** La suppression est logique. Le client est marqué comme `is_active: false` mais conservé en base de données pour traçabilité.

**Erreurs possibles :**
- `404` - Client non trouvé

---

## Codes d'erreur

- `200` - Succès
- `201` - Créé avec succès
- `401` - Non authentifié (token manquant/invalide)
- `404` - Ressource non trouvée
- `422` - Erreur de validation
- `500` - Erreur serveur

---

## Exemples d'intégration

### JavaScript/Fetch

```javascript
class ClientsAPI {
  constructor(token) {
    this.baseURL = 'http://localhost:8000/api/v1';
    this.token = token;
  }

  async createClient(clientData) {
    try {
      const response = await fetch(`${this.baseURL}/clients`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${this.token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(clientData)
      });

      const data = await response.json();

      if (data.success) {
        return data.data;
      } else {
        throw new Error(data.message);
      }
    } catch (error) {
      console.error('Erreur création client:', error);
      throw error;
    }
  }

  async getClients(page = 1, perPage = 15) {
    try {
      const response = await fetch(
        `${this.baseURL}/clients?page=${page}&per_page=${perPage}`,
        {
          headers: {
            'Authorization': `Bearer ${this.token}`,
          }
        }
      );

      const data = await response.json();
      return data.data;
    } catch (error) {
      console.error('Erreur récupération clients:', error);
      throw error;
    }
  }

  async updateClient(id, updateData) {
    try {
      const response = await fetch(`${this.baseURL}/clients/${id}`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${this.token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(updateData)
      });

      const data = await response.json();
      return data.data;
    } catch (error) {
      console.error('Erreur modification client:', error);
      throw error;
    }
  }

  async deleteClient(id) {
    try {
      const response = await fetch(`${this.baseURL}/clients/${id}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${this.token}`,
        }
      });

      const data = await response.json();
      return data.success;
    } catch (error) {
      console.error('Erreur suppression client:', error);
      throw error;
    }
  }
}

// Utilisation
const api = new ClientsAPI('your-bearer-token');

// Créer un client
const newClient = await api.createClient({
  name: 'Test Client',
  type: 'entreprise',
  email: 'test@example.com'
});

// Récupérer la liste
const clients = await api.getClients();
```

### PHP/cURL

```php
class ClientsAPI {
    private $baseURL = 'http://localhost:8000/api/v1';
    private $token;

    public function __construct($token) {
        $this->token = $token;
    }

    public function createClient($clientData) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseURL . '/clients');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($clientData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function getClients($page = 1, $perPage = 15) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseURL . "/clients?page={$page}&per_page={$perPage}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}

// Utilisation
$api = new ClientsAPI('your-bearer-token');
$result = $api->createClient([
    'name' => 'Test Client',
    'type' => 'entreprise',
    'email' => 'test@example.com'
]);
```

---

## Fonctionnalités avancées

### Audit et logs
- Tous les CUD (Create, Update, Delete) sont automatiquement loggés
- Les logs incluent l'utilisateur qui a effectué l'action
- Format : `Log::info('Action', ['client_id', 'user_info', 'changes'])`

### Validation stricte
- Email unique au niveau base de données
- SIRET unique (14 caractères exactement)
- Types de clients contrôlés (particulier/entreprise)
- URLs valides pour les sites web

### Identifiants uniques
- Génération automatique d'identifiants au format `CLI-XXXXXXXXXX`
- Utilisés pour la traçabilité et l'interface utilisateur
- Indépendants de l'ID technique de base de données

---

## Documentation Swagger

La documentation interactive complète est disponible sur :
**http://localhost:8000/api/documentation**

Cette interface permet de :
- Tester tous les endpoints clients directement
- Voir les schémas de données détaillés
- Gérer l'authentification Bearer
- Visualiser les réponses d'exemple

---

## Support et maintenance

- Logs disponibles dans `storage/logs/laravel.log`
- Validation côté serveur avec messages d'erreur en français
- Architecture respectant les standards REST
- Compatible avec toutes les librairies HTTP standards