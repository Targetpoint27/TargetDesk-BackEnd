# API Catégories TargetDesk - Guide d'intégration

## Vue d'ensemble

L'API Catégories permet de segmenter et classifier les **clients** avec un système de catégories hiérarchiques :
- Gestion complète CRUD des catégories
- Hiérarchisation parent/enfant illimitée
- 5 types prédéfinis : Secteur, Taille, Priorité, Origine, Personnalisée
- Codes couleur pour l'affichage visuel
- Assignation multiple de catégories par client
- Audit logging complet

## Base URL
```
http://localhost:8000/api/v1
```

## Authentification
Tous les endpoints catégories nécessitent une authentification Bearer token.

```
Authorization: Bearer {token}
```

---

## Endpoints disponibles

### 1. Lister toutes les catégories

**GET** `/categories`

Récupérer toutes les catégories avec options de filtrage et structure hiérarchique.

```bash
# Toutes les catégories (liste plate)
curl -X GET "http://localhost:8000/api/v1/categories" \
  -H "Authorization: Bearer {token}"

# Structure hiérarchique
curl -X GET "http://localhost:8000/api/v1/categories?hierarchical=true" \
  -H "Authorization: Bearer {token}"

# Filtrer par type
curl -X GET "http://localhost:8000/api/v1/categories?type=secteur" \
  -H "Authorization: Bearer {token}"
```

**Paramètres de requête :**
- `type` (enum, optionnel) - Filtrer par type : "secteur", "taille", "priorite", "origine", "personnalisee"
- `hierarchical` (boolean, optionnel) - Structure hiérarchique (défaut: false)
- `include_children` (boolean, optionnel) - Inclure les enfants (défaut: true)

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Catégories récupérées avec succès",
  "data": [
    {
      "id": 1,
      "name": "Industrie Automobile",
      "description": "Secteur de l'industrie automobile",
      "parent_id": null,
      "color": "#FF6B6B",
      "type": "secteur",
      "is_active": true,
      "created_by": 1,
      "clients_count": 2,
      "children_count": 1,
      "depth_level": 0,
      "full_path": "Industrie Automobile",
      "formatted_type": "Secteur",
      "creator": {
        "id": 1,
        "name": "Admin User"
      },
      "parent": null
    },
    {
      "id": 5,
      "name": "Constructeurs",
      "description": "Constructeurs automobiles",
      "parent_id": 1,
      "color": "#FF8E53",
      "type": "secteur",
      "depth_level": 1,
      "full_path": "Industrie Automobile > Constructeurs",
      "parent": {
        "id": 1,
        "name": "Industrie Automobile"
      }
    }
  ]
}
```

---

### 2. Créer une catégorie

**POST** `/categories`

Créer une nouvelle catégorie avec hiérarchisation optionnelle.

```bash
curl -X POST "http://localhost:8000/api/v1/categories" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "PME",
    "description": "Petites et moyennes entreprises",
    "type": "taille",
    "color": "#4ECDC4",
    "parent_id": null
  }'
```

**Champs requis :**
- `name` (string) - Nom unique de la catégorie (2-100 caractères)
- `type` (enum) - Type parmi : "secteur", "taille", "priorite", "origine", "personnalisee"

**Champs optionnels :**
- `description` (string) - Description (max 500 caractères)
- `parent_id` (integer) - ID de la catégorie parent pour hiérarchisation
- `color` (string) - Code couleur hexadécimal (défaut: #007bff)

**Types de catégories :**
- `secteur` - Domaine d'activité (Automobile, Telecom, Finance, etc.)
- `taille` - Taille de l'entreprise (PME, ETI, Grand Groupe, etc.)
- `priorite` - Niveau de priorité (VIP, Premium, Standard, etc.)
- `origine` - Source d'acquisition (Web, Référencement, Salon, etc.)
- `personnalisee` - Catégorie personnalisée

**Réponse (201 Created) :**
```json
{
  "success": true,
  "message": "Catégorie créée avec succès",
  "data": {
    "id": 2,
    "name": "PME",
    "description": "Petites et moyennes entreprises",
    "parent_id": null,
    "color": "#4ECDC4",
    "type": "taille",
    "full_path": "PME",
    "formatted_type": "Taille",
    "creator": {
      "id": 1,
      "name": "Admin User"
    }
  }
}
```

---

### 3. Détails d'une catégorie

**GET** `/categories/{id}`

Récupérer les détails complets d'une catégorie avec statistiques.

```bash
curl -X GET "http://localhost:8000/api/v1/categories/1" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Catégorie trouvée",
  "data": {
    "id": 1,
    "name": "Industrie Automobile",
    "description": "Secteur de l'industrie automobile",
    "color": "#FF6B6B",
    "type": "secteur",
    "clients_count": 2,
    "children_count": 1,
    "depth_level": 0,
    "full_path": "Industrie Automobile",
    "children": [
      {
        "id": 5,
        "name": "Constructeurs",
        "parent_id": 1,
        "color": "#FF8E53"
      }
    ],
    "recent_clients": [
      {
        "id": 2,
        "client_id": "CLI-KDY2WCD4N5",
        "name": "Entreprise Test"
      }
    ]
  }
}
```

---

### 4. Modifier une catégorie

**PUT** `/categories/{id}`

Mettre à jour une catégorie existante avec validation des références circulaires.

```bash
curl -X PUT "http://localhost:8000/api/v1/categories/2" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Petites Entreprises",
    "description": "Entreprises de moins de 50 employés",
    "color": "#32CD32"
  }'
```

**Champs modifiables :**
- Tous les champs sauf `id`, `created_by`
- Validation automatique des références circulaires pour `parent_id`
- Nom unique requis si modifié

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Catégorie mise à jour avec succès",
  "data": {
    "id": 2,
    "name": "Petites Entreprises",
    "description": "Entreprises de moins de 50 employés",
    "color": "#32CD32"
  }
}
```

---

### 5. Supprimer une catégorie

**DELETE** `/categories/{id}`

Supprimer (désactiver) une catégorie et tous ses enfants automatiquement.

```bash
curl -X DELETE "http://localhost:8000/api/v1/categories/4" \
  -H "Authorization: Bearer {token}"
```

**Règles de suppression :**
- **Soft delete** : Catégorie marquée comme `is_active: false`
- **Suppression en cascade** : Tous les enfants sont désactivés récursivement
- **Désassignation automatique** : Retirée de tous les clients assignés

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Catégorie supprimée avec succès",
  "data": null
}
```

---

## Assignation aux clients

### 6. Assigner des catégories à un client

**POST** `/clients/{clientId}/categories`

Assigner une ou plusieurs catégories à un client spécifique.

```bash
curl -X POST "http://localhost:8000/api/v1/clients/2/categories" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "category_ids": [1, 2, 3]
  }'
```

**Champs requis :**
- `category_ids` (array) - Tableau des IDs de catégories (minimum 1)

**Fonctionnalités :**
- **Assignation multiple** : Plusieurs catégories en une fois
- **Pas de doublons** : Évite les assignations en double
- **Audit automatique** : Enregistrement de qui a assigné et quand
- **Additive** : N'enlève pas les catégories existantes

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Catégories assignées avec succès",
  "data": {
    "client": {
      "id": 2,
      "client_id": "CLI-KDY2WCD4N5",
      "name": "Entreprise Test"
    },
    "categories": [
      {
        "id": 1,
        "name": "Industrie Automobile",
        "color": "#FF6B6B",
        "type": "secteur",
        "pivot": {
          "assigned_by": 1,
          "assigned_at": "2026-01-15 12:59:12"
        }
      }
    ]
  }
}
```

---

### 7. Récupérer les catégories d'un client

**GET** `/clients/{clientId}/categories`

Obtenir toutes les catégories assignées à un client spécifique.

```bash
curl -X GET "http://localhost:8000/api/v1/clients/2/categories" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Catégories du client récupérées avec succès",
  "data": {
    "client": {
      "id": 2,
      "client_id": "CLI-KDY2WCD4N5",
      "name": "Entreprise Test"
    },
    "categories": [
      {
        "id": 1,
        "name": "Industrie Automobile",
        "full_path": "Industrie Automobile",
        "formatted_type": "Secteur",
        "color": "#FF6B6B",
        "pivot": {
          "assigned_by": 1,
          "assigned_at": "2026-01-15 12:59:12"
        }
      }
    ],
    "categories_count": 1
  }
}
```

---

### 8. Retirer une catégorie d'un client

**DELETE** `/clients/{clientId}/categories/{categoryId}`

Retirer une catégorie spécifique d'un client.

```bash
curl -X DELETE "http://localhost:8000/api/v1/clients/2/categories/3" \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Catégorie retirée du client avec succès",
  "data": null
}
```

---

## Intégration dans le listing des clients

Le endpoint **GET** `/clients` retourne maintenant les informations de catégories pour chaque client :

```bash
curl -X GET "http://localhost:8000/api/v1/clients" \
  -H "Authorization: Bearer {token}"
```

**Structure enrichie :**
```json
{
  "success": true,
  "message": "Clients récupérés avec succès",
  "data": {
    "clients": [
      {
        "id": 2,
        "client_id": "CLI-KDY2WCD4N5",
        "name": "Entreprise Test",
        "type": "entreprise",
        "email": "contact@test.com",
        "categories_count": 2,
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
                "name": "Petites Entreprises",
                "color": "#32CD32"
              }
            ]
          }
        ],
        "categories": [
          {
            "id": 1,
            "name": "Industrie Automobile",
            "color": "#FF6B6B",
            "type": "secteur"
          }
        ]
      }
    ]
  }
}
```

**Nouvelles propriétés :**
- `categories_count` - Nombre total de catégories assignées
- `categories_summary` - Résumé groupé par type avec compteurs
- `categories` - Liste complète des catégories assignées

---

## Codes d'erreur

- `200` - Succès
- `201` - Créé avec succès
- `400` - Erreur dans la requête
- `401` - Non authentifié (token manquant/invalide)
- `404` - Ressource non trouvée (catégorie ou client)
- `422` - Erreur de validation
- `500` - Erreur serveur

### Exemples d'erreurs de validation

**Nom de catégorie déjà existant :**
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "name": ["Ce nom de catégorie existe déjà."]
  }
}
```

**Référence circulaire détectée :**
```json
{
  "success": false,
  "message": "Référence circulaire détectée. Une catégorie ne peut pas être parent de ses ancêtres."
}
```

**Code couleur invalide :**
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "color": ["La couleur doit être au format hexadécimal (#RRGGBB)."]
  }
}
```

---

## Exemples d'intégration

### JavaScript/Fetch

```javascript
class CategoriesAPI {
  constructor(token) {
    this.baseURL = 'http://localhost:8000/api/v1';
    this.token = token;
  }

  async getAllCategories(filters = {}) {
    const params = new URLSearchParams();

    if (filters.type) params.append('type', filters.type);
    if (filters.hierarchical) params.append('hierarchical', 'true');

    try {
      const response = await fetch(
        `${this.baseURL}/categories?${params}`,
        {
          headers: {
            'Authorization': `Bearer ${this.token}`,
          }
        }
      );

      const data = await response.json();
      return data.data;
    } catch (error) {
      console.error('Erreur récupération catégories:', error);
      throw error;
    }
  }

  async createCategory(categoryData) {
    try {
      const response = await fetch(`${this.baseURL}/categories`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${this.token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(categoryData)
      });

      const data = await response.json();

      if (data.success) {
        return data.data;
      } else {
        throw new Error(data.message);
      }
    } catch (error) {
      console.error('Erreur création catégorie:', error);
      throw error;
    }
  }

  async assignCategoriesToClient(clientId, categoryIds) {
    try {
      const response = await fetch(
        `${this.baseURL}/clients/${clientId}/categories`,
        {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${this.token}`,
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ category_ids: categoryIds })
        }
      );

      const data = await response.json();
      return data.data;
    } catch (error) {
      console.error('Erreur assignation catégories:', error);
      throw error;
    }
  }

  async getClientCategories(clientId) {
    try {
      const response = await fetch(
        `${this.baseURL}/clients/${clientId}/categories`,
        {
          headers: {
            'Authorization': `Bearer ${this.token}`,
          }
        }
      );

      const data = await response.json();
      return data.data;
    } catch (error) {
      console.error('Erreur récupération catégories client:', error);
      throw error;
    }
  }

  async removeCategoryFromClient(clientId, categoryId) {
    try {
      const response = await fetch(
        `${this.baseURL}/clients/${clientId}/categories/${categoryId}`,
        {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${this.token}`,
          }
        }
      );

      const data = await response.json();
      return data.success;
    } catch (error) {
      console.error('Erreur retrait catégorie:', error);
      throw error;
    }
  }

  async updateCategory(categoryId, updateData) {
    try {
      const response = await fetch(`${this.baseURL}/categories/${categoryId}`, {
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
      console.error('Erreur modification catégorie:', error);
      throw error;
    }
  }

  async deleteCategory(categoryId) {
    try {
      const response = await fetch(`${this.baseURL}/categories/${categoryId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${this.token}`,
        }
      });

      const data = await response.json();
      return data.success;
    } catch (error) {
      console.error('Erreur suppression catégorie:', error);
      throw error;
    }
  }
}

// Utilisation
const api = new CategoriesAPI('your-bearer-token');

// Créer une catégorie
const newCategory = await api.createCategory({
  name: 'Transport',
  description: 'Secteur du transport et logistique',
  type: 'secteur',
  color: '#E67E22'
});

// Récupérer les catégories par type
const secteurCategories = await api.getAllCategories({ type: 'secteur' });

// Structure hiérarchique
const hierarchicalCategories = await api.getAllCategories({
  hierarchical: true
});

// Assigner des catégories à un client
await api.assignCategoriesToClient(2, [1, 3, 5]);

// Récupérer les catégories d'un client
const clientCategories = await api.getClientCategories(2);

// Retirer une catégorie d'un client
await api.removeCategory FromClient(2, 3);
```

### PHP/cURL

```php
class CategoriesAPI {
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

    public function getAllCategories($filters = []) {
        $query = http_build_query($filters);
        $url = '/categories' . ($query ? '?' . $query : '');
        return $this->makeRequest('GET', $url);
    }

    public function createCategory($categoryData) {
        return $this->makeRequest('POST', '/categories', $categoryData);
    }

    public function updateCategory($categoryId, $updateData) {
        return $this->makeRequest('PUT', "/categories/{$categoryId}", $updateData);
    }

    public function deleteCategory($categoryId) {
        return $this->makeRequest('DELETE', "/categories/{$categoryId}");
    }

    public function assignCategoriesToClient($clientId, $categoryIds) {
        return $this->makeRequest('POST', "/clients/{$clientId}/categories", [
            'category_ids' => $categoryIds
        ]);
    }

    public function getClientCategories($clientId) {
        return $this->makeRequest('GET', "/clients/{$clientId}/categories");
    }

    public function removeCategoryFromClient($clientId, $categoryId) {
        return $this->makeRequest('DELETE', "/clients/{$clientId}/categories/{$categoryId}");
    }
}

// Utilisation
$api = new CategoriesAPI('your-bearer-token');

// Créer une catégorie hiérarchique
$result = $api->createCategory([
    'name' => 'Équipementiers',
    'description' => 'Fournisseurs de pièces automobiles',
    'parent_id' => 1, // Enfant de "Industrie Automobile"
    'type' => 'secteur',
    'color' => '#3498DB'
]);

// Récupérer la structure hiérarchique
$hierarchical = $api->getAllCategories(['hierarchical' => true]);

// Assigner plusieurs catégories
$api->assignCategoriesToClient(2, [1, 2, 3]);

// Statistiques des catégories
$categories = $api->getAllCategories();
foreach ($categories['data'] as $category) {
    echo "Catégorie: {$category['name']} - {$category['clients_count']} clients\n";
}
```

---

## Fonctionnalités avancées

### Hiérarchisation illimitée
- **Parent/Enfant** : Niveaux illimités de sous-catégories
- **Chemin complet** : Propriété `full_path` avec navigation complète
- **Protection circulaire** : Validation automatique des références circulaires
- **Suppression en cascade** : Enfants supprimés automatiquement avec le parent

### Types de catégories prédéfinis
- **Secteur** : Domaines d'activité (Automobile, IT, Finance, etc.)
- **Taille** : Classification par taille (PME, ETI, Grand Groupe)
- **Priorité** : Niveaux de service (VIP, Premium, Standard)
- **Origine** : Canaux d'acquisition (Web, Salon, Référencement)
- **Personnalisée** : Catégories spécifiques à l'entreprise

### Audit et traçabilité
- **Créateur** : Utilisateur qui a créé la catégorie
- **Assignation** : Qui a assigné et quand pour chaque client
- **Logs automatiques** : Toutes les opérations sont loggées
- **Historique** : Conservation de l'historique des changements

### Optimisations de performance
- **Eager loading** : Relations chargées efficacement
- **Index optimisés** : Performance sur les recherches fréquentes
- **Compteurs calculés** : `clients_count`, `children_count` automatiques
- **Pagination** : Support natif pour grandes listes

---

## Règles métier

### Validation
- **Nom unique** : Pas de doublons de noms de catégories
- **Couleur hexadécimale** : Format #RRGGBB obligatoire
- **Types contrôlés** : Uniquement les 5 types prédéfinis
- **Parent actif** : Parent doit être actif pour créer un enfant

### Hiérarchisation
- **Références circulaires** : Validation automatique et blocage
- **Niveau de profondeur** : Propriété `depth_level` calculée
- **Chemin complet** : Navigation parent > enfant formatée

### Soft Delete
- **Préservation des données** : Catégories marquées inactives
- **Cascade automatique** : Enfants désactivés avec le parent
- **Désassignation** : Retrait automatique de tous les clients

---

## Documentation Swagger

La documentation interactive complète est disponible sur :
**http://localhost:8000/api/documentation**

Cette interface permet de :
- Tester tous les endpoints catégories directement
- Voir les schémas de données détaillés
- Gérer l'authentification Bearer
- Visualiser les réponses d'exemple
- Explorer la hiérarchisation des catégories

---

## Notes importantes

1. **Système client uniquement** : Les catégories s'appliquent aux clients, pas aux fournisseurs
2. **Hiérarchisation flexible** : Niveaux illimités avec protection contre les cycles
3. **Types prédéfinis** : 5 types couvrant la plupart des cas d'usage
4. **Assignation multiple** : Un client peut avoir plusieurs catégories de types différents
5. **Codes couleur** : Affichage visuel uniforme avec validation format
6. **Soft delete** : Aucune perte de données, désactivation uniquement
7. **Audit complet** : Traçabilité totale des créations et assignations
8. **Performance optimisée** : Index et eager loading pour grandes bases
9. **Validation stricte** : Prévention des erreurs de structure
10. **API REST complète** : CRUD complet + assignations avec pagination

---

**Version API** : 1.0.0
**Framework** : Laravel 8.x
**Authentification** : Laravel Sanctum
**Documentation** : OpenAPI 3.0