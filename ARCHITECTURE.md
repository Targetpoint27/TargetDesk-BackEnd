# Architecture TargetDesk Backend

## Vue d'ensemble

Ce projet utilise **Laravel 8** comme framework backend avec une architecture API REST documentée via **Swagger/OpenAPI**. L'architecture suit les bonnes pratiques de séparation des responsabilités et de versioning d'API.

## Structure des dossiers

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── BaseApiController.php      # Contrôleur de base avec méthodes communes
│   │       └── V1/
│   │           └── ApiController.php      # Contrôleurs API version 1
│   ├── Middleware/
│   │   └── ApiMiddleware.php              # Middleware pour les réponses API
│   ├── Requests/
│   │   └── Api/                           # Validation des requêtes API
│   └── Resources/
│       └── Api/                           # Transformation des réponses API
├── Services/                              # Logique métier
├── Repositories/                          # Accès aux données
├── Models/
│   └── Api/                              # Modèles spécifiques à l'API
└── Exceptions/
    └── Api/
        └── ApiException.php              # Exceptions personnalisées API
```

## Composants principaux

### 1. BaseApiController
- **Localisation**: `app/Http/Controllers/Api/BaseApiController.php`
- **Rôle**: Contrôleur parent pour tous les contrôleurs API
- **Fonctionnalités**:
  - Méthodes de réponse standardisées (`successResponse`, `errorResponse`)
  - Annotations Swagger/OpenAPI de base
  - Configuration de sécurité Sanctum

### 2. ApiMiddleware
- **Localisation**: `app/Http/Middleware/ApiMiddleware.php`
- **Rôle**: Middleware appliqué à toutes les routes API
- **Fonctionnalités**:
  - Headers standardisés (`Content-Type: application/json`)
  - Version de l'API (`X-API-Version: 1.0.0`)

### 3. Gestion des erreurs
- **Localisation**: `app/Exceptions/Handler.php`
- **Fonctionnalités**:
  - Détection automatique des requêtes API
  - Réponses JSON standardisées pour toutes les erreurs
  - Gestion spécifique des erreurs de validation (422)
  - Gestion des routes non trouvées (404)

## Versioning API

L'API utilise un versioning par préfixe d'URL :

- **V1**: `/api/v1/*`
- Futures versions: `/api/v2/*`, `/api/v3/*`, etc.

### Routes disponibles

```
GET /api/v1/health          # Health check (public)
GET /api/v1/user            # Profil utilisateur (protégé)
```

## Authentification

- **Système**: Laravel Sanctum
- **Type**: Bearer Token
- **Header**: `Authorization: Bearer {token}`

### Routes protégées
Les routes protégées nécessitent le middleware `auth:sanctum` et un token valide.

## Documentation API

### Swagger/OpenAPI
- **Package**: `darkaonline/l5-swagger`
- **URL**: `http://localhost:8000/api/documentation`
- **Configuration**: `config/l5-swagger.php`

### Générer la documentation
```bash
php artisan l5-swagger:generate
```

### Annotations Swagger
Chaque endpoint doit être documenté avec les annotations OpenAPI :

```php
/**
 * @OA\Get(
 *     path="/v1/endpoint",
 *     tags={"Category"},
 *     summary="Description courte",
 *     description="Description détaillée",
 *     @OA\Response(response=200, description="Succès"),
 *     @OA\Response(response=400, description="Erreur")
 * )
 */
```

## Réponses API standardisées

### Format de succès
```json
{
    "success": true,
    "message": "Message de succès",
    "data": {
        // Données de réponse
    }
}
```

### Format d'erreur
```json
{
    "success": false,
    "message": "Message d'erreur",
    "errors": {
        // Détails des erreurs (optionnel)
    }
}
```

## Base de données

- **SGBD**: MySQL
- **Base**: `targetdesk`
- **Configuration**: `.env`
- **Migrations**: `database/migrations/`

### Commandes utiles
```bash
php artisan migrate              # Exécuter les migrations
php artisan migrate:status       # Statut des migrations
php artisan make:migration name  # Créer une migration
```

## Environnement de développement

### Serveur de développement
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

### Configuration
- **Fichier**: `.env`
- **Variables clés**:
  - `APP_NAME=TargetDesk`
  - `DB_DATABASE=targetdesk`
  - `APP_URL=http://localhost:8000`

## Bonnes pratiques

### 1. Structure des contrôleurs
- Hériter de `BaseApiController`
- Une méthode par action
- Annotations Swagger complètes
- Gestion des erreurs via exceptions

### 2. Validation des requêtes
- Utiliser des Form Requests dans `app/Http/Requests/Api/`
- Validation centralisée
- Messages d'erreur personnalisés

### 3. Transformation des données
- Utiliser des Resources dans `app/Http/Resources/Api/`
- Format de sortie cohérent
- Masquage des données sensibles

### 4. Logique métier
- Placer la logique dans `app/Services/`
- Contrôleurs minimalistes
- Code réutilisable

### 5. Accès aux données
- Repository pattern dans `app/Repositories/`
- Abstraction de la couche de données
- Facilite les tests unitaires

## Extensions futures

L'architecture actuelle permet facilement :

- Ajout de nouvelles versions d'API (`V2`, `V3`)
- Intégration de nouveaux middlewares
- Systèmes d'authentification additionnels
- Mise en cache des réponses
- Rate limiting
- Logs détaillés
- Tests automatisés

## Commandes de développement

```bash
# Installation des dépendances
composer install

# Génération de clé d'application
php artisan key:generate

# Migrations
php artisan migrate

# Documentation Swagger
php artisan l5-swagger:generate

# Serveur de développement
php artisan serve
```

---

**Version**: 1.0.0
**Framework**: Laravel 8.x
**PHP**: 8.0.28
**Documentation**: Swagger/OpenAPI 3.0