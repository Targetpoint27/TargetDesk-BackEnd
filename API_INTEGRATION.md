# Guide d'intégration API TargetDesk

## Base URL
```
http://localhost:8000/api/v1
```

## Authentification

L'API utilise **Laravel Sanctum** avec des tokens Bearer pour l'authentification.

### Format du header d'authentification
```
Authorization: Bearer {token}
```

---

## Endpoints d'authentification

### 1. Inscription d'un utilisateur

**POST** `/auth/register`

```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

**Réponse (201 Created):**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "token": "1|abc123def456..."
  }
}
```

**Erreurs possibles:**
- `422` - Erreur de validation (email déjà utilisé, mot de passe trop court, etc.)

---

### 2. Connexion

**POST** `/auth/login`

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

**Réponse (200 OK):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "token": "2|xyz789abc123..."
  }
}
```

**Erreurs possibles:**
- `401` - Identifiants incorrects
- `422` - Erreur de validation

---

### 3. Informations utilisateur

**GET** `/auth/user` 🔒 *Protégé*

```bash
curl -X GET http://localhost:8000/api/v1/auth/user \
  -H "Authorization: Bearer {your_token}"
```

**Réponse (200 OK):**
```json
{
  "success": true,
  "message": "User info retrieved",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": null,
    "created_at": "2026-01-08T15:52:47.000000Z",
    "updated_at": "2026-01-08T15:52:47.000000Z"
  }
}
```

**Erreurs possibles:**
- `401` - Token manquant ou invalide

---

### 4. Déconnexion

**POST** `/auth/logout` 🔒 *Protégé*

```bash
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer {your_token}"
```

**Réponse (200 OK):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

## Endpoint système

### Health Check

**GET** `/health`

```bash
curl -X GET http://localhost:8000/api/v1/health
```

**Réponse (200 OK):**
```json
{
  "success": true,
  "message": "API is running",
  "data": {
    "version": "1.0.0",
    "timestamp": "2026-01-08T15:52:25.297848Z"
  }
}
```

---

## Format des réponses

### Réponse de succès
```json
{
  "success": true,
  "message": "Message de succès",
  "data": {
    // Données de la réponse
  }
}
```

### Réponse d'erreur
```json
{
  "success": false,
  "message": "Message d'erreur",
  "errors": {
    // Détails des erreurs (optionnel)
  }
}
```

---

## Codes de statut HTTP

- `200` - Succès
- `201` - Créé avec succès
- `400` - Erreur de requête
- `401` - Non authentifié
- `422` - Erreur de validation
- `404` - Ressource non trouvée
- `500` - Erreur serveur

---

## Gestion des erreurs de validation

**Exemple d'erreur 422:**
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "email": ["L'email est déjà utilisé"],
    "password": ["Le mot de passe doit contenir au moins 8 caractères"]
  }
}
```

---

## Exemples d'intégration

### JavaScript/Fetch
```javascript
// Inscription
const registerUser = async (userData) => {
  try {
    const response = await fetch('http://localhost:8000/api/v1/auth/register', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(userData)
    });

    const data = await response.json();

    if (data.success) {
      // Stocker le token
      localStorage.setItem('token', data.data.token);
      return data.data.user;
    } else {
      throw new Error(data.message);
    }
  } catch (error) {
    console.error('Erreur inscription:', error);
    throw error;
  }
};

// Appel API authentifié
const getUserInfo = async () => {
  const token = localStorage.getItem('token');

  try {
    const response = await fetch('http://localhost:8000/api/v1/auth/user', {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      }
    });

    const data = await response.json();
    return data.data;
  } catch (error) {
    console.error('Erreur récupération utilisateur:', error);
    throw error;
  }
};
```

### PHP/cURL
```php
// Connexion
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/v1/auth/login');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'john@example.com',
    'password' => 'password123'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$data = json_decode($response, true);

if ($data['success']) {
    $token = $data['data']['token'];
    // Utiliser le token pour les requêtes suivantes
}
```

---

## Documentation Swagger

La documentation interactive complète est disponible sur :
**http://localhost:8000/api/documentation**

Cette interface permet de :
- Tester tous les endpoints directement
- Voir les schémas de données détaillés
- Gérer l'authentification Bearer

---

## Notes importantes

1. **Stockage du token** : Conservez le token de façon sécurisée (localStorage, sessionStorage, ou cookie httpOnly)
2. **Expiration** : Les tokens n'expirent pas automatiquement, utilisez logout pour les révoquer
3. **CORS** : Le middleware CORS est configuré pour accepter toutes les origines en développement
4. **Rate limiting** : Un rate limiting par défaut est appliqué (60 requêtes/minute)
5. **Validation** : Tous les champs requis sont validés côté serveur avec messages en français