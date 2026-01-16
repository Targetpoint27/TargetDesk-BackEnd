# API Fournisseurs - Guide d'Intégration

## Vue d'ensemble

L'API de gestion des fournisseurs permet de créer, lire, modifier et supprimer des fiches fournisseurs avec des fonctionnalités avancées comme la gestion des types de relations commerciales (fournisseur simple ou client-fournisseur).

## Authentification

Tous les endpoints nécessitent une authentification Bearer Token :
```
Authorization: Bearer YOUR_TOKEN_HERE
```

## Structure des données

### Modèle Fournisseur

```json
{
  "id": 1,
  "supplier_id": "FOUR-4082BPN6",
  "name": "Fournisseur ACME",
  "type": "entreprise",
  "email": "contact@acme-supplier.com",
  "phone": "0145678901",
  "address": "456 Avenue des Fournisseurs, 75002 Paris",
  "siret": "98765432109876",
  "sector": "Distribution",
  "website": "https://acme-supplier.com",
  "notes": "Fournisseur principal pour les produits électroniques",
  "relation_type": "fournisseur",
  "payment_terms": "30 jours net",
  "delivery_delay": 7,
  "currency": "EUR",
  "is_active": true,
  "created_by": 2,
  "created_at": "2026-01-10T17:20:54.000000Z",
  "updated_at": "2026-01-10T17:20:54.000000Z",
  "creator": {
    "id": 2,
    "name": "John Doe"
  }
}
```

### Champs obligatoires
- `name` : Nom/raison sociale du fournisseur
- `type` : Type (`particulier` | `entreprise`)
- `email` : Email principal (unique)

### Champs spécifiques aux fournisseurs
- `relation_type` : Type de relation (`fournisseur` | `client_et_fournisseur`)
- `payment_terms` : Conditions de paiement (ex: "30 jours net")
- `delivery_delay` : Délai de livraison en jours (0-365)
- `currency` : Code devise ISO 4217 (par défaut: "EUR")

### Validation des données
- `siret` : 14 caractères exactement, unique
- `email` : Format email valide, unique
- `website` : URL valide
- `delivery_delay` : Entier entre 0 et 365
- `currency` : Code ISO 4217 de 3 caractères

## Endpoints

### 1. Créer un fournisseur
**POST** `/api/v1/suppliers`

#### Paramètres requis
```json
{
  "name": "string",
  "type": "particulier|entreprise",
  "email": "string"
}
```

#### Paramètres optionnels
```json
{
  "phone": "string",
  "address": "string",
  "siret": "string",
  "sector": "string",
  "website": "string",
  "notes": "string",
  "relation_type": "fournisseur|client_et_fournisseur",
  "payment_terms": "string",
  "delivery_delay": "integer",
  "currency": "string"
}
```

#### Exemple de requête
```bash
curl -X POST "http://your-domain.com/api/v1/suppliers" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Fournisseur ACME",
    "type": "entreprise",
    "email": "contact@acme-supplier.com",
    "phone": "0145678901",
    "address": "456 Avenue des Fournisseurs, 75002 Paris",
    "siret": "98765432109876",
    "sector": "Distribution",
    "website": "https://acme-supplier.com",
    "notes": "Fournisseur principal",
    "relation_type": "fournisseur",
    "payment_terms": "30 jours net",
    "delivery_delay": 7,
    "currency": "EUR"
  }'
```

#### Réponse de succès (201)
```json
{
  "success": true,
  "message": "Fournisseur créé avec succès",
  "data": {
    "id": 1,
    "supplier_id": "FOUR-4082BPN6",
    "name": "Fournisseur ACME",
    "type": "entreprise",
    "email": "contact@acme-supplier.com",
    "relation_type": "fournisseur",
    "payment_terms": "30 jours net",
    "delivery_delay": 7,
    "currency": "EUR",
    "is_active": true,
    "created_at": "2026-01-10T17:20:54.000000Z"
  }
}
```

### 2. Lister les fournisseurs
**GET** `/api/v1/suppliers`

#### Paramètres de query optionnels
- `page` : Numéro de page (défaut: 1)
- `per_page` : Éléments par page (max 100, défaut: 15)
- `relation_type` : Filtrer par type de relation (`fournisseur` | `client_et_fournisseur`)

#### Exemple de requête
```bash
# Tous les fournisseurs
curl -X GET "http://your-domain.com/api/v1/suppliers" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Filtrer par type de relation
curl -X GET "http://your-domain.com/api/v1/suppliers?relation_type=client_et_fournisseur" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Pagination
curl -X GET "http://your-domain.com/api/v1/suppliers?page=2&per_page=10" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Réponse de succès (200)
```json
{
  "success": true,
  "message": "Fournisseurs récupérés avec succès",
  "data": {
    "suppliers": [
      {
        "id": 1,
        "supplier_id": "FOUR-4082BPN6",
        "name": "Fournisseur ACME",
        "relation_type": "fournisseur",
        "payment_terms": "30 jours net",
        "delivery_delay": 7,
        "currency": "EUR",
        "creator": {
          "id": 2,
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

### 3. Récupérer un fournisseur
**GET** `/api/v1/suppliers/{id}`

#### Exemple de requête
```bash
curl -X GET "http://your-domain.com/api/v1/suppliers/1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Réponse de succès (200)
```json
{
  "success": true,
  "message": "Fournisseur trouvé",
  "data": {
    "id": 1,
    "supplier_id": "FOUR-4082BPN6",
    "name": "Fournisseur ACME",
    "type": "entreprise",
    "email": "contact@acme-supplier.com",
    "relation_type": "fournisseur",
    "payment_terms": "30 jours net",
    "delivery_delay": 7,
    "currency": "EUR",
    "creator": {
      "id": 2,
      "name": "John Doe"
    }
  }
}
```

### 4. Modifier un fournisseur
**PUT** `/api/v1/suppliers/{id}`

#### Exemple de requête
```bash
curl -X PUT "http://your-domain.com/api/v1/suppliers/1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "payment_terms": "60 jours net",
    "delivery_delay": 10,
    "notes": "Conditions négociées"
  }'
```

#### Réponse de succès (200)
```json
{
  "success": true,
  "message": "Fournisseur mis à jour avec succès",
  "data": {
    "id": 1,
    "supplier_id": "FOUR-4082BPN6",
    "payment_terms": "60 jours net",
    "delivery_delay": 10,
    "notes": "Conditions négociées",
    "updated_at": "2026-01-10T17:23:47.000000Z"
  }
}
```

### 5. Supprimer un fournisseur
**DELETE** `/api/v1/suppliers/{id}`

#### Exemple de requête
```bash
curl -X DELETE "http://your-domain.com/api/v1/suppliers/1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Réponse de succès (200)
```json
{
  "success": true,
  "message": "Fournisseur supprimé avec succès",
  "data": null
}
```

## Gestion des erreurs

### Erreurs de validation (422)
```json
{
  "success": false,
  "message": "Erreurs de validation",
  "errors": {
    "email": ["Cet email est déjà utilisé"],
    "siret": ["Le SIRET doit contenir exactement 14 caractères"]
  }
}
```

### Fournisseur non trouvé (404)
```json
{
  "success": false,
  "message": "Fournisseur non trouvé"
}
```

### Non autorisé (401)
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

## Exemples d'intégration

### JavaScript (Fetch API)

```javascript
class SuppliersAPI {
  constructor(baseURL, token) {
    this.baseURL = baseURL;
    this.token = token;
  }

  async createSupplier(supplierData) {
    const response = await fetch(`${this.baseURL}/api/v1/suppliers`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(supplierData)
    });
    return response.json();
  }

  async getSuppliers(page = 1, relationType = null) {
    let url = `${this.baseURL}/api/v1/suppliers?page=${page}`;
    if (relationType) {
      url += `&relation_type=${relationType}`;
    }

    const response = await fetch(url, {
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json'
      }
    });
    return response.json();
  }

  async getSupplier(id) {
    const response = await fetch(`${this.baseURL}/api/v1/suppliers/${id}`, {
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json'
      }
    });
    return response.json();
  }

  async updateSupplier(id, updateData) {
    const response = await fetch(`${this.baseURL}/api/v1/suppliers/${id}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(updateData)
    });
    return response.json();
  }

  async deleteSupplier(id) {
    const response = await fetch(`${this.baseURL}/api/v1/suppliers/${id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json'
      }
    });
    return response.json();
  }
}

// Utilisation
const api = new SuppliersAPI('http://your-domain.com', 'your-token');

// Créer un fournisseur
const newSupplier = await api.createSupplier({
  name: 'Tech Solutions',
  type: 'entreprise',
  email: 'contact@techsolutions.fr',
  relation_type: 'client_et_fournisseur',
  payment_terms: '30 jours fin de mois',
  delivery_delay: 14,
  currency: 'EUR'
});

// Lister les partenaires (clients et fournisseurs)
const partners = await api.getSuppliers(1, 'client_et_fournisseur');
```

### PHP

```php
<?php

class SuppliersAPI {
    private $baseURL;
    private $token;

    public function __construct($baseURL, $token) {
        $this->baseURL = rtrim($baseURL, '/');
        $this->token = $token;
    }

    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->baseURL . $endpoint;
        $headers = [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers
        ]);

        if ($data && in_array($method, ['POST', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status_code' => $httpCode,
            'data' => json_decode($response, true)
        ];
    }

    public function createSupplier($supplierData) {
        return $this->makeRequest('POST', '/api/v1/suppliers', $supplierData);
    }

    public function getSuppliers($page = 1, $relationType = null) {
        $query = "?page=$page";
        if ($relationType) {
            $query .= "&relation_type=$relationType";
        }
        return $this->makeRequest('GET', "/api/v1/suppliers$query");
    }

    public function getSupplier($id) {
        return $this->makeRequest('GET', "/api/v1/suppliers/$id");
    }

    public function updateSupplier($id, $updateData) {
        return $this->makeRequest('PUT', "/api/v1/suppliers/$id", $updateData);
    }

    public function deleteSupplier($id) {
        return $this->makeRequest('DELETE', "/api/v1/suppliers/$id");
    }
}

// Utilisation
$api = new SuppliersAPI('http://your-domain.com', 'your-token');

// Créer un fournisseur
$result = $api->createSupplier([
    'name' => 'ACME Distribution',
    'type' => 'entreprise',
    'email' => 'contact@acme-distrib.com',
    'relation_type' => 'fournisseur',
    'payment_terms' => '45 jours fin de mois',
    'delivery_delay' => 7,
    'currency' => 'EUR'
]);

if ($result['status_code'] === 201) {
    echo "Fournisseur créé avec l'ID: " . $result['data']['data']['supplier_id'];
}
?>
```

## Bonnes pratiques

### 1. Gestion des types de relation
- Utilisez `fournisseur` pour les fournisseurs simples
- Utilisez `client_et_fournisseur` pour les partenaires commerciaux
- Filtrez les listes par `relation_type` pour des vues spécialisées

### 2. Gestion des conditions de paiement
- Stockez les conditions sous forme de texte libre (`"30 jours net"`, `"45 jours fin de mois"`)
- Normalisez les formats pour faciliter le traitement automatique

### 3. Délais de livraison
- Stockez en nombre de jours (entier)
- Validez que la valeur est raisonnable (0-365 jours)

### 4. Devises
- Utilisez les codes ISO 4217 (EUR, USD, GBP, etc.)
- EUR par défaut pour la cohérence

### 5. Pagination
- Utilisez les paramètres `page` et `per_page`
- Limitez `per_page` à 100 maximum pour les performances

### 6. Gestion d'erreurs
- Vérifiez toujours le champ `success` dans la réponse
- Gérez les erreurs de validation avec le champ `errors`
- Implémentez des retry pour les erreurs temporaires

## Audit et logs

Toutes les opérations CRUD sur les fournisseurs sont automatiquement loggées avec :
- ID du fournisseur
- Action effectuée
- Utilisateur responsable
- Timestamp
- Détails des modifications (pour les mises à jour)

Consultez les logs Laravel pour le suivi des opérations :
```bash
tail -f storage/logs/laravel.log | grep "Fournisseur"
```

## Swagger Documentation

La documentation interactive Swagger est disponible à :
```
http://your-domain.com/api/documentation
```

Section "Suppliers" pour tester les endpoints en direct.