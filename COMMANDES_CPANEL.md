# 🚀 COMMANDES EXACTES POUR CPANEL

## 📦 Fichiers à télécharger
- `targetdesk-production-update-final.tar.gz` (18.8 MB)
- `DEPLOYMENT_GUIDE.md` (guide complet)
- `COMMANDES_CPANEL.md` (ce guide)

## 1. 💾 SAUVEGARDE (OBLIGATOIRE)
```bash
# Dans Terminal cPanel ou File Manager
cd public_html
cp -r api api_backup_$(date +%Y%m%d_%H%M)
```

## 2. 📤 UPLOAD ET EXTRACTION
```bash
# 1. Upload targetdesk-production-update-final.tar.gz dans /public_html/api/
# 2. Dans Terminal cPanel :
cd ~/public_html/api
tar -xzf targetdesk-production-update-final.tar.gz
rm targetdesk-production-update-final.tar.gz
```

## 3. 🔐 PERMISSIONS
```bash
chmod -R 755 storage bootstrap/cache
chmod 644 .env
```

## 4. 📧 CONFIGURATION EMAIL (.env)

### OPTION A: Gmail (Recommandé)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre-gmail@gmail.com
MAIL_PASSWORD=votre-mot-de-passe-app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=votre-gmail@gmail.com
MAIL_FROM_NAME="TargetDesk"
```

### OPTION B: Email du domaine
```env
MAIL_MAILER=smtp
MAIL_HOST=mail.votre-domaine.com
MAIL_PORT=587
MAIL_USERNAME=notifications@votre-domaine.com
MAIL_PASSWORD=mot-de-passe-email
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=notifications@votre-domaine.com
MAIL_FROM_NAME="TargetDesk"
```

### OPTION C: Pour tests (emails dans logs)
```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS=notifications@targetdesk.com
MAIL_FROM_NAME="TargetDesk"
```

## 5. 🗄️ MISE À JOUR BASE DE DONNÉES
```bash
# Dans Terminal cPanel :
cd ~/public_html/api

# Nettoyer les caches
php artisan config:clear
php artisan cache:clear

# IMPORTANT: Exécuter les nouvelles migrations
php artisan migrate --force

# IMPORTANT: Initialiser les préférences pour utilisateurs existants
php artisan db:seed --class=UserNotificationPreferencesSeeder

# Optimiser pour production
php artisan config:cache
php artisan l5-swagger:generate
```

## 6. ⏰ CRON JOB (cPanel Cron Jobs)
```bash
# Ajouter cette ligne dans cPanel > Cron Jobs :
* * * * * cd /home/VOTRE_USERNAME/public_html/api && php artisan schedule:run >> /dev/null 2>&1

# Remplacer VOTRE_USERNAME par votre vrai nom d'utilisateur cPanel
```

## 7. 🧪 TESTS

### Test API
```bash
curl -X GET "https://votre-domaine.com/api/v1/health"
```

### Test Email (Terminal cPanel)
```bash
cd ~/public_html/api
php artisan tinker --execute="Mail::raw('Test TargetDesk', function(\$m) { \$m->to('votre-email@test.com')->subject('Test Production'); }); echo 'Email sent!';"
```

### Test Notifications
Créer un RDV avec participants via l'interface et vérifier les logs :
```bash
tail -n 20 storage/logs/laravel.log | grep "email reminder"
```

## 8. 📍 URLs IMPORTANTES

- **API Documentation**: `https://votre-domaine.com/api/documentation`
- **Nouvelles préférences**: `GET/PUT /api/v1/users/{id}/email-preferences`
- **Rappels en attente**: `GET /api/v1/email-reminders/pending`
- **Statistiques**: `GET /api/v1/email-reminders/statistics`

## 🚨 EN CAS DE PROBLÈME

### Si migration échoue :
```bash
php artisan migrate:status
php artisan migrate:rollback --step=1
php artisan migrate --force
```

### Si emails ne partent pas :
```bash
# Vérifier config
php artisan config:clear
grep MAIL_ .env

# Tester manuellement
php artisan tinker
Mail::raw('Test', function($m) { $m->to('test@email.com')->subject('Test'); });
```

### Si erreur de permissions :
```bash
chmod -R 755 storage bootstrap/cache
chown -R VOTRE_USERNAME:VOTRE_USERNAME storage
```

## 📋 CHECKLIST FINAL

- [ ] Archive uploadée et extraite
- [ ] Permissions OK (755 storage, 644 .env)
- [ ] .env modifié avec config email
- [ ] `php artisan migrate --force` exécuté
- [ ] Cache nettoyé et optimisé
- [ ] Cron job ajouté (toutes les minutes)
- [ ] Test API fonctionne
- [ ] Test email envoyé
- [ ] Documentation accessible
- [ ] Logs montrent les rappels créés

## 🎯 NOUVELLES FONCTIONNALITÉS

✅ **Notifications participants** : Les participants aux RDV reçoivent maintenant des rappels email automatiques

✅ **Préférences personnalisées** : Chaque utilisateur peut configurer ses timings de rappel

✅ **Templates différenciés** : Emails différents pour organisateur vs participant

✅ **API complète** : Endpoints pour gérer préférences et statistiques

---

**🚀 Votre système TargetDesk est maintenant prêt avec les notifications email pour participants !**