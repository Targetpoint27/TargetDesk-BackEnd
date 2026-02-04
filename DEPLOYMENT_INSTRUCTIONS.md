# Instructions de Déploiement - TargetDesk Backend

## 📦 Fichier à télécharger sur cPanel
- `targetdesk-backend-with-vendor.tar.gz` (13M - inclut le dossier vendor)

## 🚀 Étapes de déploiement sur cPanel

### 1. Upload et extraction
```bash
# Dans le gestionnaire de fichiers cPanel, aller dans public_html
# Uploader targetdesk-backend-with-vendor.tar.gz
# Extraire l'archive :
tar -xzf targetdesk-backend-with-vendor.tar.gz
rm targetdesk-backend-with-vendor.tar.gz
```

### 2. Configuration de l'environnement
```bash
# Copier et configurer le fichier .env
cp .env.production.example .env

# Éditer .env avec vos paramètres :
# - APP_URL=https://votredomaine.com
# - DB_* (paramètres de base de données)
# - Autres configurations spécifiques
```

### 3. Vérification des dépendances
```bash
# Le dossier vendor est déjà inclus dans l'archive
# Aucune installation de composer nécessaire !
ls -la vendor/  # Vérifier que le dossier vendor existe
```

### 4. Configuration Laravel
```bash
# Générer la clé d'application
php artisan key:generate

# Créer le lien symbolique pour le storage
php artisan storage:link

# Optimiser l'application
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Effacer les anciens caches
php artisan config:clear
php artisan cache:clear
```

### 5. Base de données
```bash
# Exécuter les migrations
php artisan migrate --force

# Remplir avec les données de base (rôles et permissions)
php artisan db:seed --class=RolesAndPermissionsSeeder --force
```

### 6. Permissions des fichiers
```bash
# Définir les permissions appropriées
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 777 storage
chmod -R 777 bootstrap/cache
```

### 7. Configuration du serveur web
Créer un fichier `.htaccess` dans `public_html` :
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

### 8. Test de l'API
```bash
# Tester l'endpoint de santé
curl https://votredomaine.com/api/health

# Tester l'authentification
curl -X POST https://votredomaine.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "rochneltegomo@gmail.com", "password": "12345678"}'
```

## 🔧 Configuration spécifique cPanel

### Structure des dossiers
```
public_html/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/ (dossier public de Laravel)
├── resources/
├── routes/
├── storage/
├── vendor/
├── .env
├── composer.json
└── artisan
```

### Variables d'environnement importantes
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votredomaine.com
DB_HOST=localhost
DB_DATABASE=votre_base
DB_USERNAME=votre_user
DB_PASSWORD=votre_password
SANCTUM_STATEFUL_DOMAINS=votredomaine.com
SESSION_DOMAIN=votredomaine.com
```

## 🛠 Commandes de maintenance

### Mise à jour du code
```bash
# Effacer les caches avant mise à jour
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Après mise à jour du code
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Génération de la documentation API
```bash
php artisan l5-swagger:generate
```

### Logs et débogage
```bash
# Voir les logs
tail -f storage/logs/laravel.log

# Effacer les logs
echo "" > storage/logs/laravel.log
```

## 📋 Checklist de vérification
- [ ] .env configuré avec les bonnes valeurs
- [ ] Base de données créée et accessible
- [ ] Migrations exécutées
- [ ] Seeders exécutés
- [ ] Permissions des fichiers correctes
- [ ] .htaccess configuré
- [ ] Storage link créé
- [ ] Caches optimisés
- [ ] API accessible via HTTPS
- [ ] Documentation Swagger accessible

## 🚨 Dépannage courant

### Erreur 500
```bash
# Activer temporairement le debug
# Dans .env : APP_DEBUG=true
# Vérifier les logs : storage/logs/laravel.log
```

### Problème de permissions
```bash
chmod -R 777 storage bootstrap/cache
```

### Erreur de base de données
```bash
# Vérifier les paramètres DB dans .env
# Tester la connexion :
php artisan tinker
# > DB::connection()->getPdo();
```

### Cache corrompus
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```