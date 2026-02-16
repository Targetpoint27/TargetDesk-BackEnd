# 📋 Documentation Système Client Extensible - TargetDesk

## 🎯 Vue d'ensemble

Ce document détaille l'implémentation du système client extensible avec champs personnalisés et gestion hiérarchique des documents. Le système remplace l'approche statique par une solution dynamique permettant aux utilisateurs de créer leurs propres champs et d'organiser les documents en dossiers.

---

## 🚀 Nouvelles Fonctionnalités

### 1. Champs Personnalisés Dynamiques
- **Format clé-valeur simple** : `{"field_key": "marque", "field_value": "Nike"}`
- **Intégration directe** dans les endpoints clients existants
- **Validation automatique** des clés (alphanumérique + underscore)
- **Stockage flexible** avec génération automatique des labels

### 2. Système de Dossiers Hiérarchiques
- **Structure arborescente** : Dossiers et sous-dossiers illimités
- **Gestion par chemin** : Format `KYC/Financier/Bilans`
- **Organisation KYC** : Optimisé pour les documents de conformité
- **Compatibilité rétroactive** avec les catégories existantes
- **Dossiers virtuels** : Création sans fichier placeholder, apparition automatique lors de l'upload

---

## 🔧 API Endpoints

### Clients avec Champs Personnalisés

#### Créer un client
```http
POST /api/v1/clients
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Entreprise ACME",
  "type": "entreprise",
  "email": "contact@acme.com",
  "phone": "0123456789",
  "custom_fields": [
    {
      "field_key": "marque",
      "field_value": "Nike"
    },
    {
      "field_key": "budget_annuel",
      "field_value": "50000"
    },
    {
      "field_key": "secteur_activite",
      "field_value": "Sport"
    }
  ]
}
```

#### Modifier un client
```http
PUT /api/v1/clients/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Entreprise ACME Modifiée",
  "custom_fields": [
    {
      "field_key": "marque",
      "field_value": "Adidas"
    },
    {
      "field_key": "priorite",
      "field_value": "haute"
    }
  ]
}
```

#### Récupérer un client
```http
GET /api/v1/clients/{id}
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "id": 788,
    "name": "Entreprise ACME",
    "custom_fields": [
      {
        "field_key": "marque",
        "field_value": "Adidas",
        "field_label": "Marque",
        "formatted_value": "Adidas"
      }
    ]
  }
}
```

### Gestion des Dossiers

#### Créer un dossier racine
```http
POST /api/v1/clients/{client_id}/documents/folders
Authorization: Bearer {token}
Content-Type: application/json

{
  "folder_name": "KYC",
  "description": "Documents Know Your Customer"
}
```

#### Créer un sous-dossier
```http
POST /api/v1/clients/{client_id}/documents/folders
Authorization: Bearer {token}
Content-Type: application/json

{
  "folder_name": "Financier",
  "parent_path": "KYC",
  "description": "Documents financiers du client"
}
```

#### Lister la structure des dossiers
```http
GET /api/v1/clients/{client_id}/documents/folders
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "folders": [
      {
        "path": "KYC",
        "name": "KYC",
        "level": 0,
        "document_count": 1
      },
      {
        "path": "KYC/Financier",
        "name": "Financier",
        "level": 1,
        "document_count": 3
      }
    ],
    "documents_by_folder": {
      "KYC": {
        "path": "KYC",
        "name": "KYC",
        "level": 0,
        "documents": [...]
      },
      "KYC/Financier": {
        "path": "KYC/Financier",
        "name": "Financier",
        "level": 1,
        "documents": [...]
      }
    }
  }
}

Note: Les dossiers n'apparaissent que s'ils contiennent des documents réels (pas de fichiers placeholder).
```

### Upload de Documents avec Assignation de Dossier

#### Upload dans un dossier spécifique
```http
POST /api/v1/clients/{client_id}/documents
Authorization: Bearer {token}
Content-Type: multipart/form-data

file: [fichier]
title: "Bilan financier 2024"
description: "Bilan annuel de l'entreprise"
folder_path: "KYC/Financier"
```

#### Upload traditionnel (avec catégorie)
```http
POST /api/v1/clients/{client_id}/documents
Authorization: Bearer {token}
Content-Type: multipart/form-data

file: [fichier]
title: "Contrat commercial"
description: "Contrat principal"
category: "contrat"
```

---

## 💾 Structure de Base de Données

### Nouvelles Tables

#### `client_custom_fields`
```sql
CREATE TABLE client_custom_fields (
  id bigint PRIMARY KEY AUTO_INCREMENT,
  client_id bigint NOT NULL,
  created_by bigint NOT NULL,
  field_key varchar(100) NOT NULL,
  field_value text,
  field_type varchar(50) DEFAULT 'text',
  field_label varchar(200),
  field_description text,
  field_options json,
  is_required boolean DEFAULT FALSE,
  is_active boolean DEFAULT TRUE,
  display_order int DEFAULT 0,
  created_at timestamp,
  updated_at timestamp,
  UNIQUE KEY unique_client_field (client_id, field_key)
);
```

### Tables Modifiées

#### `client_documents` - Ajout colonnes dossiers
```sql
ALTER TABLE client_documents ADD COLUMN (
  folder_path varchar(500) NULL,
  folder_name varchar(200) NULL,
  folder_level int DEFAULT 0,
  legacy_category varchar(50) NULL
);
```

---

## 🎨 Intégration Frontend

### Interface de Champs Personnalisés

#### Composant FormField Dynamique
```typescript
interface CustomField {
  field_key: string;
  field_value: string;
}

interface ClientFormData {
  name: string;
  type: 'particulier' | 'entreprise';
  email?: string;
  phone?: string;
  custom_fields: CustomField[];
}

const ClientForm: React.FC = () => {
  const [formData, setFormData] = useState<ClientFormData>({
    name: '',
    type: 'entreprise',
    custom_fields: []
  });

  const addCustomField = () => {
    setFormData(prev => ({
      ...prev,
      custom_fields: [...prev.custom_fields, { field_key: '', field_value: '' }]
    }));
  };

  const updateCustomField = (index: number, key: string, value: string) => {
    const updatedFields = [...formData.custom_fields];
    updatedFields[index] = { field_key: key, field_value: value };
    setFormData(prev => ({ ...prev, custom_fields: updatedFields }));
  };

  return (
    <form>
      {/* Champs standards */}
      <input
        type="text"
        placeholder="Nom/Raison sociale"
        value={formData.name}
        onChange={(e) => setFormData(prev => ({ ...prev, name: e.target.value }))}
      />

      {/* Champs personnalisés */}
      <div className="custom-fields-section">
        <h3>Champs Personnalisés</h3>
        {formData.custom_fields.map((field, index) => (
          <div key={index} className="custom-field-row">
            <input
              type="text"
              placeholder="Clé (ex: marque)"
              value={field.field_key}
              onChange={(e) => updateCustomField(index, e.target.value, field.field_value)}
            />
            <input
              type="text"
              placeholder="Valeur (ex: Nike)"
              value={field.field_value}
              onChange={(e) => updateCustomField(index, field.field_key, e.target.value)}
            />
            <button type="button" onClick={() => removeField(index)}>×</button>
          </div>
        ))}
        <button type="button" onClick={addCustomField}>+ Ajouter un champ</button>
      </div>
    </form>
  );
};
```

### Interface de Gestion des Dossiers

#### Composant FolderTree
```typescript
interface Folder {
  path: string;
  name: string;
  level: number;
  document_count: number;
}

const FolderManager: React.FC<{ clientId: number }> = ({ clientId }) => {
  const [folders, setFolders] = useState<Folder[]>([]);
  const [selectedFolder, setSelectedFolder] = useState<string>('');

  const createFolder = async (folderName: string, parentPath?: string) => {
    const response = await fetch(`/api/v1/clients/${clientId}/documents/folders`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({
        folder_name: folderName,
        parent_path: parentPath,
        description: `Dossier ${folderName}`
      })
    });

    if (response.ok) {
      loadFolders(); // Recharger la liste
    }
  };

  const renderFolderTree = (folders: Folder[]) => {
    return folders.map(folder => (
      <div
        key={folder.path}
        className={`folder-item level-${folder.level}`}
        onClick={() => setSelectedFolder(folder.path)}
      >
        <span className="folder-icon">📁</span>
        <span className="folder-name">{folder.name}</span>
        <span className="document-count">({folder.document_count})</span>
      </div>
    ));
  };

  return (
    <div className="folder-manager">
      <div className="folder-tree">
        {renderFolderTree(folders)}
      </div>
      <div className="folder-actions">
        <button onClick={() => createFolder('Nouveau Dossier', selectedFolder)}>
          + Créer Dossier
        </button>
      </div>
    </div>
  );
};
```

### Upload avec Sélection de Dossier

#### Composant DocumentUpload
```typescript
const DocumentUpload: React.FC<{ clientId: number }> = ({ clientId }) => {
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [selectedFolder, setSelectedFolder] = useState<string>('');
  const [folders, setFolders] = useState<Folder[]>([]);

  const uploadDocument = async () => {
    if (!selectedFile) return;

    const formData = new FormData();
    formData.append('file', selectedFile);
    formData.append('title', selectedFile.name);
    formData.append('description', 'Document uploadé depuis l\'interface');

    if (selectedFolder) {
      formData.append('folder_path', selectedFolder);
    } else {
      formData.append('category', 'autre');
    }

    const response = await fetch(`/api/v1/clients/${clientId}/documents`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`
      },
      body: formData
    });

    if (response.ok) {
      // Success handling
      setSelectedFile(null);
      setSelectedFolder('');
    }
  };

  return (
    <div className="document-upload">
      <input
        type="file"
        onChange={(e) => setSelectedFile(e.target.files?.[0] || null)}
      />

      <select
        value={selectedFolder}
        onChange={(e) => setSelectedFolder(e.target.value)}
      >
        <option value="">Sélectionnez un dossier</option>
        {folders.map(folder => (
          <option key={folder.path} value={folder.path}>
            {'  '.repeat(folder.level)}📁 {folder.name}
          </option>
        ))}
      </select>

      <button onClick={uploadDocument} disabled={!selectedFile}>
        📤 Upload
      </button>
    </div>
  );
};
```

---

## 🔄 Migration et Mise à Jour

### Étapes de Déploiement

1. **Sauvegarde Base de Données**
   ```bash
   mysqldump -u root -p targetdesk_db > backup_pre_migration.sql
   ```

2. **Exécuter les Migrations**
   ```bash
   php artisan migrate
   ```

3. **Nettoyer les Fichiers Placeholders Existants (si nécessaire)**
   ```sql
   -- Supprimer les anciens fichiers placeholder
   DELETE FROM client_documents
   WHERE title = '.folder_placeholder'
   AND mime_type = 'application/x-folder';
   ```

4. **Vérifier l'Intégrité**
   ```bash
   php artisan route:list --path=api/v1/clients
   ```

### Migration des Données Existantes

Les anciennes catégories sont automatiquement migrées vers le nouveau système de dossiers :

```sql
-- Migration automatique dans la migration
UPDATE client_documents
SET folder_path = category,
    folder_name = category,
    legacy_category = category
WHERE category IS NOT NULL;
```

### Nettoyage Post-Migration

Si vous aviez des fichiers placeholder créés avant cette mise à jour :

```sql
-- Nettoyer les anciens placeholders
UPDATE client_documents
SET is_active = false
WHERE title = '.folder_placeholder';
```

### Compatibilité Rétroactive

- ✅ **Anciennes API** : Continuent de fonctionner
- ✅ **Catégories** : Toujours supportées
- ✅ **Documents existants** : Automatiquement migrés
- ✅ **Clients existants** : Peuvent recevoir des champs personnalisés

---

## 📊 Exemples d'Utilisation

### Cas d'Usage : Agence Immobilière

```javascript
// Client avec informations spécifiques immobilier
const clientImmobilier = {
  name: "Investisseur Premium",
  type: "particulier",
  email: "client@example.com",
  custom_fields: [
    { field_key: "budget_max", field_value: "500000" },
    { field_key: "type_bien", field_value: "appartement" },
    { field_key: "surface_min", field_value: "80" },
    { field_key: "secteur_prefere", field_value: "centre-ville" },
    { field_key: "financement", field_value: "pret" }
  ]
};

// Structure de dossiers KYC
const dossiers = [
  "KYC/Identite",
  "KYC/Financier/Revenus",
  "KYC/Financier/Patrimoine",
  "Projets/Recherches",
  "Projets/Visites",
  "Contrats"
];
```

### Cas d'Usage : Société de Services

```javascript
// Client entreprise avec besoins spécifiques
const clientEntreprise = {
  name: "Tech Innovation SAS",
  type: "entreprise",
  custom_fields: [
    { field_key: "nb_employees", field_value: "50" },
    { field_key: "chiffre_affaires", field_value: "2500000" },
    { field_key: "secteur", field_value: "technologie" },
    { field_key: "decision_maker", field_value: "Jean Dupont" },
    { field_key: "budget_annuel", field_value: "100000" }
  ]
};
```

---

## 🎯 Fonctionnement des Dossiers Virtuels

### Concept
Le système utilise des **dossiers virtuels** qui :
- **Se créent sans fichier** lors de l'appel API de création
- **Apparaissent automatiquement** dès qu'un document y est uploadé
- **Disparaissent** si tous leurs documents sont supprimés

### Avantages
- ✅ **Interface propre** : Pas de fichiers fantômes ou placeholders
- ✅ **Performance** : Moins d'enregistrements en base
- ✅ **UX intuitive** : Les dossiers vides n'encombrent pas l'interface
- ✅ **Cohérence** : Seuls les dossiers avec contenu sont affichés

### Exemple de Workflow
1. **Création** : `POST /folders` → Dossier créé virtuellement
2. **Vérification** : `GET /folders` → Dossier n'apparaît pas (normal)
3. **Upload** : `POST /documents` avec `folder_path` → Document ajouté
4. **Affichage** : `GET /folders` → Dossier apparaît avec son document

---

## 🛠️ Maintenance et Optimisation

### Indexation Base de Données

```sql
-- Index pour améliorer les performances
CREATE INDEX idx_custom_fields_client_key ON client_custom_fields(client_id, field_key);
CREATE INDEX idx_documents_folder ON client_documents(client_id, folder_path);
CREATE INDEX idx_documents_level ON client_documents(client_id, folder_level);
```

### Nettoyage Périodique

```php
// Commande Artisan pour nettoyage
php artisan make:command CleanupCustomFields

// Supprimer les champs inactifs anciens
ClientCustomField::where('is_active', false)
    ->where('updated_at', '<', now()->subMonths(6))
    ->delete();
```

### Monitoring

- **Métriques** : Nombre de champs personnalisés par client
- **Performance** : Temps de réponse des endpoints
- **Usage** : Dossiers les plus utilisés
- **Stockage** : Taille des documents par dossier

---

## 🚨 Points d'Attention

### Limites Techniques
- **Clés de champs** : Maximum 100 caractères, alphanumérique + underscore
- **Valeurs** : Texte libre, maximum 1000 caractères
- **Niveaux de dossiers** : Illimités mais recommandé max 5 niveaux
- **Chemin de dossier** : Maximum 500 caractères

### Sécurité
- **Validation** : Toutes les clés sont validées côté serveur
- **Autorisation** : Vérification des permissions sur chaque endpoint
- **Injection SQL** : Protection par Eloquent ORM
- **Upload** : Validation des types de fichiers

### Performance
- **Cache** : Considérer la mise en cache des structures de dossiers
- **Pagination** : Implémenter pour les listes importantes
- **Index** : Créer des index sur les colonnes fréquemment recherchées

---

## 📞 Support

Pour toute question ou problème d'intégration :

1. **Documentation API** : `/api/documentation`
2. **Tests** : Utiliser Postman avec les exemples fournis
3. **Logs** : Consulter `storage/logs/laravel.log`
4. **Debug** : Activer le mode debug en développement

---

## 📋 Changelog

### Version 2.1 - 15 février 2026
- ✅ **Fix majeur** : Suppression des fichiers placeholder automatiques
- ✅ **Dossiers virtuels** : Création de dossiers sans fichiers fantômes
- ✅ **API optimisée** : Filtrage automatique des placeholders dans les réponses
- ✅ **UX améliorée** : Interface plus propre sans fichiers inutiles
- ✅ **Performance** : Réduction des enregistrements en base de données

### Version 2.0 - 14 février 2026
- 🚀 **Système extensible** : Champs personnalisés clé-valeur
- 🚀 **Dossiers hiérarchiques** : Organisation des documents KYC
- 🚀 **Intégration complète** : API unifiée dans les endpoints clients
- 🚀 **Migration automatique** : Compatibilité avec l'ancien système

---

*Document généré automatiquement - TargetDesk v2.1*
*Dernière mise à jour : 15 février 2026*