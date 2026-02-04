# API Gestion Documentaire TargetDesk - Guide d'intégration

## Base URL
```
http://localhost:8000/api/v1
```

## Authentification
Tous les endpoints documentaires nécessitent une authentification Bearer token.

```
Authorization: Bearer {token}
```

---

## Vue d'ensemble

Le système de gestion documentaire TargetDesk permet d'attacher, organiser et gérer des documents pour les clients et fournisseurs. Il offre :

- **Upload sécurisé** avec validation de type et taille
- **Système de versioning** automatique
- **Métadonnées** complètes (titre, description, catégorie)
- **Preview** pour PDF et images
- **Téléchargement** sécurisé
- **Statistiques** et filtrage avancé

### Types de fichiers supportés
- **Documents** : PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX
- **Images** : JPG, JPEG, PNG, GIF, BMP, SVG, WEBP
- **Texte** : TXT, CSV, RTF

### Limites
- Taille maximum : **10 MB** par fichier (configurable)
- Catégories disponibles : `contrat`, `devis`, `facture`, `autre`

---

## Endpoints Clients Documents

### 1. Lister les documents d'un client

**GET** `/clients/{client}/documents`

Récupérer la liste des documents d'un client avec statistiques.

```bash
curl -X GET "http://localhost:8000/api/v1/clients/1/documents?category=contrat&sort=name&order=asc" \
  -H "Authorization: Bearer {token}"
```

**Paramètres de requête :**
- `category` (enum, optionnel) - Filtrer par catégorie : `contrat`, `devis`, `facture`, `autre`
- `sort` (enum, optionnel) - Trier par : `name`, `date`, `type`, `category`, `size`
- `order` (enum, optionnel) - Ordre : `asc`, `desc`
- `latest_only` (boolean, optionnel) - Afficher seulement les dernières versions (défaut: true)

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Documents récupérés avec succès",
  "data": {
    "documents": [
      {
        "id": 1,
        "client_id": 1,
        "uploaded_by": 1,
        "title": "Contrat Service 2026",
        "description": "Contrat de service principal pour l'année 2026",
        "category": "contrat",
        "original_name": "contrat-2026.pdf",
        "file_path": "documents/clients/1/contrat-service-2026_ABC123_v2_2026-01-27_10-30-15.pdf",
        "mime_type": "application/pdf",
        "file_size": 1024000,
        "file_extension": "pdf",
        "version": 2,
        "document_key": "contrat-service-2026_ABC123",
        "metadata": {
          "original_size": 1024000,
          "upload_ip": "192.168.1.100",
          "user_agent": "Mozilla/5.0..."
        },
        "is_active": true,
        "last_accessed_at": null,
        "created_at": "2026-01-27T10:30:15.000000Z",
        "updated_at": "2026-01-27T10:30:15.000000Z",
        "formatted_size": "1 MB",
        "download_url": "http://localhost:8000/api/v1/clients/1/documents/1/download",
        "preview_url": "http://localhost:8000/api/v1/clients/1/documents/1/preview",
        "can_preview": true,
        "uploader": {
          "id": 1,
          "name": "John Doe"
        },
        "client": {
          "id": 1,
          "name": "Entreprise ACME",
          "client_id": "CLI-ABC123XYZ4"
        }
      }
    ],
    "statistics": {
      "total_documents": 15,
      "total_size": 52428800,
      "formatted_total_size": "50 MB",
      "by_category": {
        "contrat": 5,
        "devis": 3,
        "facture": 4,
        "autre": 3
      }
    }
  }
}
```

---

### 2. Uploader un document client

**POST** `/clients/{client}/documents`

Uploader un nouveau document pour un client avec gestion automatique du versioning.

```bash
curl -X POST http://localhost:8000/api/v1/clients/1/documents \
  -H "Authorization: Bearer {token}" \
  -F "file=@/path/to/document.pdf" \
  -F "title=Contrat Service 2026" \
  -F "description=Contrat de service principal pour l'année 2026" \
  -F "category=contrat"
```

**Champs requis :**
- `file` (file) - Fichier à uploader (max 10MB)
- `title` (string) - Titre du document (2-255 caractères)
- `category` (enum) - Catégorie : `contrat`, `devis`, `facture`, `autre`

**Champs optionnels :**
- `description` (string) - Description détaillée (max 1000 caractères)

**Réponse (201 Created) :**
```json
{
  "success": true,
  "message": "Document uploadé avec succès",
  "data": {
    "id": 1,
    "client_id": 1,
    "title": "Contrat Service 2026",
    "description": "Contrat de service principal pour l'année 2026",
    "category": "contrat",
    "original_name": "document.pdf",
    "file_path": "documents/clients/1/contrat-service-2026_ABC123_v1_2026-01-27_10-30-15.pdf",
    "mime_type": "application/pdf",
    "file_size": 1024000,
    "version": 1,
    "document_key": "contrat-service-2026_ABC123",
    "created_at": "2026-01-27T10:30:15.000000Z",
    "formatted_size": "1 MB",
    "download_url": "http://localhost:8000/api/v1/clients/1/documents/1/download",
    "uploader": {
      "id": 1,
      "name": "John Doe"
    }
  }
}
```

**Gestion du versioning :**
Si un document avec le même titre et la même catégorie existe :
- Nouvelle version créée automatiquement
- Ancienne version marquée comme inactive
- Message de réponse : `"Nouvelle version du document uploadée avec succès (v2)"`

**Erreurs possibles :**
- `422` - Fichier trop volumineux, type non supporté, champs manquants
- `500` - Erreur lors de l'upload

---

### 3. Détails d'un document client

**GET** `/clients/{client}/documents/{document}`

Récupérer les détails complets d'un document client.

```bash
curl -X GET http://localhost:8000/api/v1/clients/1/documents/1 \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Document trouvé",
  "data": {
    "id": 1,
    "client_id": 1,
    "title": "Contrat Service 2026",
    "description": "Contrat de service principal",
    "category": "contrat",
    "version": 2,
    "formatted_size": "1 MB",
    "can_preview": true,
    "download_url": "http://localhost:8000/api/v1/clients/1/documents/1/download",
    "preview_url": "http://localhost:8000/api/v1/clients/1/documents/1/preview",
    "created_at": "2026-01-27T10:30:15.000000Z",
    "uploader": {
      "id": 1,
      "name": "John Doe"
    },
    "client": {
      "id": 1,
      "name": "Entreprise ACME"
    }
  }
}
```

**Erreurs possibles :**
- `404` - Document non trouvé ou n'appartient pas au client

---

### 4. Modifier les métadonnées d'un document client

**PUT** `/clients/{client}/documents/{document}`

Mettre à jour le titre, la description ou la catégorie d'un document.

```bash
curl -X PUT http://localhost:8000/api/v1/clients/1/documents/1 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Contrat Service 2026 - Modifié",
    "description": "Description mise à jour",
    "category": "contrat"
  }'
```

**Champs modifiables :**
- `title` (string, optionnel) - Nouveau titre
- `description` (string, optionnel) - Nouvelle description
- `category` (enum, optionnel) - Nouvelle catégorie

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Document mis à jour avec succès",
  "data": {
    // Document avec nouvelles métadonnées
  }
}
```

**Erreurs possibles :**
- `404` - Document non trouvé
- `422` - Erreur de validation

---

### 5. Supprimer un document client

**DELETE** `/clients/{client}/documents/{document}`

Supprimer définitivement un document et son fichier physique.

```bash
curl -X DELETE http://localhost:8000/api/v1/clients/1/documents/1 \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Document supprimé avec succès"
}
```

**Note importante :** La suppression est définitive. Le fichier physique et l'enregistrement en base de données sont supprimés.

---

### 6. Télécharger un document client

**GET** `/clients/{client}/documents/{document}/download`

Télécharger le fichier d'un document client.

```bash
curl -X GET http://localhost:8000/api/v1/clients/1/documents/1/download \
  -H "Authorization: Bearer {token}" \
  -O
```

**Réponse (200 OK) :**
- Fichier binaire avec les en-têtes appropriés
- `Content-Type`: Type MIME du fichier
- `Content-Disposition`: Nom du fichier original

**Erreurs possibles :**
- `404` - Document non trouvé ou fichier physique manquant

---

### 7. Prévisualiser un document client

**GET** `/clients/{client}/documents/{document}/preview`

Prévisualiser un document (PDF et images uniquement).

```bash
curl -X GET http://localhost:8000/api/v1/clients/1/documents/1/preview \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
- Contenu du fichier pour affichage direct
- `Content-Type`: Type MIME pour preview
- `Cache-Control`: Headers de cache optimisés

**Erreurs possibles :**
- `404` - Document non trouvé
- `422` - Preview non disponible pour ce type de fichier

---

### 8. Lister les versions d'un document client

**GET** `/clients/{client}/documents/{document}/versions`

Récupérer toutes les versions d'un document (actives et inactives).

```bash
curl -X GET http://localhost:8000/api/v1/clients/1/documents/1/versions \
  -H "Authorization: Bearer {token}"
```

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Versions récupérées avec succès",
  "data": [
    {
      "id": 2,
      "version": 2,
      "is_active": true,
      "title": "Contrat Service 2026",
      "created_at": "2026-01-27T11:00:00.000000Z",
      "formatted_size": "1.2 MB",
      "uploader": {
        "id": 1,
        "name": "John Doe"
      }
    },
    {
      "id": 1,
      "version": 1,
      "is_active": false,
      "title": "Contrat Service 2026",
      "created_at": "2026-01-27T10:30:15.000000Z",
      "formatted_size": "1 MB",
      "uploader": {
        "id": 1,
        "name": "John Doe"
      }
    }
  ]
}
```

---

## Endpoints Fournisseurs Documents

Les endpoints pour les documents fournisseurs suivent exactement la même structure que les clients, mais utilisent le préfixe `/suppliers/{supplier}/documents/` au lieu de `/clients/{client}/documents/`.

### Endpoints disponibles :

1. **GET** `/suppliers/{supplier}/documents` - Lister les documents
2. **POST** `/suppliers/{supplier}/documents` - Uploader un document
3. **GET** `/suppliers/{supplier}/documents/{document}` - Détails d'un document
4. **PUT** `/suppliers/{supplier}/documents/{document}` - Modifier métadonnées
5. **DELETE** `/suppliers/{supplier}/documents/{document}` - Supprimer un document
6. **GET** `/suppliers/{supplier}/documents/{document}/download` - Télécharger
7. **GET** `/suppliers/{supplier}/documents/{document}/preview` - Prévisualiser
8. **GET** `/suppliers/{supplier}/documents/{document}/versions` - Lister versions

### Exemple d'usage fournisseur :

```bash
# Upload document fournisseur
curl -X POST http://localhost:8000/api/v1/suppliers/1/documents \
  -H "Authorization: Bearer {token}" \
  -F "file=@facture.pdf" \
  -F "title=Facture Janvier 2026" \
  -F "description=Facture mensuelle de prestation" \
  -F "category=facture"

# Lister documents fournisseur
curl -X GET "http://localhost:8000/api/v1/suppliers/1/documents?category=facture" \
  -H "Authorization: Bearer {token}"
```

---

## Codes d'erreur

- `200` - Succès
- `201` - Document créé avec succès
- `401` - Non authentifié (token manquant/invalide)
- `404` - Ressource non trouvée (client/fournisseur/document)
- `422` - Erreur de validation (fichier trop gros, type non supporté, etc.)
- `500` - Erreur serveur (problème d'upload, espace disque, etc.)

---

## Exemples d'intégration

### JavaScript/Fetch - Upload avec progress

```javascript
class DocumentsAPI {
  constructor(token) {
    this.baseURL = 'http://localhost:8000/api/v1';
    this.token = token;
  }

  async uploadClientDocument(clientId, formData, onProgress = null) {
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();

      // Progress tracking
      if (onProgress) {
        xhr.upload.addEventListener('progress', (e) => {
          if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            onProgress(percentComplete);
          }
        });
      }

      xhr.addEventListener('load', () => {
        if (xhr.status === 201) {
          resolve(JSON.parse(xhr.responseText));
        } else {
          reject(new Error(`Upload failed: ${xhr.status}`));
        }
      });

      xhr.addEventListener('error', () => {
        reject(new Error('Network error'));
      });

      xhr.open('POST', `${this.baseURL}/clients/${clientId}/documents`);
      xhr.setRequestHeader('Authorization', `Bearer ${this.token}`);
      xhr.send(formData);
    });
  }

  async getClientDocuments(clientId, filters = {}) {
    const params = new URLSearchParams(filters).toString();
    const url = `${this.baseURL}/clients/${clientId}/documents${params ? '?' + params : ''}`;

    const response = await fetch(url, {
      headers: {
        'Authorization': `Bearer ${this.token}`,
      }
    });

    const data = await response.json();
    if (data.success) {
      return data.data;
    } else {
      throw new Error(data.message);
    }
  }

  async downloadDocument(clientId, documentId) {
    const response = await fetch(
      `${this.baseURL}/clients/${clientId}/documents/${documentId}/download`,
      {
        headers: {
          'Authorization': `Bearer ${this.token}`,
        }
      }
    );

    if (response.ok) {
      const blob = await response.blob();
      const contentDisposition = response.headers.get('Content-Disposition');
      const filename = contentDisposition.split('filename=')[1].replace(/"/g, '');

      // Create download link
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } else {
      throw new Error('Download failed');
    }
  }
}

// Utilisation
const api = new DocumentsAPI('your-bearer-token');

// Upload avec progress
const formData = new FormData();
formData.append('file', file);
formData.append('title', 'Mon Document');
formData.append('category', 'contrat');
formData.append('description', 'Description du document');

api.uploadClientDocument(1, formData, (progress) => {
  console.log(`Upload progress: ${progress}%`);
}).then(result => {
  console.log('Document uploadé:', result.data);
}).catch(error => {
  console.error('Erreur upload:', error);
});

// Récupérer documents avec filtres
const documents = await api.getClientDocuments(1, {
  category: 'contrat',
  sort: 'date',
  order: 'desc'
});
```

### React Hook personnalisé

```javascript
import { useState, useCallback } from 'react';

export function useDocuments(token) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const api = new DocumentsAPI(token);

  const uploadDocument = useCallback(async (clientId, file, metadata, onProgress) => {
    setLoading(true);
    setError(null);

    try {
      const formData = new FormData();
      formData.append('file', file);
      Object.entries(metadata).forEach(([key, value]) => {
        formData.append(key, value);
      });

      const result = await api.uploadClientDocument(clientId, formData, onProgress);
      return result.data;
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, [api]);

  const getDocuments = useCallback(async (clientId, filters) => {
    setLoading(true);
    setError(null);

    try {
      const result = await api.getClientDocuments(clientId, filters);
      return result;
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, [api]);

  return {
    uploadDocument,
    getDocuments,
    downloadDocument: api.downloadDocument.bind(api),
    loading,
    error
  };
}

// Composant React
function DocumentManager({ clientId, token }) {
  const { uploadDocument, getDocuments, loading, error } = useDocuments(token);
  const [documents, setDocuments] = useState([]);
  const [uploadProgress, setUploadProgress] = useState(0);

  const handleFileUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    try {
      const result = await uploadDocument(
        clientId,
        file,
        {
          title: file.name,
          category: 'autre',
          description: ''
        },
        setUploadProgress
      );

      // Refresh documents list
      const updatedDocuments = await getDocuments(clientId);
      setDocuments(updatedDocuments.documents);

    } catch (error) {
      console.error('Upload error:', error);
    }
  };

  return (
    <div>
      <input type="file" onChange={handleFileUpload} disabled={loading} />
      {loading && <progress value={uploadProgress} max="100" />}
      {error && <div className="error">{error}</div>}

      <div className="documents-list">
        {documents.map(doc => (
          <div key={doc.id} className="document-item">
            <h4>{doc.title}</h4>
            <p>{doc.description}</p>
            <span className="badge">{doc.category}</span>
            <span className="size">{doc.formatted_size}</span>
          </div>
        ))}
      </div>
    </div>
  );
}
```

### PHP/Laravel Integration

```php
<?php

class DocumentService
{
    private $baseURL = 'http://localhost:8000/api/v1';
    private $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function uploadClientDocument($clientId, $file, $metadata)
    {
        $ch = curl_init();

        $postFields = [
            'file' => new CURLFile($file['tmp_name'], $file['type'], $file['name']),
            'title' => $metadata['title'],
            'category' => $metadata['category'],
            'description' => $metadata['description'] ?? ''
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseURL . "/clients/{$clientId}/documents",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
            ],
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);

        if ($httpCode !== 201) {
            throw new Exception($data['message'] ?? 'Upload failed');
        }

        return $data['data'];
    }

    public function getClientDocuments($clientId, $filters = [])
    {
        $queryString = http_build_query($filters);
        $url = $this->baseURL . "/clients/{$clientId}/documents" .
               ($queryString ? "?{$queryString}" : '');

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
            ],
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        return $data['success'] ? $data['data'] : null;
    }

    public function downloadDocument($clientId, $documentId, $savePath)
    {
        $ch = curl_init();
        $fp = fopen($savePath, 'w+');

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseURL . "/clients/{$clientId}/documents/{$documentId}/download",
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
            ],
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        return $httpCode === 200;
    }
}

// Utilisation avec Laravel
class DocumentController extends Controller
{
    public function store(Request $request, $clientId)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB
            'title' => 'required|string|max:255',
            'category' => 'required|in:contrat,devis,facture,autre',
            'description' => 'nullable|string|max:1000'
        ]);

        $documentService = new DocumentService(auth()->user()->api_token);

        try {
            $result = $documentService->uploadClientDocument($clientId, $request->file('file'), [
                'title' => $request->title,
                'category' => $request->category,
                'description' => $request->description
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploadé avec succès',
                'data' => $result
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
```

### Intégration Angular/TypeScript

#### Service Angular complet avec gestion des erreurs

```typescript
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpParams, HttpResponse, HttpEventType } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { map, catchError } from 'rxjs/operators';

export interface DocumentUploadProgress {
  progress: number;
  document?: any;
}

@Injectable({
  providedIn: 'root'
})
export class DocumentService {
  private readonly baseURL = 'http://localhost:8000/api/v1';

  constructor(private http: HttpClient) {}

  private getHeaders(): HttpHeaders {
    const token = localStorage.getItem('auth_token'); // ou votre méthode de récupération du token
    return new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });
  }

  /**
   * Upload d'un document avec suivi du progrès
   * IMPORTANT: Ne pas définir Content-Type pour les uploads de fichiers
   */
  uploadClientDocument(
    clientId: number,
    file: File,
    metadata: { title: string; category: string; description?: string }
  ): Observable<DocumentUploadProgress> {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('title', metadata.title);
    formData.append('category', metadata.category);
    if (metadata.description) {
      formData.append('description', metadata.description);
    }

    return this.http.post<any>(
      `${this.baseURL}/clients/${clientId}/documents`,
      formData,
      {
        headers: this.getHeaders().delete('Content-Type'), // Laisser le navigateur gérer Content-Type
        reportProgress: true,
        observe: 'events'
      }
    ).pipe(
      map(event => {
        if (event.type === HttpEventType.UploadProgress) {
          const progress = Math.round(100 * event.loaded / (event.total || 1));
          return { progress };
        } else if (event.type === HttpEventType.Response) {
          return { progress: 100, document: event.body.data };
        }
        return { progress: 0 };
      }),
      catchError(this.handleError)
    );
  }

  /**
   * Récupérer la liste des documents
   */
  getClientDocuments(
    clientId: number,
    filters: { category?: string; sort?: string; order?: string; latest_only?: boolean } = {}
  ): Observable<any> {
    let params = new HttpParams();
    Object.keys(filters).forEach(key => {
      if (filters[key] !== undefined && filters[key] !== null) {
        params = params.set(key, filters[key].toString());
      }
    });

    return this.http.get<any>(
      `${this.baseURL}/clients/${clientId}/documents`,
      {
        headers: this.getHeaders(),
        params
      }
    ).pipe(
      map(response => response.data),
      catchError(this.handleError)
    );
  }

  /**
   * Prévisualisation d'un document
   * CRITIQUE: Headers spéciaux pour éviter les problèmes de cache
   */
  previewDocument(clientId: number, documentId: number): Observable<Blob> {
    // Cache-busting pour éviter les anciens headers JSON
    const cacheBreaker = Date.now();

    return this.http.get(
      `${this.baseURL}/clients/${clientId}/documents/${documentId}/preview?v=${cacheBreaker}`,
      {
        headers: new HttpHeaders({
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Accept': '*/*', // Accepter tout type de contenu
          'Cache-Control': 'no-cache',
          'Pragma': 'no-cache'
        }),
        responseType: 'blob' // CRITIQUE: Spécifier que nous attendons du binaire
      }
    ).pipe(
      catchError(this.handleError)
    );
  }

  /**
   * Téléchargement d'un document
   */
  downloadDocument(clientId: number, documentId: number, filename?: string): Observable<void> {
    return this.http.get(
      `${this.baseURL}/clients/${clientId}/documents/${documentId}/download`,
      {
        headers: new HttpHeaders({
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Accept': '*/*'
        }),
        responseType: 'blob'
      }
    ).pipe(
      map(blob => {
        // Créer le lien de téléchargement
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename || 'document';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
      }),
      catchError(this.handleError)
    );
  }

  /**
   * Afficher un PDF dans un iframe ou nouvel onglet
   */
  displayDocument(clientId: number, documentId: number, target: 'iframe' | 'new-tab' = 'iframe'): Observable<string> {
    return this.previewDocument(clientId, documentId).pipe(
      map(blob => {
        const url = window.URL.createObjectURL(blob);

        if (target === 'new-tab') {
          window.open(url, '_blank');
        }

        return url; // Retourner l'URL pour utilisation dans un iframe
      })
    );
  }

  private handleError(error: any): Observable<never> {
    console.error('Document service error:', error);
    let errorMessage = 'Une erreur est survenue';

    if (error.error && error.error.message) {
      errorMessage = error.error.message;
    } else if (error.message) {
      errorMessage = error.message;
    }

    return throwError(errorMessage);
  }
}
```

#### Composant Angular pour preview/téléchargement

```typescript
import { Component, Input, OnInit } from '@angular/core';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { DocumentService } from './document.service';

@Component({
  selector: 'app-document-preview',
  template: `
    <div class="document-preview">
      <div class="document-actions">
        <button (click)="previewDocument()" [disabled]="loading" class="btn-preview">
          <i class="icon-eye"></i> Aperçu
        </button>
        <button (click)="downloadDocument()" [disabled]="loading" class="btn-download">
          <i class="icon-download"></i> Télécharger
        </button>
        <button (click)="openInNewTab()" [disabled]="loading" class="btn-new-tab">
          <i class="icon-external"></i> Nouvel onglet
        </button>
      </div>

      <!-- Zone de prévisualisation -->
      <div class="preview-container" *ngIf="previewUrl">
        <iframe
          [src]="previewUrl"
          width="100%"
          height="600px"
          frameborder="0">
        </iframe>
      </div>

      <!-- Loading state -->
      <div *ngIf="loading" class="loading">
        <div class="spinner"></div>
        Chargement...
      </div>

      <!-- Error state -->
      <div *ngIf="error" class="error">
        <p>{{ error }}</p>
        <button (click)="retry()" class="btn-retry">Réessayer</button>
      </div>
    </div>
  `,
  styleUrls: ['./document-preview.component.scss']
})
export class DocumentPreviewComponent implements OnInit {
  @Input() clientId!: number;
  @Input() documentId!: number;
  @Input() document: any;

  previewUrl: SafeResourceUrl | null = null;
  loading = false;
  error: string | null = null;

  constructor(
    private documentService: DocumentService,
    private sanitizer: DomSanitizer
  ) {}

  ngOnInit(): void {
    // Auto-preview pour les PDFs
    if (this.document?.can_preview) {
      this.previewDocument();
    }
  }

  previewDocument(): void {
    this.loading = true;
    this.error = null;

    this.documentService.displayDocument(this.clientId, this.documentId, 'iframe')
      .subscribe({
        next: (url) => {
          this.previewUrl = this.sanitizer.bypassSecurityTrustResourceUrl(url);
          this.loading = false;
        },
        error: (error) => {
          this.error = `Erreur lors de la prévisualisation: ${error}`;
          this.loading = false;
        }
      });
  }

  downloadDocument(): void {
    this.loading = true;
    this.error = null;

    const filename = this.document?.original_name || 'document';

    this.documentService.downloadDocument(this.clientId, this.documentId, filename)
      .subscribe({
        next: () => {
          this.loading = false;
        },
        error: (error) => {
          this.error = `Erreur lors du téléchargement: ${error}`;
          this.loading = false;
        }
      });
  }

  openInNewTab(): void {
    this.documentService.displayDocument(this.clientId, this.documentId, 'new-tab')
      .subscribe({
        error: (error) => {
          this.error = `Erreur lors de l'ouverture: ${error}`;
        }
      });
  }

  retry(): void {
    this.error = null;
    this.previewDocument();
  }

  ngOnDestroy(): void {
    // Nettoyer les URLs blob pour éviter les fuites mémoire
    if (this.previewUrl) {
      const url = this.previewUrl.toString();
      if (url.startsWith('blob:')) {
        window.URL.revokeObjectURL(url);
      }
    }
  }
}
```

#### Module Angular avec HttpClient

```typescript
import { NgModule } from '@angular/core';
import { HttpClientModule, HTTP_INTERCEPTORS } from '@angular/common/http';
import { DocumentService } from './document.service';

// Intercepteur pour les headers d'authentification
@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  intercept(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    const token = localStorage.getItem('auth_token');

    if (token && req.url.includes('/api/v1/')) {
      const authReq = req.clone({
        setHeaders: {
          Authorization: `Bearer ${token}`
        }
      });
      return next.handle(authReq);
    }

    return next.handle(req);
  }
}

@NgModule({
  imports: [HttpClientModule],
  providers: [
    DocumentService,
    {
      provide: HTTP_INTERCEPTORS,
      useClass: AuthInterceptor,
      multi: true
    }
  ]
})
export class DocumentModule {}
```

#### Points critiques pour Angular

##### ⚠️ **Problèmes courants et solutions :**

1. **Headers Content-Type automatiques :**
```typescript
// ❌ MAUVAIS - Ne pas définir Content-Type pour les uploads
const headers = new HttpHeaders({
  'Content-Type': 'multipart/form-data' // SUPPRIME CETTE LIGNE
});

// ✅ CORRECT - Laisser Angular gérer automatiquement
const headers = new HttpHeaders({
  'Authorization': 'Bearer token'
  // Pas de Content-Type
});
```

2. **Cache-busting pour preview/download :**
```typescript
// ✅ Toujours ajouter un paramètre unique pour éviter le cache
const cacheBreaker = Date.now();
const url = `${baseUrl}/preview?v=${cacheBreaker}`;
```

3. **Response type pour fichiers binaires :**
```typescript
// ✅ OBLIGATOIRE pour les endpoints de fichiers
this.http.get(url, { responseType: 'blob' })
```

4. **Headers Accept appropriés :**
```typescript
// ✅ Pour les fichiers, utiliser Accept: */*
headers: new HttpHeaders({
  'Accept': '*/*',  // Pas 'application/json'
  'Authorization': 'Bearer token'
})
```

---

## Fonctionnalités avancées

### Système de versioning
- **Détection automatique** : Même titre + même catégorie = nouvelle version
- **Gestion de l'état** : Version précédente marquée inactive
- **Conservation historique** : Toutes les versions sont conservées
- **Accès aux versions** : Endpoint dédié pour lister toutes les versions

### Sécurité et validation
- **Types MIME vérifiés** : Double validation (extension + MIME type)
- **Taille limitée** : 10MB par défaut, configurable
- **Authentification requise** : Tous les endpoints protégés
- **Validation d'appartenance** : Documents liés au bon client/fournisseur

### Métadonnées et tracking
- **Informations d'upload** : IP, User-Agent, utilisateur
- **Accès tracking** : Dernière date de consultation
- **Formatage automatique** : Tailles en unités lisibles
- **URLs pré-générées** : Download et preview URLs

### Performance et stockage
- **Organisation fichiers** : Arborescence par client/fournisseur
- **Noms uniques** : Évite les conflits de fichiers
- **Headers cache** : Optimisation preview/download
- **Compression** : Support des formats optimisés

---

## Cas d'usage typiques

### 1. Interface de drag & drop

```javascript
// Composant de drop zone
function DocumentDropZone({ clientId, onUploadComplete }) {
  const [dragOver, setDragOver] = useState(false);
  const { uploadDocument } = useDocuments(token);

  const handleDrop = async (e) => {
    e.preventDefault();
    setDragOver(false);

    const files = Array.from(e.dataTransfer.files);

    for (const file of files) {
      try {
        await uploadDocument(clientId, file, {
          title: file.name.replace(/\.[^/.]+$/, ""), // Remove extension
          category: 'autre',
          description: `Document uploadé via drag & drop`
        });
      } catch (error) {
        console.error(`Error uploading ${file.name}:`, error);
      }
    }

    onUploadComplete();
  };

  return (
    <div
      className={`drop-zone ${dragOver ? 'drag-over' : ''}`}
      onDragOver={(e) => { e.preventDefault(); setDragOver(true); }}
      onDragLeave={() => setDragOver(false)}
      onDrop={handleDrop}
    >
      <p>Glissez vos documents ici ou cliquez pour sélectionner</p>
    </div>
  );
}
```

### 2. Gestionnaire de versions

```javascript
function DocumentVersions({ clientId, documentId }) {
  const [versions, setVersions] = useState([]);
  const api = new DocumentsAPI(token);

  useEffect(() => {
    api.getClientDocumentVersions(clientId, documentId)
       .then(data => setVersions(data.data));
  }, [clientId, documentId]);

  return (
    <div className="versions-list">
      <h3>Historique des versions</h3>
      {versions.map(version => (
        <div key={version.id} className={`version ${version.is_active ? 'active' : ''}`}>
          <span className="version-number">v{version.version}</span>
          <span className="date">{new Date(version.created_at).toLocaleDateString()}</span>
          <span className="size">{version.formatted_size}</span>
          <span className="uploader">{version.uploader.name}</span>
          {version.is_active && <span className="badge">Actuelle</span>}
          <button onClick={() => api.downloadDocument(clientId, version.id)}>
            Télécharger
          </button>
        </div>
      ))}
    </div>
  );
}
```

### 3. Statistiques et filtres

```javascript
function DocumentDashboard({ clientId }) {
  const [documents, setDocuments] = useState([]);
  const [statistics, setStatistics] = useState({});
  const [filters, setFilters] = useState({});
  const api = new DocumentsAPI(token);

  const loadDocuments = useCallback(async () => {
    const data = await api.getClientDocuments(clientId, filters);
    setDocuments(data.documents);
    setStatistics(data.statistics);
  }, [clientId, filters]);

  useEffect(() => {
    loadDocuments();
  }, [loadDocuments]);

  return (
    <div className="document-dashboard">
      <div className="statistics">
        <div className="stat-card">
          <h3>{statistics.total_documents}</h3>
          <p>Documents</p>
        </div>
        <div className="stat-card">
          <h3>{statistics.formatted_total_size}</h3>
          <p>Espace utilisé</p>
        </div>
        <div className="category-breakdown">
          {Object.entries(statistics.by_category || {}).map(([category, count]) => (
            <div key={category} className="category-stat">
              <span className={`badge ${category}`}>{category}</span>
              <span className="count">{count}</span>
            </div>
          ))}
        </div>
      </div>

      <div className="filters">
        <select onChange={(e) => setFilters({...filters, category: e.target.value})}>
          <option value="">Toutes catégories</option>
          <option value="contrat">Contrats</option>
          <option value="devis">Devis</option>
          <option value="facture">Factures</option>
          <option value="autre">Autres</option>
        </select>

        <select onChange={(e) => setFilters({...filters, sort: e.target.value})}>
          <option value="date">Trier par date</option>
          <option value="name">Trier par nom</option>
          <option value="size">Trier par taille</option>
        </select>
      </div>

      <div className="documents-grid">
        {documents.map(doc => (
          <DocumentCard key={doc.id} document={doc} />
        ))}
      </div>
    </div>
  );
}
```

---

## Documentation Swagger

La documentation interactive complète est disponible sur :
**http://localhost:8000/api/documentation**

Cette interface permet de :
- Tester tous les endpoints documentaires directement
- Voir les schémas de données détaillés
- Uploader des fichiers de test
- Gérer l'authentification Bearer
- Visualiser les réponses d'exemple avec métadonnées complètes

---

## Support et maintenance

### Logs et debugging
- Logs d'upload disponibles dans `storage/logs/laravel.log`
- Erreurs de validation avec messages en français
- Tracking des accès aux documents pour audit

### Stockage et performance
- Fichiers organisés dans `storage/app/documents/`
- Arborescence : `documents/{clients|suppliers}/{id}/`
- Noms de fichiers avec horodatage pour éviter conflits
- Support des liens symboliques pour optimisation

### Monitoring recommandé
- Surveillance espace disque pour uploads
- Monitoring des temps de réponse download/preview
- Alertes sur échecs d'upload récurrents
- Backup régulier du dossier documents

### Configuration avancée
```php
// config/filesystems.php
'documents' => [
    'driver' => 'local',
    'root' => storage_path('app/documents'),
    'url' => env('APP_URL').'/storage/documents',
    'visibility' => 'private',
],

// .env
DOCUMENTS_MAX_SIZE=10240  # 10MB en KB
DOCUMENTS_STORAGE_PATH=documents
DOCUMENTS_PREVIEW_CACHE_TTL=3600  # 1 heure
```

La gestion documentaire TargetDesk offre une solution complète et sécurisée pour organiser tous les fichiers liés aux clients et fournisseurs avec un système de versioning avancé.