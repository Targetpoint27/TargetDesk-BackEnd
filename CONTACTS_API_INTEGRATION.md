# API Contacts TargetDesk - Guide d'intégration

## Vue d'ensemble

L'API Contacts permet de gérer les contacts pour les **clients** et les **fournisseurs** avec les mêmes fonctionnalités :
- Gestion du contact principal (un seul par entité)
- Emails et téléphones multiples
- Validation d'unicité des emails par entité
- Audit logging complet

## Base URL
```
http://localhost:8000/api/v1
```

## Authentification
Tous les endpoints contacts nécessitent une authentification Bearer token.

```
Authorization: Bearer {token}
```

---

## Endpoints disponibles

### 1. Lister tous les contacts (Global)

**GET** `/contacts`

Récupérer tous les contacts (clients ET fournisseurs) avec informations sur l'entité associée. Endpoint principal pour vue d'ensemble et recherche globale.

```bash
# Tous les contacts
curl -X GET "http://localhost:8000/api/v1/contacts?page=1&per_page=15" \
  -H "Authorization: Bearer {token}"

# Filtrer par type d'entité
curl -X GET "http://localhost:8000/api/v1/contacts?entity_type=supplier" \
  -H "Authorization: Bearer {token}"

# Rechercher dans les noms et emails
curl -X GET "http://localhost:8000/api/v1/contacts?search=jean" \
  -H "Authorization: Bearer {token}"
```

**Paramètres de requête :**
- `page` (int, optionnel) - Numéro de page (défaut: 1)
- `per_page` (int, optionnel) - Éléments par page (défaut: 15, max: 100)
- `search` (string, optionnel) - Recherche dans prénoms, noms et emails
- `entity_type` (enum, optionnel) - Filtrer par type ("client" ou "supplier")

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Contacts récupérés avec succès",
  "data": {
    "contacts": [
      {
        "id": 1,
        "client_id": 2,
        "supplier_id": null,
        "civility": "M.",
        "first_name": "Jean",
        "last_name": "Dupont",
        "function": "Directeur Commercial",
        "department": "Ventes",
        "is_primary": true,
        "is_active": true,
        "entity_type": "client",
        "entity_name": "Entreprise ACME",
        "entity_id": "CLI-ABC123XYZ4",
        "full_name": "M. Jean Dupont",
        "primary_email": "jean.dupont@client.com",
        "primary_phone": "0123456789",
        "emails": [...],
        "phones": [...],
        "client": {
          "id": 2,
          "client_id": "CLI-ABC123XYZ4",
          "name": "Entreprise ACME"
        },
        "supplier": null,
        "creator": {
          "id": 1,
          "name": "Test User"
        }
      },
      {
        "id": 5,
        "client_id": null,
        "supplier_id": 4,
        "entity_type": "supplier",
        "entity_name": "ElectroTech Distribution",
        "entity_id": "FOUR-YXZU82DM",
        "supplier": {
          "id": 4,
          "supplier_id": "FOUR-YXZU82DM",
          "name": "ElectroTech Distribution"
        },
        "client": null
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 1,
      "total_items": 5,
      "per_page": 15
    },
    "filters": {
      "search": "jean",
      "entity_type": "client"
    }
  }
}
```

---

### 2. Lister les contacts d'un client

**GET** `/clients/{clientId}/contacts`

Récupérer la liste paginée des contacts d'un client spécifique, triée avec le contact principal en premier.

### 3. Lister les contacts d'un fournisseur

**GET** `/suppliers/{supplierId}/contacts`

Récupérer la liste paginée des contacts d'un fournisseur spécifique, triée avec le contact principal en premier.

```bash
# Pour un client
curl -X GET "http://localhost:8000/api/v1/clients/2/contacts?page=1&per_page=10" \
  -H "Authorization: Bearer {token}"

# Pour un fournisseur
curl -X GET "http://localhost:8000/api/v1/suppliers/4/contacts?page=1&per_page=10" \
  -H "Authorization: Bearer {token}"
```

**Paramètres de requête :**
- `page` (int, optionnel) - Numéro de page (défaut: 1)
- `per_page` (int, optionnel) - Éléments par page (défaut: 10, max: 100)

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Contacts récupérés avec succès",
  "data": {
    "contacts": [
      {
        "id": 1,
        "client_id": 2,
        "supplier_id": null,
        "civility": "M.",
        "first_name": "Jean",
        "last_name": "Dupont",
        "function": "Directeur Commercial",
        "department": "Ventes",
        "is_primary": true,
        "is_active": true,
        "created_by": 1,
        "created_at": "2026-01-10T06:36:19.000000Z",
        "updated_at": "2026-01-10T06:36:19.000000Z",
        "full_name": "M. Jean Dupont",
        "primary_email": "jean.dupont@test.com",
        "primary_phone": "0123456789",
        "emails": [
          {
            "id": 1,
            "contact_id": 1,
            "email": "jean.dupont@test.com",
            "type": "professionnel",
            "is_primary": true,
            "created_at": "2026-01-10T06:36:19.000000Z",
            "updated_at": "2026-01-10T06:36:19.000000Z"
          },
          {
            "id": 2,
            "contact_id": 1,
            "email": "jean.perso@gmail.com",
            "type": "personnel",
            "is_primary": false,
            "created_at": "2026-01-10T06:36:19.000000Z",
            "updated_at": "2026-01-10T06:36:19.000000Z"
          }
        ],
        "phones": [
          {
            "id": 1,
            "contact_id": 1,
            "phone": "0123456789",
            "type": "bureau",
            "is_primary": true,
            "created_at": "2026-01-10T06:36:19.000000Z",
            "updated_at": "2026-01-10T06:36:19.000000Z"
          },
          {
            "id": 2,
            "contact_id": 1,
            "phone": "0987654321",
            "type": "mobile",
            "is_primary": false,
            "created_at": "2026-01-10T06:36:19.000000Z",
            "updated_at": "2026-01-10T06:36:19.000000Z"
          }
        ],
        "creator": {
          "id": 1,
          "name": "Test User"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 1,
      "total_items": 1,
      "per_page": 10
    },
    "client": {
      "id": 2,
      "client_id": "CLI-ABC123XYZ4",
      "name": "Entreprise Test"
    }
    // OU pour un fournisseur :
    "supplier": {
      "id": 4,
      "supplier_id": "FOUR-YXZU82DM",
      "name": "ElectroTech Distribution"
    }
  }
}
```

---

### 4. Créer un contact

**POST** `/clients/{clientId}/contacts` ou **POST** `/suppliers/{supplierId}/contacts`

Créer un nouveau contact pour un client ou fournisseur spécifique avec gestion automatique du contact principal.

```bash
# Pour un client
curl -X POST http://localhost:8000/api/v1/clients/2/contacts \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "civility": "M.",
    "first_name": "Jean",
    "last_name": "Dupont",
    "function": "Directeur Commercial",
    "department": "Ventes",
    "emails": [
      {
        "email": "jean.dupont@client.com",
        "type": "professionnel",
        "is_primary": true
      }
    ],
    "phones": [
      {
        "phone": "0123456789",
        "type": "bureau",
        "is_primary": true
      }
    ]
  }'

# Pour un fournisseur
curl -X POST http://localhost:8000/api/v1/suppliers/4/contacts \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "civility": "M.",
    "first_name": "Pierre",
    "last_name": "Dubois",
    "function": "Responsable Commercial",
    "department": "Ventes",
    "emails": [
      {
        "email": "pierre.dubois@supplier.com",
        "type": "professionnel",
        "is_primary": true
      }
    ]
  }'
```

**Champs requis :**
- `first_name` (string) - Prénom
- `last_name` (string) - Nom
- `emails` (array) - Au moins un email requis

**Champs optionnels :**
- `civility` (enum) - M., Mme, Dr., Prof., Maître
- `function` (string) - Fonction/poste
- `department` (string) - Service/département
- `is_primary` (boolean) - Contact principal (auto si premier contact)
- `phones` (array) - Téléphones (optionnels)

**Validation d'unicité :**
- Email unique par **client** (pour contacts clients)
- Email unique par **fournisseur** (pour contacts fournisseurs)
- Emails peuvent être identiques entre différents clients/fournisseurs

**Types d'emails :**
- `professionnel` (défaut)
- `personnel`

**Types de téléphones :**
- `bureau` (défaut)
- `mobile`
- `fax`
- `autre`

**Réponse (201 Created) :**
```json
{
  "success": true,
  "message": "Contact créé avec succès",
  "data": {
    "id": 1,
    "client_id": 2,        // Pour client OU null pour fournisseur
    "supplier_id": null,   // Pour fournisseur OU null pour client
    "civility": "M.",
    "first_name": "Jean",
    "last_name": "Dupont",
    "function": "Directeur Commercial",
    "department": "Ventes",
    "is_primary": true,
    "is_active": true,
    "created_by": 1,
    "created_at": "2026-01-10T06:36:19.000000Z",
    "updated_at": "2026-01-10T06:36:19.000000Z",
    "full_name": "M. Jean Dupont",
    "primary_email": "jean.dupont@test.com",
    "primary_phone": "0123456789",
    "emails": [...],
    "phones": [...],
    // Pour un client :
    "client": {
      "id": 2,
      "client_id": "CLI-ABC123XYZ4",
      "name": "Entreprise Test"
    }
    // Pour un fournisseur :
    "supplier": {
      "id": 4,
      "supplier_id": "FOUR-YXZU82DM",
      "name": "ElectroTech Distribution"
    }
  }
}
```

**Erreurs possibles :**
- `422` - Erreur de validation (email déjà utilisé, champs requis, etc.)
- `404` - Client ou fournisseur non trouvé

---

### 5. Détails d'un contact

**GET** `/contacts/{id}`

Récupérer les détails complets d'un contact spécifique.

```bash
curl -X GET http://localhost:8000/api/v1/contacts/1 \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Contact trouvé",
  "data": {
    "id": 1,
    "client_id": 2,
    "civility": "M.",
    "first_name": "Jean",
    "last_name": "Dupont",
    "function": "Directeur Commercial",
    "department": "Ventes",
    "is_primary": true,
    "is_active": true,
    "created_by": 1,
    "created_at": "2026-01-10T06:36:19.000000Z",
    "updated_at": "2026-01-10T06:36:19.000000Z",
    "full_name": "M. Jean Dupont",
    "primary_email": "jean.dupont@test.com",
    "primary_phone": "0123456789",
    "emails": [...],
    "phones": [...],
    "client": {
      "id": 2,
      "client_id": "CLI-ABC123XYZ4",
      "name": "Entreprise Test"
    },
    "creator": {
      "id": 1,
      "name": "Test User"
    }
  }
}
```

**Erreurs possibles :**
- `404` - Contact non trouvé

---

### 6. Modifier un contact

**PUT** `/contacts/{id}`

Mettre à jour les informations d'un contact existant.

```bash
curl -X PUT http://localhost:8000/api/v1/contacts/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "function": "Directeur Général",
    "department": "Direction",
    "emails": [
      {
        "email": "jean.directeur@test.com",
        "type": "professionnel",
        "is_primary": true
      }
    ],
    "phones": [
      {
        "phone": "0111111111",
        "type": "bureau",
        "is_primary": true
      }
    ]
  }'
```

**Champs modifiables :**
- Tous les champs sauf `id`, `client_id`, `created_by`
- Les champs non fournis restent inchangés
- Validation d'unicité pour les emails dans le client

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Contact mis à jour avec succès",
  "data": {
    // Contact modifié avec nouvelles valeurs
  }
}
```

**Erreurs possibles :**
- `404` - Contact non trouvé
- `422` - Erreur de validation (email déjà utilisé, etc.)

---

### 7. Définir un contact comme principal

**PUT** `/contacts/{id}/make-primary`

Définir un contact comme contact principal du client. L'ancien contact principal perd automatiquement ce statut.

```bash
curl -X PUT http://localhost:8000/api/v1/contacts/2/make-primary \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Contact défini comme principal",
  "data": {
    "id": 2,
    "client_id": 2,
    "civility": "Mme",
    "first_name": "Marie",
    "last_name": "Martin",
    "function": "Assistante Direction",
    "department": null,
    "is_primary": true,
    "is_active": true,
    "created_by": 1,
    "created_at": "2026-01-10T06:41:52.000000Z",
    "updated_at": "2026-01-10T06:42:26.000000Z",
    "full_name": "Mme Marie Martin",
    "primary_email": "marie.martin@test.com",
    "primary_phone": null
  }
}
```

**Erreurs possibles :**
- `404` - Contact non trouvé

---

### 8. Supprimer un contact

**DELETE** `/contacts/{id}`

Supprimer (désactiver) un contact. Il s'agit d'une suppression logique (soft delete).

```bash
curl -X DELETE http://localhost:8000/api/v1/contacts/1 \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Contact supprimé avec succès",
  "data": null
}
```

**Règles de suppression :**
- **Soft delete** : Le contact est marqué comme `is_active: false`
- **Protection contact principal** : Impossible de supprimer le contact principal s'il y a d'autres contacts actifs
- **Dernier contact** : Le contact principal peut être supprimé s'il est le seul contact du client

**Erreurs possibles :**
- `404` - Contact non trouvé
- `422` - Impossible de supprimer le contact principal (définir d'abord un autre contact comme principal)

---

## Règles métier importantes

### Contact principal
- **Un seul contact principal par entité** (client OU fournisseur)
- Le **premier contact** créé devient automatiquement principal
- Changement automatique : définir un nouveau principal **dépromeut** l'ancien
- **Protection** : impossible de supprimer le contact principal s'il y a d'autres contacts

### Emails
- **Au moins un email requis** par contact
- **Unicité par entité** :
  - Email unique par **client** (pour contacts clients)
  - Email unique par **fournisseur** (pour contacts fournisseurs)
  - Même email autorisé entre différents clients/fournisseurs
- **Un email principal** par contact maximum
- Si aucun email n'est marqué comme principal, le premier devient principal

### Téléphones
- **Optionnels** (aucun téléphone requis)
- **Un téléphone principal** par contact maximum
- Si aucun téléphone n'est marqué comme principal, le premier devient principal

### Audit et logs
- Toutes les opérations CUD sont automatiquement loggées
- Logs incluent l'utilisateur qui a effectué l'action
- Historique conservé pour traçabilité

---

## Codes d'erreur

- `200` - Succès
- `201` - Créé avec succès
- `401` - Non authentifié (token manquant/invalide)
- `404` - Ressource non trouvée (contact ou client)
- `422` - Erreur de validation
- `500` - Erreur serveur

---

## Exemples d'intégration

### JavaScript/Fetch

```javascript
class ContactsAPI {
  constructor(token) {
    this.baseURL = 'http://localhost:8000/api/v1';
    this.token = token;
  }

  async getAllContacts(page = 1, perPage = 15, search = '', entityType = '') {
    try {
      let url = `${this.baseURL}/contacts?page=${page}&per_page=${perPage}`;

      if (search) {
        url += `&search=${encodeURIComponent(search)}`;
      }

      if (entityType) {
        url += `&entity_type=${entityType}`;
      }

      const response = await fetch(url, {
        headers: {
          'Authorization': `Bearer ${this.token}`,
        }
      });

      const data = await response.json();
      return data.data;
    } catch (error) {
      console.error('Erreur récupération tous les contacts:', error);
      throw error;
    }
  }

  async getClientContacts(clientId, page = 1, perPage = 10) {
    try {
      const response = await fetch(
        `${this.baseURL}/clients/${clientId}/contacts?page=${page}&per_page=${perPage}`,
        {
          headers: {
            'Authorization': `Bearer ${this.token}`,
          }
        }
      );

      const data = await response.json();
      return data.data;
    } catch (error) {
      console.error('Erreur récupération contacts client:', error);
      throw error;
    }
  }

  async getSupplierContacts(supplierId, page = 1, perPage = 10) {
    try {
      const response = await fetch(
        `${this.baseURL}/suppliers/${supplierId}/contacts?page=${page}&per_page=${perPage}`,
        {
          headers: {
            'Authorization': `Bearer ${this.token}`,
          }
        }
      );

      const data = await response.json();
      return data.data;
    } catch (error) {
      console.error('Erreur récupération contacts fournisseur:', error);
      throw error;
    }
  }

  async createClientContact(clientId, contactData) {
    try {
      const response = await fetch(`${this.baseURL}/clients/${clientId}/contacts`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${this.token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(contactData)
      });

      const data = await response.json();

      if (data.success) {
        return data.data;
      } else {
        throw new Error(data.message);
      }
    } catch (error) {
      console.error('Erreur création contact client:', error);
      throw error;
    }
  }

  async createSupplierContact(supplierId, contactData) {
    try {
      const response = await fetch(`${this.baseURL}/suppliers/${supplierId}/contacts`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${this.token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(contactData)
      });

      const data = await response.json();

      if (data.success) {
        return data.data;
      } else {
        throw new Error(data.message);
      }
    } catch (error) {
      console.error('Erreur création contact fournisseur:', error);
      throw error;
    }
  }

  async updateContact(contactId, updateData) {
    try {
      const response = await fetch(`${this.baseURL}/contacts/${contactId}`, {
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
      console.error('Erreur modification contact:', error);
      throw error;
    }
  }

  async makePrimary(contactId) {
    try {
      const response = await fetch(`${this.baseURL}/contacts/${contactId}/make-primary`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${this.token}`,
        }
      });

      const data = await response.json();
      return data.data;
    } catch (error) {
      console.error('Erreur définition contact principal:', error);
      throw error;
    }
  }

  async deleteContact(contactId) {
    try {
      const response = await fetch(`${this.baseURL}/contacts/${contactId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${this.token}`,
        }
      });

      const data = await response.json();
      return data.success;
    } catch (error) {
      console.error('Erreur suppression contact:', error);
      throw error;
    }
  }
}

// Utilisation
const api = new ContactsAPI('your-bearer-token');

// Récupérer TOUS les contacts (vue d'ensemble)
const allContacts = await api.getAllContacts(1, 15);

// Rechercher des contacts globalement
const searchResults = await api.getAllContacts(1, 15, 'jean');

// Filtrer par type d'entité
const clientContactsOnly = await api.getAllContacts(1, 15, '', 'client');
const supplierContactsOnly = await api.getAllContacts(1, 15, '', 'supplier');

// Créer un contact pour un client
const newClientContact = await api.createClientContact(2, {
  first_name: 'Jean',
  last_name: 'Dupont',
  function: 'Directeur',
  emails: [
    {
      email: 'jean.dupont@client.com',
      type: 'professionnel',
      is_primary: true
    }
  ]
});

// Créer un contact pour un fournisseur
const newSupplierContact = await api.createSupplierContact(4, {
  first_name: 'Pierre',
  last_name: 'Dubois',
  function: 'Responsable Commercial',
  emails: [
    {
      email: 'pierre.dubois@supplier.com',
      type: 'professionnel',
      is_primary: true
    }
  ]
});

// Récupérer les contacts d'un client
const clientContacts = await api.getClientContacts(2, 1, 10);

// Récupérer les contacts d'un fournisseur
const supplierContacts = await api.getSupplierContacts(4, 1, 10);

// Définir comme contact principal
await api.makePrimary(2);
```

### PHP/cURL

```php
class ContactsAPI {
    private $baseURL = 'http://localhost:8000/api/v1';
    private $token;

    public function __construct($token) {
        $this->token = $token;
    }

    private function makeRequest($method, $url, $data = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseURL . $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function createClientContact($clientId, $contactData) {
        return $this->makeRequest('POST', "/clients/{$clientId}/contacts", $contactData);
    }

    public function createSupplierContact($supplierId, $contactData) {
        return $this->makeRequest('POST', "/suppliers/{$supplierId}/contacts", $contactData);
    }

    public function getAllContacts($page = 1, $perPage = 15, $search = '', $entityType = '') {
        $query = "page={$page}&per_page={$perPage}";

        if (!empty($search)) {
            $query .= "&search=" . urlencode($search);
        }

        if (!empty($entityType)) {
            $query .= "&entity_type={$entityType}";
        }

        return $this->makeRequest('GET', "/contacts?{$query}");
    }

    public function getClientContacts($clientId, $page = 1, $perPage = 10) {
        return $this->makeRequest('GET', "/clients/{$clientId}/contacts?page={$page}&per_page={$perPage}");
    }

    public function getSupplierContacts($supplierId, $page = 1, $perPage = 10) {
        return $this->makeRequest('GET', "/suppliers/{$supplierId}/contacts?page={$page}&per_page={$perPage}");
    }

    public function makePrimary($contactId) {
        return $this->makeRequest('PUT', "/contacts/{$contactId}/make-primary");
    }

    public function deleteContact($contactId) {
        return $this->makeRequest('DELETE', "/contacts/{$contactId}");
    }
}

// Utilisation
$api = new ContactsAPI('your-bearer-token');

// Récupérer TOUS les contacts
$allContacts = $api->getAllContacts(1, 15);

// Rechercher des contacts globalement
$searchResults = $api->getAllContacts(1, 15, 'jean');

// Filtrer par type d'entité
$clientContacts = $api->getAllContacts(1, 15, '', 'client');
$supplierContacts = $api->getAllContacts(1, 15, '', 'supplier');

// Créer un contact pour un client
$result = $api->createClientContact(2, [
    'first_name' => 'Jean',
    'last_name' => 'Dupont',
    'emails' => [
        [
            'email' => 'jean@client.com',
            'type' => 'professionnel',
            'is_primary' => true
        ]
    ]
]);

// Créer un contact pour un fournisseur
$result = $api->createSupplierContact(4, [
    'first_name' => 'Pierre',
    'last_name' => 'Dubois',
    'emails' => [
        [
            'email' => 'pierre@supplier.com',
            'type' => 'professionnel',
            'is_primary' => true
        ]
    ]
]);
```

---

## Gestion des erreurs de validation

### Exemple d'erreur 422 - Email déjà utilisé :
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "emails.0.email": ["Cet email est déjà utilisé par un autre contact de ce client."]
  }
}

// Ou pour un fournisseur :
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "emails.0.email": ["Cet email est déjà utilisé par un autre contact de ce fournisseur."]
  }
}
```

### Exemple d'erreur 422 - Contact principal :
```json
{
  "success": false,
  "message": "Impossible de supprimer le contact principal. Définissez d'abord un autre contact comme principal."
}
```

### Exemple d'erreur 422 - Champs requis :
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "first_name": ["Le prénom est requis"],
    "last_name": ["Le nom est requis"],
    "emails": ["Au moins un email est requis"]
  }
}
```

---

## Fonctionnalités avancées

### Tri automatique
- Les contacts sont automatiquement triés avec le **contact principal en premier**
- Puis par nom de famille et prénom

### Pagination intelligente
- Pagination automatique si plus de 10 contacts
- Paramètres `page` et `per_page` flexibles
- Informations de pagination complètes dans la réponse

### Audit et traçabilité
- Tous les CUD (Create, Update, Delete) sont loggés
- Information du créateur/modificateur incluse
- Historique conservé pour compliance

### Accesseurs calculés
- `full_name` : Civilité + Prénom + Nom
- `primary_email` : Email principal du contact
- `primary_phone` : Téléphone principal du contact

---

## Documentation Swagger

La documentation interactive complète est disponible sur :
**http://localhost:8000/api/documentation**

Cette interface permet de :
- Tester tous les endpoints contacts directement
- Voir les schémas de données détaillés
- Gérer l'authentification Bearer
- Visualiser les réponses d'exemple

---

## Notes importantes

1. **Contact principal automatique** : Le premier contact créé pour une entité (client/fournisseur) devient automatiquement principal
2. **Email unique par entité** :
   - Un même email ne peut pas être utilisé par plusieurs contacts du même **client**
   - Un même email ne peut pas être utilisé par plusieurs contacts du même **fournisseur**
   - Le même email **peut** être utilisé entre différents clients/fournisseurs
3. **Soft delete** : Les contacts supprimés sont conservés en base (`is_active = false`)
4. **Protection métier** : Impossible de supprimer le contact principal s'il y a d'autres contacts
5. **Gestion automatique** : Changement de contact principal dépromeut automatiquement l'ancien
6. **Support unifié** : Même API et logique métier pour clients et fournisseurs
7. **Audit complet** : Tous les changements sont loggés avec l'utilisateur responsable
8. **Validation française** : Tous les messages d'erreur sont en français
9. **Performance** : Index optimisés sur les colonnes de recherche fréquente

---

**Version API** : 1.0.0
**Framework** : Laravel 8.x
**Authentification** : Laravel Sanctum
**Documentation** : OpenAPI 3.0