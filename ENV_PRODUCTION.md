# Configuration .env Production - TargetDesk

## 📄 Fichier .env complet pour la production

```env
APP_NAME=TargetDesk
APP_ENV=production
APP_KEY=base64:2fvoDL+e2Gf6v8bJX2vn3gaioaL+j43Syyg3c6NCnd0=
APP_DEBUG=false
APP_URL=https://motivational-nickel-rabbit.148-230-126-127.cpanel.site/test/api

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=targetdesk_db
DB_USERNAME=targetdesk_db
DB_PASSWORD="%SNy!fTC6J3EqT#m"

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DRIVER=local
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# ===== CONFIGURATION EMAIL POUR NOTIFICATIONS =====
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=VOTRE_EMAIL@VOTRE_DOMAINE.com
MAIL_PASSWORD=VOTRE_MOT_DE_PASSE_EMAIL
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=VOTRE_EMAIL@VOTRE_DOMAINE.com
MAIL_FROM_NAME="TargetDesk CRM"

# Configuration Swagger pour serveur distant
L5_SWAGGER_CONST_HOST=motivational-nickel-rabbit.148-230-126-127.cpanel.site/test/api/public
L5_SWAGGER_BASE_PATH=/test/api/public/api

# Configuration CORS et Sanctum
SANCTUM_STATEFUL_DOMAINS=motivational-nickel-rabbit.148-230-126-127.cpanel.site

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1

MIX_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
MIX_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

## 🔧 Modifications apportées

### ✅ Changements importants :
1. **`QUEUE_CONNECTION=database`** - Pour traiter les emails en arrière-plan
2. **Section EMAIL** - Configuration complète pour notifications
3. **`MAIL_FROM_NAME="TargetDesk CRM"`** - Nom professionnel

## 📧 Variables Email à compléter

### Option A - Email du domaine (Recommandé)
```env
MAIL_USERNAME=notifications@votre-domaine.com
MAIL_PASSWORD=votre-mot-de-passe-email
MAIL_FROM_ADDRESS=notifications@votre-domaine.com
```

### Option B - Gmail (Alternative)
```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre-gmail@gmail.com
MAIL_PASSWORD=mot-de-passe-app-16-caracteres
MAIL_FROM_ADDRESS=votre-gmail@gmail.com
MAIL_ENCRYPTION=tls
```

### Option C - Tests (emails dans logs)
```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS=notifications@targetdesk.com
```

## 🚀 Après modification du .env

```bash
# Nettoyer et recacher la configuration
php artisan config:clear
php artisan config:cache

# Tester la configuration email
php artisan tinker --execute="Mail::raw('Test TargetDesk', function(\$m) { \$m->to('test@email.com')->subject('Test Production'); }); echo 'Email sent!';"
```

## 📋 Checklist Configuration

- [ ] Variables email complétées
- [ ] `QUEUE_CONNECTION=database`
- [ ] `php artisan config:cache` exécuté
- [ ] Test email envoyé avec succès
- [ ] Logs vérifiés (aucune erreur)

## 🔍 Vérification

```bash
# Vérifier que les notifications fonctionnent
tail -f storage/logs/laravel.log | grep "email reminder"

# Vérifier les queues (si utilisées)
php artisan queue:work --once
```

---

**Configuration prête pour les notifications email automatiques !** 🚀