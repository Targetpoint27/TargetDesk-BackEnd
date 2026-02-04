# Configuration du Cron Job (Scheduler) - cPanel

## 🕒 Étape par étape pour configurer le Cron Job

### 1. Accéder aux Cron Jobs dans cPanel

1. **Connectez-vous à votre cPanel**
2. **Cherchez "Cron Jobs"** dans la section "Advanced" ou "Avancé"
3. **Cliquez sur "Cron Jobs"**

### 2. Ajouter un nouveau Cron Job

Dans l'interface Cron Jobs :

#### **Paramètres du Cron Job :**

**Minute :** `*`
**Heure :** `*`
**Jour :** `*`
**Mois :** `*`
**Jour de la semaine :** `*`

**OU utilisez les menus déroulants :**
- **Paramètre commun :** Sélectionnez "Once Per Minute (Every Minute)"

#### **Commande :**
```bash
cd /home/VOTRE_USERNAME/public_html/api && php artisan schedule:run >> /dev/null 2>&1
```

**⚠️ IMPORTANT :** Remplacez `VOTRE_USERNAME` par votre vrai nom d'utilisateur cPanel

### 3. Trouver votre nom d'utilisateur

Si vous ne connaissez pas votre nom d'utilisateur :

```bash
# Dans Terminal cPanel, tapez :
pwd
# Cela affichera : /home/VOTRE_USERNAME
# Votre username est la partie après /home/
```

### 4. Exemples selon les hébergeurs

#### **Hostinger :**
```bash
cd /home/u123456789/public_html/api && /opt/alt/php81/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

#### **OVH :**
```bash
cd /home/USERNAME/www/api && /usr/local/php8.1/bin/php artisan schedule:run >> /dev/null 2>&1
```

#### **cPanel standard :**
```bash
cd /home/USERNAME/public_html/api && php artisan schedule:run >> /dev/null 2>&1
```

### 5. Interface cPanel - Exemple visuel

```
┌─ Add New Cron Job ─────────────────────────────┐
│                                                │
│ Common Settings: [Once Per Minute ▼]          │
│                                                │
│ OR                                             │
│                                                │
│ Minute:    [*]                                 │
│ Hour:      [*]                                 │
│ Day:       [*]                                 │
│ Month:     [*]                                 │
│ Weekday:   [*]                                 │
│                                                │
│ Command:                                       │
│ [cd /home/USERNAME/public_html/api && php...] │
│                                                │
│ [Add New Cron Job] [Cancel]                   │
└────────────────────────────────────────────────┘
```

### 6. Vérification du Cron Job

#### **Dans cPanel :**
- Le cron job doit apparaître dans la liste des "Current Cron Jobs"
- Statut : "Active"

#### **Via Terminal (si disponible) :**
```bash
# Voir les crons actifs
crontab -l

# Vérifier les logs Laravel
tail -f storage/logs/laravel.log | grep "schedule"
```

### 7. Logs de vérification

#### **Tester manuellement :**
```bash
# Dans Terminal cPanel
cd ~/public_html/api
php artisan schedule:run
```

#### **Vérifier que ça fonctionne :**
```bash
# Voir les rappels créés/traités
tail -n 50 storage/logs/laravel.log | grep -E "reminder|schedule"
```

### 8. Que fait le Scheduler Laravel ?

Le cron job exécute automatiquement :

- **Toutes les 5 minutes :** Traitement des rappels email en attente
- **Toutes les heures :** Planification de nouveaux rappels
- **Quotidiennement :** Nettoyage des anciens rappels échoués
- **Hebdomadairement :** Nettoyage des rappels envoyés anciens

## 🚨 Résolution de problèmes

### **Erreur : "php: command not found"**
```bash
# Utilisez le chemin complet du PHP
cd /home/USERNAME/public_html/api && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

### **Erreur : "Permission denied"**
```bash
# Vérifiez les permissions
chmod +x /home/USERNAME/public_html/api/artisan
```

### **Le cron ne se lance pas :**
```bash
# Testez la commande manuellement d'abord
cd ~/public_html/api
php artisan schedule:run
```

### **Vérifier les chemins PHP disponibles :**
```bash
which php
# ou
whereis php
# ou
find /usr -name "php*" 2>/dev/null | grep bin
```

## ✅ Checklist Final

- [ ] Cron job créé dans cPanel
- [ ] Fréquence : Toutes les minutes (`* * * * *`)
- [ ] Commande avec bon username et chemin
- [ ] Cron job apparaît dans la liste "Current Cron Jobs"
- [ ] Test manuel : `php artisan schedule:run` fonctionne
- [ ] Logs montrent l'activité du scheduler
- [ ] Notifications email fonctionnent automatiquement

## 📧 Test complet

1. **Créez un rendez-vous** avec participant pour dans 2 heures
2. **Attendez 1-2 minutes** (le cron s'exécute)
3. **Vérifiez les logs** : `tail -n 20 storage/logs/laravel.log`
4. **Confirmez** que les rappels sont programmés

---

**Votre système de notifications automatiques est maintenant actif ! 🚀**