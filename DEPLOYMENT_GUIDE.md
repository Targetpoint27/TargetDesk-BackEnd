# Guide de Déploiement Production - TargetDesk Backend

## 📦 Archive créée : `targetdesk-production-update.tar.gz`

## 🚀 Étapes de Déploiement cPanel

### 1. Sauvegarde avant mise à jour
```bash
# Dans cPanel File Manager, créer une sauvegarde
cp -r public_html/api public_html/api_backup_$(date +%Y%m%d)

# Sauvegarder la base de données via phpMyAdmin
# Export -> Structure et données -> SQL
```

### 2. Upload et extraction
```bash
# 1. Upload du fichier targetdesk-production-update.tar.gz dans le dossier api/
# 2. Via Terminal cPanel ou File Manager :

cd public_html/api
tar -xzf targetdesk-production-update.tar.gz
rm targetdesk-production-update.tar.gz
```

### 3. Configuration des permissions
```bash
# Permissions des dossiers
chmod -R 755 bootstrap/cache
chmod -R 755 storage
chmod -R 755 storage/logs
chmod -R 755 storage/framework
chmod -R 755 storage/framework/cache
chmod -R 755 storage/framework/sessions
chmod -R 755 storage/framework/views

# Permissions des fichiers
chmod 644 .env
chmod 644 composer.json
```

### 4. Configuration .env (Mise à jour)

```bash
# Éditer le fichier .env existant et ajouter/modifier ces lignes :

# ===== NOUVELLES CONFIGURATIONS EMAIL =====
# Option 1: Gmail (Recommandé)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre-email@gmail.com
MAIL_PASSWORD=votre-mot-de-passe-app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=votre-email@gmail.com
MAIL_FROM_NAME="TargetDesk"

# Option 2: cPanel Mail (Si vous avez un email sur votre domaine)
MAIL_MAILER=smtp
MAIL_HOST=mail.votre-domaine.com
MAIL_PORT=587
MAIL_USERNAME=notifications@votre-domaine.com
MAIL_PASSWORD=mot-de-passe-email
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=notifications@votre-domaine.com
MAIL_FROM_NAME="TargetDesk"

# Option 3: Pour les tests (emails dans les logs)
MAIL_MAILER=log
MAIL_FROM_ADDRESS=notifications@targetdesk.com
MAIL_FROM_NAME="TargetDesk"

# ===== CONFIGURATION QUEUE (IMPORTANT) =====
QUEUE_CONNECTION=database
# OU
QUEUE_CONNECTION=sync

# ===== URL DE PRODUCTION =====
APP_URL=https://votre-domaine.com/api
L5_SWAGGER_CONST_HOST=votre-domaine.com/api
```

### 5. Commandes de mise à jour

```bash
# 1. Installer les dépendances (si composer disponible)
composer install --no-dev --optimize-autoloader

# OU si pas de composer en ligne de commande, uploader vendor/ inclus

# 2. Nettoyer les caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 3. Exécuter les nouvelles migrations (IMPORTANT)
php artisan migrate --force

# 4. Initialiser les préférences pour utilisateurs existants (IMPORTANT)
php artisan db:seed --class=UserNotificationPreferencesSeeder

# 5. Optimiser pour la production
php artisan config:cache
php artisan route:cache

# 5. Générer la documentation API mise à jour
php artisan l5-swagger:generate
```

## 📧 Configuration Email Détaillée

### Gmail (Recommandé)
1. **Créer un mot de passe d'application** :
   - Aller sur https://myaccount.google.com/security
   - Activer la validation en 2 étapes
   - Générer un mot de passe d'application
   - Utiliser ce mot de passe dans MAIL_PASSWORD

2. **Configuration .env** :
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre-gmail@gmail.com
MAIL_PASSWORD=mot-de-passe-app-16-caracteres
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=votre-gmail@gmail.com
MAIL_FROM_NAME="TargetDesk Notifications"
```

### Email cPanel/Domaine personnalisé
```env
MAIL_MAILER=smtp
MAIL_HOST=mail.votre-domaine.com
MAIL_PORT=587
MAIL_USERNAME=notifications@votre-domaine.com
MAIL_PASSWORD=votre-mot-de-passe
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=notifications@votre-domaine.com
MAIL_FROM_NAME="TargetDesk"
```

## ⏰ Configuration du Cron Job (Scheduler)

### Ajouter dans cPanel Cron Jobs :
```bash
# Exécuter toutes les minutes pour le scheduler Laravel
* * * * * cd /home/votre-username/public_html/api && php artisan schedule:run >> /dev/null 2>&1

# OU avec le chemin PHP spécifique de votre hébergeur
* * * * * cd /home/votre-username/public_html/api && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

## 🔧 Vérifications Post-Déploiement

### 1. Test de l'API
```bash
# Tester la connexion
curl -X POST https://votre-domaine.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@targetdesk.com", "password": "votre-password"}'
```

### 2. Test des emails
```bash
# Via tinker (si accès SSH/Terminal)
php artisan tinker
Mail::raw('Test email TargetDesk', function($message) {
    $message->to('votre-email@test.com')->subject('Test Production');
});
```

### 3. Test des notifications
```bash
# Créer un rendez-vous de test et vérifier les rappels
curl -X POST "https://votre-domaine.com/api/v1/clients/1/appointments" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title": "Test Production", "scheduled_at": "2026-02-05T10:00:00", "duration": 60, "type": "demo"}'
```

### 4. Vérifier les logs
```bash
# Vérifier que les rappels sont créés
tail -n 50 storage/logs/laravel.log | grep "email reminder"
```

## 📊 Nouvelles Fonctionnalités Ajoutées

### API Endpoints Notifications
- `GET /api/v1/users/{user}/email-preferences` - Récupérer les préférences
- `PUT /api/v1/users/{user}/email-preferences` - Modifier les préférences
- `GET /api/v1/email-reminders/pending` - Rappels en attente
- `GET /api/v1/email-reminders/sent` - Historique des rappels
- `GET /api/v1/email-reminders/statistics` - Statistiques

### Nouvelles Tables
- `user_notification_preferences` - Préférences utilisateur
- `scheduled_email_reminders` - Rappels programmés
- Colonnes ajoutées : `participant_id`, `recipient_type`

### Fonctionnement Automatique
1. **Création RDV** → Rappels automatiques pour organiser ET participants
2. **Scheduler** → Traitement des rappels toutes les 5 minutes
3. **Emails** → Contenu différencié organizer vs participants

## 🚨 Points d'Attention

### Base de Données
```sql
-- Si les migrations échouent, exécuter manuellement :

-- 1. Table des préférences utilisateur
CREATE TABLE user_notification_preferences (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    user_id bigint unsigned NOT NULL,
    type varchar(255) NOT NULL DEFAULT 'appointment_reminder',
    timing json NOT NULL,
    email_enabled tinyint(1) NOT NULL DEFAULT 1,
    is_active tinyint(1) NOT NULL DEFAULT 1,
    created_at timestamp NULL DEFAULT NULL,
    updated_at timestamp NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY user_notification_preferences_user_id_type_unique (user_id,type),
    KEY user_notification_preferences_user_id_foreign (user_id),
    CONSTRAINT user_notification_preferences_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- 2. Ajouter les colonnes manquantes aux rappels
ALTER TABLE scheduled_email_reminders
ADD COLUMN participant_id bigint unsigned NULL AFTER user_id,
ADD COLUMN recipient_type enum('organizer','participant') DEFAULT 'organizer' AFTER participant_id,
MODIFY COLUMN user_id bigint unsigned NULL;
```

### Résolution de Problèmes
```bash
# Si erreur de permissions
chmod -R 755 storage bootstrap/cache

# Si erreur de cache
php artisan config:clear
php artisan cache:clear

# Si erreur de composer
rm -rf vendor/
# Re-upload vendor/ depuis l'archive locale

# Si erreur de migration
php artisan migrate:status
php artisan migrate --force
```

## 📝 Fichiers de Configuration

### .htaccess (si nécessaire)
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

### Storage Symlink (si images)
```bash
php artisan storage:link
```

## 🔍 Monitoring

### Logs importants à surveiller
```bash
# Logs généraux
tail -f storage/logs/laravel.log

# Logs emails
grep "Email reminder" storage/logs/laravel.log

# Logs erreurs
grep "ERROR" storage/logs/laravel.log
```

### URLs de test
- API Documentation : `https://votre-domaine.com/api/documentation`
- Health Check : `https://votre-domaine.com/api/v1/health`
- Login Test : `https://votre-domaine.com/api/v1/auth/login`

---

## ✅ Checklist Final

- [ ] Archive uploadée et extraite
- [ ] Permissions configurées
- [ ] .env mis à jour avec config email
- [ ] Migrations exécutées
- [ ] Cache configuré
- [ ] Cron job ajouté
- [ ] Test de connexion API ✓
- [ ] Test d'envoi email ✓
- [ ] Test création RDV avec participants ✓
- [ ] Documentation accessible ✓

**Version :** Production Update v2.0
**Date :** Février 2026
**Nouvelles fonctionnalités :** Notifications email pour participants aux rendez-vous