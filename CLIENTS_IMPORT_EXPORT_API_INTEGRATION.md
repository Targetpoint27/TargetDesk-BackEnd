# API Import/Export Clients TargetDesk - Guide d'intégration

## Base URL
```
http://localhost:8000/api/v1
```

## Authentification
Tous les endpoints d'import/export nécessitent une authentification Bearer token.

```
Authorization: Bearer {token}
```

---

## Fonctionnalités d'Import

### 1. Télécharger les modèles d'import

#### **CSV Template**
**GET** `/clients/export/template`

Télécharge un fichier CSV modèle avec les colonnes requises et un exemple de données.

```bash
curl -X GET http://localhost:8000/api/v1/clients/export/template \
  -H "Authorization: Bearer {token}" \
  -o template.csv
```

#### **Excel Template**
**GET** `/clients/export/template/excel`

Télécharge un fichier Excel (.xlsx) modèle avec mise en forme, validation et exemple de données.

```bash
curl -X GET http://localhost:8000/api/v1/clients/export/template/excel \
  -H "Authorization: Bearer {token}" \
  -o template.xlsx
```

**Caractéristiques du modèle Excel :**
- Headers traduits en français avec couleur
- Validation dropdown pour le champ "Type" (particulier/entreprise)
- Auto-sizing automatique des colonnes
- Exemple de données pré-rempli
- Compatibilité Microsoft Office/LibreOffice

**Colonnes du modèle :**
- `name` (requis) - Nom/Raison sociale
- `type` (requis) - "particulier" ou "entreprise"
- `email` (requis) - Email principal (unique)
- `phone` - Numéro de téléphone
- `address` - Adresse complète
- `siret` - SIRET (14 caractères, unique)
- `sector` - Secteur d'activité
- `website` - Site web (URL valide)
- `notes` - Notes libres

---

### 2. Prévisualisation d'import

**POST** `/clients/import/preview`

Upload et analyse un fichier CSV/Excel pour prévisualisation avant import définitif.

```bash
# Import CSV
curl -X POST http://localhost:8000/api/v1/clients/import/preview \
  -H "Authorization: Bearer {token}" \
  -F "file=@clients.csv" \
  -F "mapping[name]=name" \
  -F "mapping[email]=email"

# Import Excel
curl -X POST http://localhost:8000/api/v1/clients/import/preview \
  -H "Authorization: Bearer {token}" \
  -F "file=@clients.xlsx"
```

**Paramètres :**
- `file` (requis) - Fichier CSV/Excel (max 10MB)
- `mapping` (optionnel) - Configuration mapping colonnes vers champs

**Formats supportés :**
- ✅ **CSV** (.csv, .txt)
- ✅ **Excel** (.xlsx, .xls)

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Prévisualisation générée avec succès",
  "data": {
    "preview": [
      {
        "row_number": 2,
        "data": {
          "name": "Entreprise Test",
          "type": "entreprise",
          "email": "test@example.com",
          "phone": "0123456789",
          "address": "123 Rue Test",
          "siret": "12345678901234"
        },
        "validation": {
          "is_valid": true,
          "errors": []
        },
        "duplicate": {
          "is_duplicate": false,
          "conflicts": []
        }
      }
    ],
    "stats": {
      "total_rows": 10,
      "valid_rows": 9,
      "invalid_rows": 1,
      "duplicates_found": 2
    },
    "errors": [
      {
        "row_number": 3,
        "validation": {
          "errors": {
            "email": ["L'email doit être valide"]
          }
        }
      }
    ],
    "duplicates": [
      {
        "row_number": 5,
        "duplicate": {
          "conflicts": [
            {
              "field": "email",
              "value": "existing@example.com",
              "existing_client": {
                "id": 1,
                "client_id": "CLI-ABC123",
                "name": "Client Existant",
                "email": "existing@example.com"
              }
            }
          ]
        }
      }
    ]
  }
}
```

**Détection de doublons :**
- Email déjà utilisé
- SIRET déjà utilisé
- Affichage du client existant en conflit

**Validations :**
- Champs obligatoires (name, type, email)
- Format email valide
- Type "particulier" ou "entreprise"
- SIRET exactement 14 caractères
- URL valide pour website

---

### 3. Import définitif

**POST** `/clients/import`

Exécute l'import des clients avec gestion des doublons.

```bash
# Import CSV avec mapping
curl -X POST http://localhost:8000/api/v1/clients/import \
  -H "Authorization: Bearer {token}" \
  -F "file=@clients.csv" \
  -F "mapping[name]=name" \
  -F "mapping[email]=email" \
  -F "mapping[type]=type" \
  -F "duplicate_action=ignore"

# Import Excel direct
curl -X POST http://localhost:8000/api/v1/clients/import \
  -H "Authorization: Bearer {token}" \
  -F "file=@clients.xlsx" \
  -F "mapping[name]=name" \
  -F "mapping[email]=email" \
  -F "duplicate_action=update"
```

**Paramètres :**
- `file` (requis) - Fichier CSV/Excel
- `mapping` (requis) - Configuration mapping colonnes
- `duplicate_action` (requis) - Action pour les doublons :
  - `ignore` - Ignorer les doublons (les laisser inchangés)
  - `replace` - Remplacer complètement le client existant
  - `update` - Mettre à jour seulement les champs non vides

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Import terminé avec succès",
  "data": {
    "report": {
      "total_rows": 100,
      "imported": 85,
      "updated": 10,
      "ignored": 3,
      "errors": [
        {
          "row": 15,
          "errors": {
            "email": ["L'email doit être valide"]
          }
        },
        {
          "row": 23,
          "error": "SIRET déjà utilisé"
        }
      ],
      "warnings": [
        "Ligne 25: Client ignoré (doublon email)",
        "Ligne 30: Client mis à jour (SIRET existant)",
        "Ligne 45: Client remplacé (email existant)"
      ]
    }
  }
}
```

**Logs d'audit :**
Tous les imports sont automatiquement loggés avec :
- Utilisateur qui a effectué l'import
- Nombre de lignes traitées
- Nom du fichier source
- Actions effectuées (créations, mises à jour, erreurs)

---

## Fonctionnalités d'Export

### 1. Export de clients

**POST** `/clients/export`

Export la liste des clients vers CSV ou Excel avec filtres et sélection de colonnes.

```bash
# Export CSV basique
curl -X POST http://localhost:8000/api/v1/clients/export \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "format": "csv",
    "columns": ["client_id", "name", "email", "phone", "created_at"],
    "filters": {
      "type": "entreprise",
      "is_active": true,
      "search": "ACME"
    },
    "limit": 1000
  }' \
  -o export.csv

# Export Excel avec formatage
curl -X POST http://localhost:8000/api/v1/clients/export \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "format": "excel",
    "columns": ["client_id", "name", "type", "email", "phone", "categories", "created_at"],
    "filters": {
      "type": "entreprise"
    },
    "limit": 5000
  }' \
  -o export.xlsx

# Export sélection spécifique
curl -X POST http://localhost:8000/api/v1/clients/export \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "format": "excel",
    "client_ids": [1, 5, 10, 15],
    "columns": ["name", "email", "phone", "address"]
  }' \
  -o selection.xlsx
```

**Paramètres :**
- `format` (optionnel) - "csv" ou "excel" (défaut: csv)
- `columns` (optionnel) - Colonnes à exporter (défaut: toutes)
- `filters` (optionnel) - Filtres à appliquer :
  - `type` - Type de client ("particulier", "entreprise")
  - `is_active` - Clients actifs/inactifs (true/false)
  - `search` - Recherche dans nom, email, client_id
- `client_ids` (optionnel) - Array d'IDs spécifiques à exporter
- `limit` (optionnel) - Limite max (défaut: 10000)

**Colonnes disponibles :**
- `client_id` - Identifiant unique généré
- `name` - Nom/Raison sociale
- `type` - Type de client
- `email` - Email principal
- `phone` - Téléphone
- `address` - Adresse complète
- `siret` - SIRET
- `sector` - Secteur d'activité
- `website` - Site web
- `notes` - Notes libres
- `is_active` - Statut actif/inactif
- `created_at` - Date de création
- `updated_at` - Dernière modification
- `creator` - Nom du créateur
- `categories` - Catégories assignées (nom des catégories)

**Réponse :**
Fichier CSV/Excel téléchargeable avec les données sélectionnées.

**Format CSV :**
```csv
client_id,name,type,email,phone,categories,created_at
CLI-ABC123,Entreprise ACME,entreprise,contact@acme.com,0123456789,"Grande Entreprise, Secteur Tech",2026-01-09 17:57:31
CLI-DEF456,Jean Dupont,particulier,jean@example.com,0987654321,"Client VIP",2026-01-10 10:30:15
```

**Format Excel :**
- Headers avec mise en forme (couleur, gras)
- Auto-sizing automatique des colonnes
- Formatage des dates lisible
- Conversion booléens (true/false → Actif/Inactif)
- Gestion correcte des caractères spéciaux

**Limitations :**
- Maximum 10 000 lignes par export (configurable)
- Fichiers générés à la volée (pas de stockage serveur)
- Timeout 2 minutes pour gros volumes

---

## Gestion des erreurs

### Codes d'erreur

- `200` - Succès
- `201` - Créé avec succès
- `422` - Erreur de validation
- `401` - Non authentifié (token manquant/invalide)
- `404` - Ressource non trouvée
- `500` - Erreur serveur

### Messages d'erreur import

```json
{
  "success": false,
  "message": "Erreur lors de l'import",
  "errors": {
    "file": ["Le fichier est requis"],
    "mapping": ["Le mapping doit être un tableau"],
    "duplicate_action": ["L'action doit être ignore, replace ou update"]
  }
}
```

**Erreurs spécifiques :**
```json
{
  "success": false,
  "message": "Format de fichier non supporté: .pdf"
}
```

```json
{
  "success": false,
  "message": "Erreur lors de la lecture du fichier Excel: Corrupted file"
}
```

### Messages d'erreur export

```json
{
  "success": false,
  "message": "Erreur lors de l'export",
  "errors": {
    "limit": ["La limite doit être entre 1 et 10000"],
    "client_ids": ["Les IDs clients doivent exister"],
    "format": ["Le format doit être csv ou excel"]
  }
}
```

---

## Exemples d'intégration

### JavaScript/Fetch

```javascript
class ClientImportExport {
  constructor(token) {
    this.baseURL = 'http://localhost:8000/api/v1';
    this.token = token;
  }

  // Télécharger le modèle CSV
  async downloadTemplate(format = 'csv') {
    const endpoint = format === 'excel'
      ? '/clients/export/template/excel'
      : '/clients/export/template';

    const response = await fetch(`${this.baseURL}${endpoint}`, {
      headers: {
        'Authorization': `Bearer ${this.token}`,
      }
    });

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `template_clients.${format === 'excel' ? 'xlsx' : 'csv'}`;
    a.click();
    window.URL.revokeObjectURL(url);
  }

  // Prévisualisation d'import
  async previewImport(file, mapping = {}) {
    const formData = new FormData();
    formData.append('file', file);

    Object.entries(mapping).forEach(([key, value]) => {
      formData.append(`mapping[${key}]`, value);
    });

    const response = await fetch(`${this.baseURL}/clients/import/preview`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
      },
      body: formData
    });

    return await response.json();
  }

  // Import définitif
  async importClients(file, mapping, duplicateAction = 'ignore') {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('duplicate_action', duplicateAction);

    Object.entries(mapping).forEach(([key, value]) => {
      formData.append(`mapping[${key}]`, value);
    });

    const response = await fetch(`${this.baseURL}/clients/import`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
      },
      body: formData
    });

    return await response.json();
  }

  // Export de clients
  async exportClients(options = {}) {
    const defaultOptions = {
      format: 'csv',
      columns: ['client_id', 'name', 'type', 'email', 'phone'],
      limit: 10000
    };

    const exportOptions = { ...defaultOptions, ...options };

    const response = await fetch(`${this.baseURL}/clients/export`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(exportOptions)
    });

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;

    const extension = exportOptions.format === 'excel' ? 'xlsx' : 'csv';
    a.download = `export_clients_${new Date().toISOString().split('T')[0]}.${extension}`;
    a.click();
    window.URL.revokeObjectURL(url);
  }
}

// Utilisation complète
const importer = new ClientImportExport('your-bearer-token');

// 1. Télécharger les modèles
await importer.downloadTemplate('csv');   // Modèle CSV
await importer.downloadTemplate('excel'); // Modèle Excel

// 2. Import avec prévisualisation
const fileInput = document.getElementById('fileInput');
const file = fileInput.files[0];

// Prévisualisation d'abord
const preview = await importer.previewImport(file, {
  name: 'name',
  email: 'email',
  type: 'type',
  phone: 'phone'
});

console.log('Statistiques:', preview.data.stats);
console.log('Erreurs:', preview.data.errors);
console.log('Doublons:', preview.data.duplicates);

// Import définitif si validation OK
if (preview.data.stats.valid_rows > 0) {
  const result = await importer.importClients(file, {
    name: 'name',
    email: 'email',
    type: 'type',
    phone: 'phone',
    address: 'address',
    siret: 'siret'
  }, 'update'); // Mettre à jour les doublons

  console.log('Import terminé:', result.data.report);
}

// 3. Exports personnalisés
// Export CSV basique
await importer.exportClients({
  format: 'csv',
  columns: ['client_id', 'name', 'email'],
  filters: { type: 'entreprise' },
  limit: 500
});

// Export Excel avec toutes les colonnes
await importer.exportClients({
  format: 'excel',
  columns: ['client_id', 'name', 'type', 'email', 'phone', 'address', 'categories', 'created_at'],
  filters: { is_active: true }
});

// Export sélection spécifique
await importer.exportClients({
  format: 'excel',
  client_ids: [1, 5, 10, 15, 20],
  columns: ['name', 'email', 'phone']
});
```

### PHP/cURL

```php
class ClientImportExport {
    private $baseURL = 'http://localhost:8000/api/v1';
    private $token;

    public function __construct($token) {
        $this->token = $token;
    }

    public function downloadTemplate($format = 'csv') {
        $endpoint = $format === 'excel'
            ? '/clients/export/template/excel'
            : '/clients/export/template';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseURL . $endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $extension = $format === 'excel' ? 'xlsx' : 'csv';
            $filename = "template_clients_" . date('Y-m-d') . ".$extension";
            file_put_contents($filename, $response);
            return $filename;
        }

        return false;
    }

    public function previewImport($filePath, $mapping = []) {
        $ch = curl_init();

        $postFields = [
            'file' => new CURLFile($filePath),
        ];

        foreach ($mapping as $key => $value) {
            $postFields["mapping[$key]"] = $value;
        }

        curl_setopt($ch, CURLOPT_URL, $this->baseURL . '/clients/import/preview');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function importClients($filePath, $mapping, $duplicateAction = 'ignore') {
        $ch = curl_init();

        $postFields = [
            'file' => new CURLFile($filePath),
            'duplicate_action' => $duplicateAction
        ];

        foreach ($mapping as $key => $value) {
            $postFields["mapping[$key]"] = $value;
        }

        curl_setopt($ch, CURLOPT_URL, $this->baseURL . '/clients/import');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function exportClients($options = []) {
        $defaultOptions = [
            'format' => 'csv',
            'columns' => ['client_id', 'name', 'type', 'email', 'phone'],
            'limit' => 10000
        ];

        $exportOptions = array_merge($defaultOptions, $options);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseURL . '/clients/export');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($exportOptions));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $extension = $exportOptions['format'] === 'excel' ? 'xlsx' : 'csv';
        $filename = 'export_clients_' . date('Y-m-d_H-i-s') . ".$extension";
        file_put_contents($filename, $response);

        return $filename;
    }
}

// Utilisation complète
$importer = new ClientImportExport('your-bearer-token');

// 1. Télécharger les modèles
$csvTemplate = $importer->downloadTemplate('csv');
$excelTemplate = $importer->downloadTemplate('excel');

echo "Modèles téléchargés: $csvTemplate, $excelTemplate" . PHP_EOL;

// 2. Prévisualisation avant import
$preview = $importer->previewImport('clients.xlsx', [
    'name' => 'name',
    'email' => 'email',
    'type' => 'type',
    'phone' => 'phone'
]);

echo "Lignes valides: " . $preview['data']['stats']['valid_rows'] . PHP_EOL;
echo "Lignes invalides: " . $preview['data']['stats']['invalid_rows'] . PHP_EOL;
echo "Doublons détectés: " . $preview['data']['stats']['duplicates_found'] . PHP_EOL;

// Affichage des erreurs
if (!empty($preview['data']['errors'])) {
    echo "Erreurs détectées:" . PHP_EOL;
    foreach ($preview['data']['errors'] as $error) {
        echo "  Ligne {$error['row_number']}: " . implode(', ', $error['validation']['errors']) . PHP_EOL;
    }
}

// 3. Import définitif si validation OK
if ($preview['data']['stats']['valid_rows'] > 0) {
    $result = $importer->importClients('clients.xlsx', [
        'name' => 'name',
        'email' => 'email',
        'type' => 'type',
        'phone' => 'phone',
        'address' => 'address',
        'siret' => 'siret'
    ], 'update');

    $report = $result['data']['report'];
    echo "Import terminé:" . PHP_EOL;
    echo "  - Importés: {$report['imported']}" . PHP_EOL;
    echo "  - Mis à jour: {$report['updated']}" . PHP_EOL;
    echo "  - Ignorés: {$report['ignored']}" . PHP_EOL;
    echo "  - Erreurs: " . count($report['errors']) . PHP_EOL;
}

// 4. Exports variés
// Export CSV des entreprises
$csvFile = $importer->exportClients([
    'format' => 'csv',
    'columns' => ['client_id', 'name', 'email', 'phone'],
    'filters' => ['type' => 'entreprise'],
    'limit' => 1000
]);

// Export Excel complet avec catégories
$excelFile = $importer->exportClients([
    'format' => 'excel',
    'columns' => ['client_id', 'name', 'type', 'email', 'phone', 'categories', 'created_at'],
    'filters' => ['is_active' => true]
]);

// Export sélection spécifique
$selectionFile = $importer->exportClients([
    'format' => 'excel',
    'client_ids' => [1, 5, 10, 15, 20],
    'columns' => ['name', 'email', 'phone', 'address']
]);

echo "Exports créés: $csvFile, $excelFile, $selectionFile" . PHP_EOL;
```

---

## Bonnes pratiques

### Import
1. **Toujours prévisualiser** avant l'import définitif
2. **Utiliser les modèles fournis** pour éviter les erreurs de format
3. **Nettoyer les données** en amont (espaces, caractères spéciaux)
4. **Tester avec un petit échantillon** avant l'import complet
5. **Choisir la bonne action** pour les doublons selon le contexte :
   - `ignore` : pour préserver les données existantes
   - `update` : pour compléter les informations manquantes
   - `replace` : pour remplacer complètement (attention aux pertes)
6. **Vérifier les logs** après import pour audit complet
7. **Préférer Excel** pour la validation automatique des données

### Export
1. **Limiter les colonnes** nécessaires pour optimiser les performances
2. **Utiliser des filtres** pour réduire le volume de données
3. **Respecter la limite** de 10 000 lignes par export
4. **Planifier les exports volumineux** en dehors des heures de pointe
5. **Préférer Excel** pour le formatage et la lisibilité
6. **Utiliser des noms de fichiers descriptifs** avec horodatage

### Sécurité
1. **Valider les fichiers** côté client avant upload (taille, format)
2. **Respecter les limites** de taille de fichier (10MB)
3. **Auditer tous les imports/exports** via les logs automatiques
4. **Contrôler les permissions** d'accès aux fonctionnalités
5. **Ne pas stocker** les fichiers uploadés sur le serveur
6. **Utiliser HTTPS** en production pour la transmission sécurisée

### Performance
1. **Traitement en streaming** pour les gros fichiers
2. **Pagination automatique** des résultats d'export
3. **Cache des templates** pour optimiser les téléchargements
4. **Compression automatique** des exports Excel volumineux

---

## Comparaison CSV vs Excel

| Caractéristique | CSV | Excel |
|-----------------|-----|--------|
| **Taille fichier** | ✅ Léger | ⚠️ Plus volumineux |
| **Compatibilité** | ✅ Universel | ✅ Bureautique |
| **Formatage** | ❌ Aucun | ✅ Couleurs, styles |
| **Validation** | ❌ Manuelle | ✅ Automatique |
| **Lisibilité** | ⚠️ Basique | ✅ Excellente |
| **Performance** | ✅ Rapide | ⚠️ Plus lent |
| **Édition** | ⚠️ Limitée | ✅ Avancée |

**Recommandations :**
- **CSV** : intégrations automatisées, gros volumes, simplicité
- **Excel** : manipulation manuelle, présentation, validation

---

## Limitations techniques

- **Taille maximum** : 10MB par fichier (configurable)
- **Formats supportés** : CSV, TXT, Excel (.xlsx, .xls)
- **Limite export** : 10 000 lignes par export (configurable)
- **Timeout** : 2 minutes pour les opérations d'import/export
- **Encodage** : UTF-8 recommandé pour les caractères spéciaux
- **Mémoire** : Traitement en streaming pour optimiser l'usage mémoire
- **Concurrence** : Support multi-utilisateurs avec file d'attente

---

## Endpoints disponibles

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/clients/export/template` | GET | Télécharger modèle CSV |
| `/clients/export/template/excel` | GET | Télécharger modèle Excel |
| `/clients/import/preview` | POST | Prévisualiser import |
| `/clients/import` | POST | Importer clients |
| `/clients/export` | POST | Exporter clients |

---

## Documentation Swagger

La documentation interactive complète est disponible sur :
**http://localhost:8000/api/documentation**

Section **"Client Import/Export"** avec tous les endpoints testables directement dans l'interface.

**Fonctionnalités Swagger :**
- Test en temps réel des endpoints
- Upload de fichiers de test
- Visualisation des réponses
- Génération de code d'exemple
- Authentification intégrée

---

## Support et maintenance

- **Logs** : `storage/logs/laravel.log`
- **Monitoring** : Tous les imports/exports sont loggés automatiquement
- **Performance** : Traitement en streaming pour les gros fichiers
- **Évolutivité** : Architecture modulaire pour ajouter d'autres formats
- **Sauvegardes** : Les données importantes sont sauvegardées avant modification
- **Rollback** : Possibilité de restaurer en cas de problème majeur

**Logs d'audit typiques :**
```
[2026-01-16 11:42:38] Import preview generated {filename: "clients.xlsx", rows_count: 150, user_id: 6}
[2026-01-16 11:43:15] Client import completed {filename: "clients.xlsx", imported: 145, errors: 5, user_id: 6}
[2026-01-16 11:45:20] Client export completed {format: "excel", count: 1250, columns: ["name", "email"], user_id: 6}
```