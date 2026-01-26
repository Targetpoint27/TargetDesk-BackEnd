# 📊 Export Timeline Client - Documentation Complète

## 🎯 Vue d'ensemble

L'API d'export timeline permet d'exporter l'historique complet des interactions client en formats CSV et Excel. Cette fonctionnalité offre une traçabilité complète pour l'analyse commerciale et le reporting.

**Endpoint principal :** `GET /api/v1/clients/{client}/timeline/export`

---

## 🔧 Spécifications Techniques

### Authentification
- **Requis :** Token Bearer Sanctum
- **Permissions :** Accès aux données client selon les règles de visibilité

### Formats Supportés
- **CSV** : Format standard pour tableurs (défaut)
- **Excel (XLSX)** : Format Microsoft Excel natif

### Limites
- **Pas de limite** sur le nombre d'enregistrements
- **Timeout** : 120 secondes pour gros exports
- **Sécurité** : Respect des niveaux de confidentialité utilisateur

---

## 📚 Guide d'utilisation

### Syntaxe de base
```http
GET /api/v1/clients/{client_id}/timeline/export?format={format}
```

### Paramètres

| Paramètre | Type | Requis | Valeurs | Défaut | Description |
|-----------|------|--------|---------|--------|-------------|
| `client_id` | int | ✅ | ID client valide | - | Identifiant du client |
| `format` | string | ❌ | `csv`, `xlsx` | `csv` | Format d'export |

---

## 🧪 Tests Complets et Exemples

### ✅ Test 1: Export CSV Standard

**Requête :**
```bash
curl -X GET "http://localhost:8000/api/v1/clients/1/timeline/export?format=csv" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Réponse attendue :**
```json
{
  "success": true,
  "message": "Export préparé",
  "data": {
    "filename": "timeline_client_1_2026-01-22_09-18-47.csv",
    "format": "csv",
    "records_count": 11,
    "download_url": "http://localhost/exports/timeline_client_1_2026-01-22_09-18-47.csv",
    "metadata": {
      "exported_at": "2026-01-22 09:18:47",
      "exported_by": "Nom Utilisateur",
      "client_info": {
        "id": 1,
        "client_id": "CLI-E76E2DC4GQ",
        "name": "Entreprise ACME SARL"
      },
      "date_range": {
        "from": "2026-01-20 14:36:40",
        "to": "2026-01-22 10:00:00"
      },
      "types_included": ["appointment", "call", "note"],
      "total_by_type": {
        "appointment": 6,
        "call": 1,
        "note": 4
      }
    },
    "preview_data": [
      {
        "Date": "2026-01-22 10:00:00",
        "Type": "Appointment",
        "Titre": "RDV sans participants",
        "Résumé": "Test sans participants",
        "Utilisateur": "Bescovic rochnel Tegomo",
        "Importance": "normal",
        "Confidentialité": "public",
        "Lien détail": "http://localhost/api/v1/appointments/5"
      }
    ],
    "columns": [
      "Date",
      "Type",
      "Titre",
      "Résumé",
      "Utilisateur",
      "Importance",
      "Confidentialité",
      "Lien détail"
    ]
  }
}
```

### ✅ Test 2: Export Excel

**Requête :**
```bash
curl -X GET "http://localhost:8000/api/v1/clients/1/timeline/export?format=xlsx" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Réponse :** Même structure avec `"format": "xlsx"` et extension `.xlsx`

### ✅ Test 3: Timeline Vide

**Requête :**
```bash
curl -X GET "http://localhost:8000/api/v1/clients/3/timeline/export" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Réponse :**
```json
{
  "success": true,
  "message": "Timeline vide",
  "data": {
    "message": "Aucune interaction à exporter pour ce client",
    "records_count": 0,
    "client_id": 3,
    "client_name": "Nom du Client"
  }
}
```

### ❌ Test 4: Format Invalide

**Requête :**
```bash
curl -X GET "http://localhost:8000/api/v1/clients/1/timeline/export?format=pdf" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Réponse :**
```json
{
  "success": false,
  "message": "Erreur lors de l'export",
  "errors": {
    "format": ["Le champ format doit être l'une des valeurs suivantes : csv, xlsx."]
  }
}
```

### ❌ Test 5: Client Inexistant

**Requête :**
```bash
curl -X GET "http://localhost:8000/api/v1/clients/99999/timeline/export" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Réponse :**
```json
{
  "success": false,
  "message": "No query results for model [App\\Models\\Client] 99999"
}
```

---

## 📋 Structure des Données Exportées

### Colonnes CSV/Excel

| Colonne | Description | Exemple |
|---------|-------------|---------|
| **Date** | Date et heure de l'interaction | `2026-01-22 10:00:00` |
| **Type** | Type d'interaction | `Note`, `Call`, `Appointment`, `Email`, `Opportunity` |
| **Titre** | Titre/sujet de l'interaction | `"RDV commercial"` |
| **Résumé** | Résumé du contenu (200 char max) | `"Négociation contrat..."` |
| **Utilisateur** | Nom de l'utilisateur responsable | `"Jean Dupont"` |
| **Importance** | Niveau d'importance | `low`, `normal`, `high`, `critical` |
| **Confidentialité** | Niveau de confidentialité | `public`, `private`, `team` |
| **Lien détail** | URL vers l'API de détail | `http://api/v1/notes/123` |

### Types d'interactions exportés

| Type | Description | Lien API |
|------|-------------|----------|
| `Note` | Notes client | `/api/v1/notes/{id}` |
| `Call` | Journal d'appels | `/api/v1/calls/{id}` |
| `Appointment` | Rendez-vous | `/api/v1/appointments/{id}` |
| `Email` | Emails entrants/sortants | `/api/v1/emails/{id}` |
| `Opportunity` | Opportunités commerciales | `/api/v1/opportunities/{id}` |
| `Modification` | Journal d'audit | Pas de lien détail |

---

## 📊 Métadonnées d'Export

Chaque export inclut des métadonnées complètes :

### Informations de traçabilité
- **exported_at** : Date/heure d'export
- **exported_by** : Utilisateur ayant effectué l'export

### Informations client
- **client_info** : ID, nom, code client
- **date_range** : Plage temporelle des données
- **types_included** : Types d'interactions présents
- **total_by_type** : Comptage par type

### Informations techniques
- **filename** : Nom du fichier généré
- **download_url** : URL de téléchargement
- **records_count** : Nombre total d'enregistrements
- **columns** : Structure des colonnes

---

## 🔄 Intégration dans Applications Clientes

### JavaScript/TypeScript
```typescript
class TimelineExporter {
  private baseUrl = 'http://localhost:8000/api/v1';
  private token: string;

  constructor(token: string) {
    this.token = token;
  }

  async exportClientTimeline(clientId: number, format: 'csv' | 'xlsx' = 'csv') {
    const response = await fetch(
      `${this.baseUrl}/clients/${clientId}/timeline/export?format=${format}`,
      {
        headers: {
          'Authorization': `Bearer ${this.token}`,
          'Accept': 'application/json'
        }
      }
    );

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.message);
    }

    return result.data;
  }

  async downloadExport(exportData: any) {
    // Dans un environnement réel, download_url pointerait vers un fichier
    window.open(exportData.download_url, '_blank');
  }
}

// Utilisation
const exporter = new TimelineExporter('YOUR_TOKEN');

try {
  const exportInfo = await exporter.exportClientTimeline(1, 'xlsx');
  console.log(`Export prêt: ${exportInfo.records_count} enregistrements`);
  console.log('Métadonnées:', exportInfo.metadata);

  // Télécharger le fichier
  await exporter.downloadExport(exportInfo);
} catch (error) {
  console.error('Erreur export:', error.message);
}
```

### PHP/Laravel
```php
<?php

use Illuminate\Support\Facades\Http;

class TimelineExportService
{
    private $baseUrl = 'http://localhost:8000/api/v1';
    private $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function exportClientTimeline(int $clientId, string $format = 'csv'): array
    {
        $response = Http::withToken($this->token)
            ->get("{$this->baseUrl}/clients/{$clientId}/timeline/export", [
                'format' => $format
            ]);

        if (!$response->successful()) {
            throw new Exception('Export failed: ' . $response->body());
        }

        $result = $response->json();

        if (!$result['success']) {
            throw new Exception($result['message']);
        }

        return $result['data'];
    }

    public function getExportStatistics(array $exportData): array
    {
        return [
            'filename' => $exportData['filename'],
            'size' => $exportData['records_count'],
            'period' => $exportData['metadata']['date_range'],
            'types' => array_keys($exportData['metadata']['total_by_type'])
        ];
    }
}

// Utilisation
$service = new TimelineExportService('YOUR_TOKEN');

try {
    $export = $service->exportClientTimeline(1, 'xlsx');
    $stats = $service->getExportStatistics($export);

    echo "Export généré: {$stats['filename']}\n";
    echo "Enregistrements: {$stats['size']}\n";
    echo "Types: " . implode(', ', $stats['types']) . "\n";

} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
```

### Python
```python
import requests
from datetime import datetime

class TimelineExporter:
    def __init__(self, base_url: str, token: str):
        self.base_url = base_url.rstrip('/')
        self.headers = {
            'Authorization': f'Bearer {token}',
            'Accept': 'application/json'
        }

    def export_client_timeline(self, client_id: int, format: str = 'csv') -> dict:
        """Export timeline for a client"""
        url = f"{self.base_url}/clients/{client_id}/timeline/export"
        params = {'format': format}

        response = requests.get(url, headers=self.headers, params=params)
        response.raise_for_status()

        result = response.json()
        if not result['success']:
            raise Exception(result['message'])

        return result['data']

    def print_export_summary(self, export_data: dict):
        """Print a summary of the export"""
        metadata = export_data['metadata']

        print(f"📄 Export: {export_data['filename']}")
        print(f"📊 Enregistrements: {export_data['records_count']}")
        print(f"👤 Client: {metadata['client_info']['name']}")
        print(f"📅 Période: {metadata['date_range']['from']} → {metadata['date_range']['to']}")
        print(f"📋 Types: {', '.join(metadata['types_included'])}")
        print(f"🔗 Téléchargement: {export_data['download_url']}")

# Utilisation
exporter = TimelineExporter('http://localhost:8000/api/v1', 'YOUR_TOKEN')

try:
    export = exporter.export_client_timeline(1, 'csv')
    exporter.print_export_summary(export)

    # Afficher aperçu des données
    print("\n📋 Aperçu des données:")
    for row in export['preview_data']:
        print(f"  {row['Date']} | {row['Type']} | {row['Titre']}")

except Exception as e:
    print(f"❌ Erreur: {e}")
```

---

## 🔍 Cas d'Usage Métier

### 1. Reporting Mensuel Commercial
```bash
# Export complet pour analyse mensuelle
curl -X GET "http://localhost:8000/api/v1/clients/1/timeline/export?format=xlsx" \
  -H "Authorization: Bearer TOKEN"
```

### 2. Audit de Conformité
```bash
# Export avec traçabilité complète
curl -X GET "http://localhost:8000/api/v1/clients/1/timeline/export?format=csv" \
  -H "Authorization: Bearer TOKEN"
# Métadonnées incluent exported_by, exported_at pour audit
```

### 3. Analyse de Performance Équipe
```bash
# Données pour analyser l'activité par utilisateur
# (voir metadata.total_by_type et détails par utilisateur)
```

### 4. Backup/Archivage Client
```bash
# Export complet avant archivage client
for client_id in {1..10}; do
  curl -X GET "http://localhost:8000/api/v1/clients/$client_id/timeline/export?format=xlsx" \
    -H "Authorization: Bearer TOKEN" \
    -o "backup_client_$client_id.json"
done
```

---

## ⚠️ Gestion d'Erreurs

### Codes de réponse HTTP

| Code | Statut | Description |
|------|--------|-------------|
| `200` | ✅ Success | Export généré avec succès |
| `400` | ❌ Bad Request | Format invalide |
| `401` | ❌ Unauthorized | Token manquant/invalide |
| `403` | ❌ Forbidden | Pas d'accès au client |
| `404` | ❌ Not Found | Client inexistant |
| `422` | ❌ Validation Error | Paramètres invalides |
| `500` | ❌ Server Error | Erreur interne |

### Gestion des erreurs côté client

```typescript
async function safeExport(clientId: number, format: string) {
  try {
    const response = await fetch(`/api/v1/clients/${clientId}/timeline/export?format=${format}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(`HTTP ${response.status}: ${error.message}`);
    }

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.message);
    }

    // Timeline vide n'est pas une erreur
    if (result.data.records_count === 0) {
      console.warn('Timeline vide pour ce client');
      return null;
    }

    return result.data;

  } catch (error) {
    console.error('Erreur export:', error.message);
    // Gérer l'erreur selon le contexte
    throw error;
  }
}
```

---

## 🚀 Optimisations et Bonnes Pratiques

### Performance
- **Pagination côté serveur** : Pas de limite sur les exports (géré automatiquement)
- **Lazy loading** : Utiliser `preview_data` pour l'aperçu avant téléchargement complet
- **Cache** : Considérer la mise en cache pour les gros exports répétitifs

### Sécurité
- **Authentification** : Token Bearer obligatoire
- **Autorisations** : Respect des niveaux de confidentialité utilisateur
- **Audit** : Traçabilité complète avec exported_by et exported_at

### UX/UI
- **Progress indicator** : Utiliser metadata.records_count pour estimer la progression
- **Preview** : Afficher preview_data avant téléchargement
- **Error handling** : Messages d'erreur user-friendly

---

## 📈 Évolutions Futures

### Fonctionnalités prévues
- **Export filtré** : Par type, période, importance
- **Export streaming** : Pour très gros volumes
- **Formats additionnels** : PDF, JSON
- **Compression** : ZIP pour gros fichiers
- **Planification** : Exports automatiques périodiques

### Intégrations
- **Stockage cloud** : S3, Google Drive
- **Email** : Envoi automatique des exports
- **Webhook** : Notification de fin d'export

---

*📄 Documentation Export Timeline v1.1 - TargetDesk CRM API*
*✅ Testée et validée - 22 janvier 2026*