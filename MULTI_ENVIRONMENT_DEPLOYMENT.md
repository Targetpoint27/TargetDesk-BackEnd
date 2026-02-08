# Guide de Déploiement Multi-Environnements TargetDesk CRM

## 🚀 Environnements de Déploiement

### **Test (Staging)** - test.targetdesk.fr
- **URL API :** `https://test.targetdesk.fr/api`
- **URL Frontend :** `https://test.targetdesk.fr`
- **Base de données :** `targetdesk_test`
- **Configuration :** `.env.test`

### **Production** - targetdesk.fr
- **URL API :** `https://targetdesk.fr/api`
- **URL Frontend :** `https://targetdesk.fr`
- **Base de données :** `targetdesk_dbprod`
- **Configuration :** `.env.production`

---

## 📁 Structure des Archives

### **Archive Test**
```bash
tar --exclude='node_modules' --exclude='.git' --exclude='storage/logs/*' --exclude='bootstrap/cache/*' --exclude='.env' --exclude='*.tar.gz' -czf targetdesk-test.tar.gz .
```

### **Archive Production Ultra-Clean**
```bash
tar --exclude='*.md' --exclude='*.zip' --exclude='*.tar.gz' --exclude='node_modules' --exclude='.git' --exclude='storage/logs/*' --exclude='bootstrap/cache/*' --exclude='.env' --exclude='public/uploads/*' --exclude='public/images/*' --exclude='*.csv' --exclude='*.xlsx' --exclude='*.xls' --exclude='*.pdf' --exclude='.DS_Store' --exclude='Thumbs.db' --exclude='*.tmp' --exclude='*.temp' --exclude='storage/framework/cache/*' --exclude='storage/framework/sessions/*' --exclude='storage/framework/views/*' -czf targetdesk-production-ultra-clean.tar.gz .
```

---

## 🔧 Déploiement Test (test.targetdesk.fr)

### 1. **Upload et Extraction**
```bash
# Dans le répertoire test.targetdesk.fr/api/
tar -xzf targetdesk-test.tar.gz
rm targetdesk-test.tar.gz
```

### 2. **Création des dossiers et Configuration**
```bash
# Créer les dossiers nécessaires
mkdir -p storage/logs
mkdir -p storage/app/public
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache
mkdir -p public/uploads

# Configuration des permissions
cp .env.test .env
chmod 644 .env
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chown -R www-data:www-data storage/
chown -R www-data:www-data bootstrap/cache/
```

### 3. **Laravel Setup**
```bash
php artisan key:generate
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4. **Base de données**
```bash
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=SupplierPermissionsSeeder
php artisan db:seed --class=UserNotificationPreferencesSeeder
```

### 5. **Cron Job Test**
```
* * * * * cd /home/targetdesk/public_html/test.targetdesk.fr/api && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🚀 Déploiement Production (targetdesk.fr)

### 1. **Upload et Extraction**
```bash
# Dans le répertoire targetdesk.fr/api/
tar -xzf targetdesk-production-ultra-clean.tar.gz
rm targetdesk-production-ultra-clean.tar.gz
```

### 2. **Création des dossiers et Configuration**
```bash
# Créer les dossiers nécessaires
mkdir -p storage/logs
mkdir -p storage/app/public
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache
mkdir -p public/uploads

# Configuration des permissions
cp .env.production .env
chmod 644 .env
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chown -R www-data:www-data storage/
chown -R www-data:www-data bootstrap/cache/
```

### 3. **Laravel Setup**
```bash
php artisan key:generate
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4. **Base de données Production**
```bash
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=SupplierPermissionsSeeder
php artisan db:seed --class=UserNotificationPreferencesSeeder
```

### 5. **Cron Job Production**
```
* * * * * cd /home/targetdesk/public_html/targetdesk.fr/api && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📧 Configuration Email DNS

### **Enregistrements DNS à ajouter sur targetdesk.fr :**

#### **SPF Record**
```
Type: TXT
Nom: @
Valeur: v=spf1 a mx include:_spf.hostinger.com ~all
```

#### **DKIM Record**
```
Type: TXT
Nom: default._domainkey
Valeur: [Clé générée par cPanel Authentication]
```

#### **DMARC Record**
```
Type: TXT
Nom: _dmarc
Valeur: v=DMARC1; p=quarantine; rua=mailto:admin@targetdesk.fr; pct=100
```

---

## 🔍 Vérifications Post-Déploiement

### **API Health Check**
```bash
curl https://test.targetdesk.fr/api/v1/health
curl https://targetdesk.fr/api/v1/health
```

### **Test Email**
```bash
# Test création utilisateur
php artisan tinker
$user = new App\Models\User();
$user->name = 'Test Deploy';
$user->email = 'test@targetdesk.fr';
$user->save();
```

### **Test Queue**
```bash
php artisan queue:work --timeout=60 --once
```

### **Test Scheduler**
```bash
php artisan schedule:run
```

---

## 📊 Monitoring et Logs

### **Emplacements des logs**
- **Application :** `storage/logs/laravel.log`
- **Web Server :** `/var/log/apache2/` ou `/var/log/nginx/`
- **Cron Jobs :** `/var/log/cron`

### **Surveillance Email**
```bash
tail -f storage/logs/laravel.log | grep "Notification"
```

---

## 🔄 Commandes de Maintenance

### **Nettoyage Cache**
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### **Mise à jour Base de Données**
```bash
php artisan migrate --force
```

### **Nettoyage Notifications Expirées**
```bash
php artisan appointments:cleanup-expired-reminders
```

---

## 🆘 Troubleshooting

### **Problème Email**
1. Vérifier configuration DNS SPF/DKIM
2. Tester avec : `php artisan tinker` puis `Mail::raw('test', function($m) { $m->to('test@example.com'); });`

### **Problème Queue**
```bash
php artisan queue:restart
php artisan queue:work --daemon
```

### **Problème Permissions**
```bash
sudo chown -R www-data:www-data storage/
sudo chmod -R 755 storage/
```

---

## 📋 Checklist de Déploiement

### **Avant déploiement :**
- [ ] Archive créée avec bonne exclusion
- [ ] Fichier .env.test/.env.production configuré
- [ ] DNS SPF/DKIM configuré
- [ ] Base de données créée

### **Après déploiement :**
- [ ] Permissions correctes
- [ ] Laravel configuré (key, cache)
- [ ] Migrations exécutées
- [ ] Seeders exécutés
- [ ] Cron job configuré
- [ ] Tests API fonctionnels
- [ ] Notifications email testées

### **Validation finale :**
- [ ] Health check API OK
- [ ] Frontend connecté à l'API
- [ ] Création utilisateur + email reçu
- [ ] Scheduler actif
- [ ] Logs sans erreurs