# ANALYSE COMPLÈTE - GESTION DES TÂCHES

## Vue d'ensemble

Ce document analyse les spécifications pour le module de gestion des tâches qui s'intègre au système de gestion de projets existant. Le système doit couvrir 3 grandes fonctionnalités :

1. **EPIC 1 : Gestion des tâches** (9 histoires utilisateur)
2. **EPIC 2 : Collaboration et communication** (2 histoires utilisateur)
3. **EPIC 3 : Time tracking** (4 histoires utilisateur)

## EPIC 1 : Gestion des tâches

### 1. Créer une tâche

**Critères d'acceptation détaillés :**
1. ✅ Champs obligatoires : Titre*, Projet*, Assigné à*
2. ✅ Champs optionnels : Description, Priorité (Basse/Normale/Haute/Critique), Statut initial ("À faire"), Date d'échéance, Durée estimée (heures), Type de tâche (Dev, Design, Test, Analyse, Autre), Étiquettes, Tâche parente (sous-tâches)
3. ✅ Génération automatique identifiant unique (TSK-YYYY-NNNN)
4. ✅ Affichage visuel priorité (icône et couleur)
5. ✅ Étiquettes : création à la volée ou sélection liste existante
6. ✅ Multi-assignation (plusieurs personnes)
7. ✅ Message de confirmation après création
8. ✅ Notifications aux membres assignés
9. ✅ Log d'audit enregistre la création

**Endpoints requis :**
- `POST /api/v1/projects/{projectId}/tasks` - Créer une tâche
- `GET /api/v1/tags` - Lister les étiquettes existantes
- `POST /api/v1/tags` - Créer une nouvelle étiquette
- `GET /api/v1/projects/{projectId}/users` - Lister les membres assignables

### 2. Consulter les détails d'une tâche

**Critères d'acceptation détaillés :**
1. ✅ Affichage fiche tâche : Projet, Titre, Description, Statut, Priorité, Assigné à, Date de création, Date d'échéance, Durée estimée, Durée réelle (temps saisi), Type, Étiquettes, Chiffrage (si renseigné), Tâche parente (si sous-tâche)
2. ✅ Organisation en sections : Informations, Temps, Chiffrage, Difficultés, Commentaires, Fichiers, Historique
3. ✅ Actions rapides : Modifier, Changer statut, Saisir temps, Ajouter commentaire, Joindre fichier, Déclarer difficulté
4. ✅ Historique des modifications visible
5. ✅ Commentaires affichés chronologiquement
6. ✅ Indicateur visuel si tâche en retard (date échéance dépassée)

**Endpoints requis :**
- `GET /api/v1/tasks/{taskId}` - Détails complets d'une tâche
- `GET /api/v1/tasks/{taskId}/history` - Historique des modifications
- `GET /api/v1/tasks/{taskId}/comments` - Commentaires de la tâche
- `GET /api/v1/tasks/{taskId}/files` - Fichiers attachés
- `POST /api/v1/tasks/{taskId}/files` - Joindre un fichier

### 3. Modifier une tâche

**Critères d'acceptation détaillés :**
1. ✅ Membres assignés peuvent modifier : Description, Date d'échéance, Étiquettes, Commentaires
2. ✅ Chef de projet peut modifier tous les champs
3. ✅ Mêmes validations que lors de la création
4. ✅ Changement d'assignation nécessite confirmation
5. ✅ Nouveaux assignés reçoivent notification
6. ✅ Anciens assignés reçoivent notification de désassignation
7. ✅ Historique des modifications conservé
8. ✅ Message de confirmation après modification
9. ✅ Log d'audit enregistre la modification

**Endpoints requis :**
- `PUT /api/v1/tasks/{taskId}` - Modifier une tâche
- `POST /api/v1/tasks/{taskId}/assign` - Modifier les assignations

### 4. Changer le statut d'une tâche

**Critères d'acceptation détaillés :**
1. ✅ Statuts disponibles : À faire, En cours, Bloqué, Test, Terminé
2. ✅ Changement accessible depuis fiche tâche et liste
3. ✅ Champ "Commentaire" optionnel pour justifier le changement
4. ✅ Statut "Bloqué" nécessite justification obligatoire + alerte possible
5. ✅ Statut "Terminé" nécessite : Confirmation + Temps saisi (sinon avertissement)
6. ✅ Passage à "Terminé" met à jour automatiquement % avancement projet
7. ✅ Historique des changements de statut conservé
8. ✅ Indicateur visuel (badge coloré) affiche statut actuel
9. ✅ Log d'audit enregistre chaque changement de statut

**Endpoints requis :**
- `PUT /api/v1/tasks/{taskId}/status` - Changer le statut
- `GET /api/v1/task-statuses` - Lister les statuts disponibles

### 5. Filtrer et rechercher des tâches

**Critères d'acceptation détaillés :**
1. ✅ Filtres disponibles : Projet, Statut, Priorité, Assigné à, Type de tâche, Étiquettes, Période (aujourd'hui, cette semaine, ce mois, personnalisé), En retard (oui/non)
2. ✅ Filtres cumulables (logique ET)
3. ✅ Champ recherche par titre ou description
4. ✅ Compteur indique nombre de tâches correspondant aux filtres
5. ✅ Filtres appliqués visibles sous forme de badges supprimables
6. ✅ Bouton "Réinitialiser les filtres" disponible
7. ✅ Filtres sauvegardés comme vue personnalisée
8. ✅ Vues personnalisées accessibles via menu déroulant

**Endpoints requis :**
- `GET /api/v1/tasks` - Lister avec filtres et recherche
- `POST /api/v1/users/{userId}/task-views` - Sauvegarder une vue
- `GET /api/v1/users/{userId}/task-views` - Lister mes vues sauvegardées
- `DELETE /api/v1/task-views/{viewId}` - Supprimer une vue

### 6. Trier les tâches

**Critères d'acceptation détaillés :**
1. ✅ Colonnes triables : Priorité, Statut, Date d'échéance, Projet, Assigné à, Durée estimée
2. ✅ Tri croissant ou décroissant
3. ✅ Indicateur visuel (flèche) montre colonne et sens de tri actifs
4. ✅ Tri par défaut : Priorité (décroissant) puis Date d'échéance (croissant)
5. ✅ Tri conservé lors de navigation entre pages
6. ✅ Tri "intelligent" disponible : Tâches urgentes en premier (Critique + échéance proche)

**Endpoints requis :**
- Intégré dans `GET /api/v1/tasks` avec paramètres de tri

### 7. Ajouter un commentaire à une tâche

**Critères d'acceptation détaillés :**
1. ✅ Éditeur de texte riche disponible pour saisir le commentaire
2. ✅ Commentaires horodatés et signés automatiquement
3. ✅ Commentaires affichés chronologiquement (plus récent en premier ou dernier selon préférence)
4. ✅ Commentaire peut mentionner membre (@nom) qui reçoit notification
5. ✅ Commentaire modifiable par son auteur dans les 15 minutes suivant création
6. ✅ Commentaire supprimable par son auteur ou chef de projet
7. ✅ Nombre de commentaires affiché dans liste des tâches
8. ✅ Nouveaux commentaires notifiés aux membres assignés

**Endpoints requis :**
- `POST /api/v1/tasks/{taskId}/comments` - Ajouter un commentaire
- `PUT /api/v1/comments/{commentId}` - Modifier un commentaire
- `DELETE /api/v1/comments/{commentId}` - Supprimer un commentaire

## EPIC 2 : Collaboration et communication

### 8. Notifications et mentions

**Besoins identifiés :**
- Notifications pour assignations, commentaires, mentions
- Système de notifications en temps réel

**Endpoints requis :**
- `GET /api/v1/notifications` - Lister mes notifications
- `PUT /api/v1/notifications/{id}/read` - Marquer comme lue
- `POST /api/v1/notifications/mark-all-read` - Tout marquer comme lu

## EPIC 3 : Time tracking

### 9. Saisir du temps sur une tâche

**Critères d'acceptation détaillés :**
1. ✅ Bouton "Saisir temps" disponible dans fiche tâche et liste
2. ✅ Formulaire contient : Date* (défaut aujourd'hui), Heures* (nombre décimal ou HH:MM), Description activité, Statut tâche après saisie (optionnel)
3. ✅ Saisie en heures décimales (ex: 2.5h) ou format HH:MM (ex: 2:30)
4. ✅ Système valide durée cohérente (<24h par jour)
5. ✅ Temps saisi ajouté à durée réelle de la tâche
6. ✅ Historique saisies temps conservé (date, membre, durée, description)
7. ✅ Membre peut modifier/supprimer ses saisies (délai 7 jours)
8. ✅ Chef projet peut modifier/supprimer toutes saisies
9. ✅ Notification chef projet en cas dépassement (temps réel > temps estimé)
10. ✅ Log d'audit enregistre chaque saisie de temps

**Endpoints requis :**
- `POST /api/v1/tasks/{taskId}/time-entries` - Saisir du temps
- `GET /api/v1/tasks/{taskId}/time-entries` - Historique du temps
- `PUT /api/v1/time-entries/{entryId}` - Modifier une saisie
- `DELETE /api/v1/time-entries/{entryId}` - Supprimer une saisie

### 10. Consulter mon temps saisi

**Critères d'acceptation détaillés :**
1. ✅ Vue "Mon temps" affiche toutes saisies du membre
2. ✅ Colonnes affichées : Date, Projet, Tâche, Durée, Description
3. ✅ Saisies triées par date (plus récente en premier)
4. ✅ Récapitulatif affiche : Temps total saisi (aujourd'hui, cette semaine, ce mois), Répartition par projet (graphique), Nombre tâches avec temps saisi
5. ✅ Saisies filtrables par : Projet, Période (semaine, mois, personnalisée)
6. ✅ Saisies exportables en Excel ou PDF
7. ✅ Bouton "Modifier" permet corriger saisie récente
8. ✅ Bouton "Supprimer" permet retirer saisie erronée

**Endpoints requis :**
- `GET /api/v1/users/{userId}/time-entries` - Mon temps saisi
- `GET /api/v1/users/{userId}/time-summary` - Récapitulatifs et stats
- `GET /api/v1/users/{userId}/time-entries/export` - Export des données

### 11. Voir le temps saisi par tâche

**Critères d'acceptation détaillés :**
1. ✅ Fiche tâche affiche : Durée estimée, Durée réelle (somme temps saisis), Écart (réel - estimé), % réalisation (réel / estimé × 100)
2. ✅ Code couleur : Vert (réel ≤ estimé), Orange (réel entre estimé et estimé+20%), Rouge (réel > estimé+20%)
3. ✅ Onglet "Historique temps" liste toutes saisies avec : Date, Membre, Durée, Description
4. ✅ Chef projet peut trier saisies par membre ou par date
5. ✅ Jauge visuelle représente % temps consommé par rapport estimation
6. ✅ Si aucune estimation, seul temps réel affiché

**Endpoints requis :**
- `GET /api/v1/tasks/{taskId}/time-summary` - Résumé temps tâche
- Intégré dans `GET /api/v1/tasks/{taskId}`

### 12. Voir le temps saisi par projet

**Critères d'acceptation détaillés :**
1. ✅ Onglet "Temps & Chiffrage" projet affiche : Total heures estimées (somme toutes tâches), Total heures réelles (somme tous temps saisis), Écart, % réalisation
2. ✅ Tableau détaille temps par membre : Nom, Heures saisies, Nb tâches, Taux horaire (si visible), Coût généré
3. ✅ Tableau détaille temps par type tâche : Type, Heures, % du total
4. ✅ Graphique montre évolution temps saisi au fil du temps (courbe cumulative)
5. ✅ Graphique montre répartition temps entre membres (camembert)
6. ✅ Données filtrables par période
7. ✅ Données exportables en Excel

**Endpoints requis :**
- `GET /api/v1/projects/{projectId}/time-summary` - Résumé temps projet
- `GET /api/v1/projects/{projectId}/time-entries` - Détails avec filtres
- `GET /api/v1/projects/{projectId}/time-analytics` - Graphiques et analytics

## RÉCAPITULATIF DES ENDPOINTS

### Gestion des tâches (CRUD de base)
1. `GET /api/v1/tasks` - Lister/filtrer/rechercher les tâches
2. `POST /api/v1/projects/{projectId}/tasks` - Créer une tâche
3. `GET /api/v1/tasks/{taskId}` - Détails d'une tâche
4. `PUT /api/v1/tasks/{taskId}` - Modifier une tâche
5. `DELETE /api/v1/tasks/{taskId}` - Supprimer une tâche

### Gestion des statuts
6. `GET /api/v1/task-statuses` - Lister les statuts disponibles
7. `PUT /api/v1/tasks/{taskId}/status` - Changer le statut

### Gestion des assignations
8. `POST /api/v1/tasks/{taskId}/assign` - Modifier les assignations
9. `GET /api/v1/projects/{projectId}/users` - Membres assignables

### Gestion des étiquettes
10. `GET /api/v1/tags` - Lister les étiquettes
11. `POST /api/v1/tags` - Créer une étiquette
12. `DELETE /api/v1/tags/{tagId}` - Supprimer une étiquette

### Commentaires
13. `GET /api/v1/tasks/{taskId}/comments` - Commentaires d'une tâche
14. `POST /api/v1/tasks/{taskId}/comments` - Ajouter un commentaire
15. `PUT /api/v1/comments/{commentId}` - Modifier un commentaire
16. `DELETE /api/v1/comments/{commentId}` - Supprimer un commentaire

### Gestion des fichiers
17. `GET /api/v1/tasks/{taskId}/files` - Lister les fichiers attachés
18. `POST /api/v1/tasks/{taskId}/files` - Joindre un fichier
19. `DELETE /api/v1/task-files/{fileId}` - Supprimer un fichier attaché
20. `GET /api/v1/task-files/{fileId}/download` - Télécharger un fichier

### Difficultés et blocages
21. `POST /api/v1/tasks/{taskId}/difficulties` - Déclarer une difficulté
22. `GET /api/v1/tasks/{taskId}/difficulties` - Lister les difficultés

### Historique et audit
23. `GET /api/v1/tasks/{taskId}/history` - Historique des modifications

### Vues personnalisées
24. `GET /api/v1/users/{userId}/task-views` - Mes vues sauvegardées
25. `POST /api/v1/users/{userId}/task-views` - Sauvegarder une vue
26. `PUT /api/v1/task-views/{viewId}` - Modifier une vue
27. `DELETE /api/v1/task-views/{viewId}` - Supprimer une vue

### Time tracking - Saisies
28. `POST /api/v1/tasks/{taskId}/time-entries` - Saisir du temps
29. `GET /api/v1/tasks/{taskId}/time-entries` - Temps d'une tâche
30. `PUT /api/v1/time-entries/{entryId}` - Modifier une saisie
31. `DELETE /api/v1/time-entries/{entryId}` - Supprimer une saisie

### Time tracking - Consultations personnelles
32. `GET /api/v1/users/{userId}/time-entries` - Mon temps saisi
33. `GET /api/v1/users/{userId}/time-summary` - Mes résumés et stats
34. `GET /api/v1/users/{userId}/time-entries/export` - Export mon temps

### Time tracking - Vues tâche
35. `GET /api/v1/tasks/{taskId}/time-summary` - Résumé temps tâche

### Time tracking - Vues projet
36. `GET /api/v1/projects/{projectId}/time-summary` - Résumé temps projet
37. `GET /api/v1/projects/{projectId}/time-entries` - Détails temps projet
38. `GET /api/v1/projects/{projectId}/time-analytics` - Analytics temps projet

### Notifications
39. `GET /api/v1/notifications` - Mes notifications
40. `PUT /api/v1/notifications/{id}/read` - Marquer comme lue
41. `POST /api/v1/notifications/mark-all-read` - Tout marquer comme lu

**TOTAL : 41 endpoints identifiés**

## MODÈLES DE DONNÉES REQUIS

### Table: tasks
```sql
- id (bigint, PK)
- code (varchar, unique) // TSK-YYYY-NNNN
- project_id (bigint, FK)
- parent_task_id (bigint, FK nullable) // Pour sous-tâches
- title (varchar, required)
- description (text, nullable)
- status (enum: a_faire, en_cours, bloque, test, termine)
- priority (enum: basse, normale, haute, critique)
- type (enum: dev, design, test, analyse, autre)
- estimated_hours (decimal, nullable)
- actual_hours (decimal, default 0) // Calculé
- due_date (datetime, nullable)
- created_by (bigint, FK)
- updated_by (bigint, FK)
- created_at, updated_at, deleted_at
```

### Table: task_assignments
```sql
- id (bigint, PK)
- task_id (bigint, FK)
- user_id (bigint, FK)
- assigned_at (datetime)
- assigned_by (bigint, FK)
- created_at, updated_at
```

### Table: task_tags
```sql
- id (bigint, PK)
- name (varchar, unique)
- color (varchar, nullable) // Code couleur hex
- created_by (bigint, FK)
- created_at, updated_at
```

### Table: task_tag_relations
```sql
- task_id (bigint, FK)
- tag_id (bigint, FK)
- Primary key(task_id, tag_id)
```

### Table: task_comments
```sql
- id (bigint, PK)
- task_id (bigint, FK)
- user_id (bigint, FK)
- content (text)
- mentions (json, nullable) // Utilisateurs mentionnés
- edited_at (datetime, nullable)
- created_at, updated_at, deleted_at
```

### Table: task_time_entries
```sql
- id (bigint, PK)
- task_id (bigint, FK)
- user_id (bigint, FK)
- date (date)
- hours (decimal) // Stockage en heures décimales
- description (text, nullable)
- created_at, updated_at, deleted_at
```

### Table: task_history
```sql
- id (bigint, PK)
- task_id (bigint, FK)
- user_id (bigint, FK)
- action (varchar) // created, updated, status_changed, etc.
- field_name (varchar, nullable)
- old_value (text, nullable)
- new_value (text, nullable)
- metadata (json, nullable)
- created_at
```

### Table: task_views (vues sauvegardées)
```sql
- id (bigint, PK)
- user_id (bigint, FK)
- name (varchar)
- filters (json) // Filtres sauvegardés
- sort_config (json) // Configuration de tri
- is_default (boolean, default false)
- created_at, updated_at
```

### Table: task_files
```sql
- id (bigint, PK)
- task_id (bigint, FK)
- original_name (varchar)
- stored_name (varchar)
- file_path (varchar)
- file_size (bigint)
- mime_type (varchar)
- uploaded_by (bigint, FK)
- created_at, updated_at, deleted_at
```

### Table: task_difficulties
```sql
- id (bigint, PK)
- task_id (bigint, FK)
- user_id (bigint, FK)
- type (enum: blocking, technical, resource, other)
- title (varchar)
- description (text)
- severity (enum: low, medium, high, critical)
- status (enum: open, in_progress, resolved)
- resolved_at (datetime, nullable)
- resolved_by (bigint, FK, nullable)
- resolution_notes (text, nullable)
- created_at, updated_at
```

### Table: notifications
```sql
- id (bigint, PK)
- user_id (bigint, FK)
- type (varchar) // task_assigned, comment_added, mention, etc.
- title (varchar)
- message (text)
- data (json) // Données contextuelles
- read_at (datetime, nullable)
- created_at, updated_at
```

## RÈGLES MÉTIER IMPORTANTES

### Permissions et sécurité
- **Membres assignés** : peuvent modifier description, date d'échéance, étiquettes, commentaires
- **Chef de projet** : peut modifier tous les champs
- **Propriétaire de commentaire** : peut modifier dans les 15 minutes
- **Propriétaire de saisie temps** : peut modifier/supprimer dans les 7 jours

### Validations
- Code automatique format TSK-YYYY-NNNN
- Durée temps < 24h par jour par utilisateur
- Statut "Bloqué" nécessite commentaire obligatoire
- Statut "Terminé" nécessite confirmation

### Automatisations
- Génération automatique du code tâche
- Calcul automatique actual_hours depuis time_entries
- Mise à jour % avancement projet quand tâche terminée
- Notifications automatiques (assignation, mention, etc.)
- Alertes dépassement temps estimé

### Intégrations requises
- Système de projets existant (table projects)
- Système d'utilisateurs existant (table users)
- Système de notifications (à intégrer)
- Système d'audit logging

## PRIORITÉS D'IMPLÉMENTATION

### Phase 1 - Core (MVP)
1. CRUD tâches de base (endpoints 1-5)
2. Gestion statuts (endpoints 6-7)
3. Assignations (endpoints 8-9)
4. Time tracking saisie (endpoints 22-23)

### Phase 2 - Collaboration
5. Commentaires (endpoints 13-16)
6. Étiquettes (endpoints 10-12)
7. Notifications (endpoints 33-35)

### Phase 3 - Reporting
8. Vues personnalisées (endpoints 18-21)
9. Time tracking avancé (endpoints 26-32)
10. Historique et audit (endpoint 17)

Cette analyse couvre l'intégralité des spécifications fournies et définit une architecture complète pour le module de gestion des tâches intégré au système de projets existant.