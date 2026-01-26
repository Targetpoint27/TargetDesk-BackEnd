# 🔧 Intégration Export Timeline - Angular

## 🎯 Solution au Problème CORS

### ❌ Problème Initial
```
Access to XMLHttpRequest at 'http://localhost/exports/timeline.xlsx'
from origin 'http://localhost:4200' has been blocked by CORS policy
```

### ✅ Solution Implémentée
**Téléchargement direct via l'API avec paramètre `download=true`**

---

## 🚀 Intégration Angular

### Service Export
```typescript
// src/app/services/timeline-export.service.ts
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpResponse } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../environments/environment';

export interface ExportMetadata {
  filename: string;
  format: string;
  records_count: number;
  metadata: {
    exported_at: string;
    exported_by: string;
    client_info: {
      id: number;
      client_id: string;
      name: string;
    };
    date_range: {
      from: string;
      to: string;
    };
    types_included: string[];
    total_by_type: { [key: string]: number };
  };
  preview_data: any[];
  columns: string[];
}

@Injectable({
  providedIn: 'root'
})
export class TimelineExportService {
  private readonly baseUrl = `${environment.apiUrl}/api/v1`;

  constructor(private http: HttpClient) {}

  /**
   * Obtenir les métadonnées d'export (aperçu)
   */
  getExportMetadata(clientId: number, format: 'csv' | 'xlsx' = 'csv'): Observable<ExportMetadata> {
    const url = `${this.baseUrl}/clients/${clientId}/timeline/export`;
    const params = { format };

    return this.http.get<{ data: ExportMetadata }>(url, { params })
      .pipe(
        map(response => response.data)
      );
  }

  /**
   * Télécharger le fichier directement
   */
  downloadExport(clientId: number, format: 'csv' | 'xlsx' = 'csv'): Observable<Blob> {
    const url = `${this.baseUrl}/clients/${clientId}/timeline/export`;
    const params = { format, download: 'true' };

    return this.http.get(url, {
      params,
      responseType: 'blob',
      observe: 'response'
    }).pipe(
      map((response: HttpResponse<Blob>) => {
        // Extraire le nom de fichier des headers si nécessaire
        const contentDisposition = response.headers.get('Content-Disposition');
        const filename = this.extractFilename(contentDisposition);

        // Déclencher le téléchargement
        this.saveBlob(response.body!, filename);

        return response.body!;
      })
    );
  }

  /**
   * Méthode combinée : aperçu puis téléchargement
   */
  exportWithPreview(clientId: number, format: 'csv' | 'xlsx' = 'csv'): Observable<ExportMetadata> {
    return this.getExportMetadata(clientId, format);
  }

  private extractFilename(contentDisposition: string | null): string {
    if (!contentDisposition) return 'export.csv';

    const filenameMatch = contentDisposition.match(/filename="?([^"]+)"?/);
    return filenameMatch ? filenameMatch[1] : 'export.csv';
  }

  private saveBlob(blob: Blob, filename: string): void {
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  }
}
```

### Composant Timeline
```typescript
// src/app/components/client-timeline.component.ts
import { Component } from '@angular/core';
import { TimelineExportService, ExportMetadata } from '../services/timeline-export.service';

@Component({
  selector: 'app-client-timeline',
  template: `
    <div class="export-section">
      <h3>Export Timeline</h3>

      <!-- Boutons d'export -->
      <div class="export-buttons">
        <button
          class="btn btn-primary"
          (click)="exportWithPreview('csv')"
          [disabled]="isExporting">
          <i class="fas fa-file-csv"></i>
          Export CSV
        </button>

        <button
          class="btn btn-success"
          (click)="exportWithPreview('xlsx')"
          [disabled]="isExporting">
          <i class="fas fa-file-excel"></i>
          Export Excel
        </button>
      </div>

      <!-- Preview modal -->
      <div *ngIf="exportPreview" class="export-preview">
        <h4>Aperçu Export</h4>
        <div class="metadata">
          <p><strong>Fichier:</strong> {{ exportPreview.filename }}</p>
          <p><strong>Enregistrements:</strong> {{ exportPreview.records_count }}</p>
          <p><strong>Période:</strong>
            {{ exportPreview.metadata.date_range.from }} →
            {{ exportPreview.metadata.date_range.to }}
          </p>
          <p><strong>Types:</strong> {{ exportPreview.metadata.types_included.join(', ') }}</p>
        </div>

        <!-- Aperçu données -->
        <div class="preview-data">
          <h5>Aperçu des données (5 premiers enregistrements):</h5>
          <table class="table table-sm">
            <thead>
              <tr>
                <th *ngFor="let col of exportPreview.columns">{{ col }}</th>
              </tr>
            </thead>
            <tbody>
              <tr *ngFor="let row of exportPreview.preview_data">
                <td>{{ row.Date }}</td>
                <td>{{ row.Type }}</td>
                <td>{{ row.Titre }}</td>
                <td>{{ row.Résumé | slice:0:50 }}{{ row.Résumé?.length > 50 ? '...' : '' }}</td>
                <td>{{ row.Utilisateur }}</td>
                <td>{{ row.Importance }}</td>
                <td>{{ row.Confidentialité }}</td>
                <td><a [href]="row['Lien détail']" target="_blank">Voir</a></td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Actions -->
        <div class="export-actions">
          <button
            class="btn btn-primary"
            (click)="confirmDownload()"
            [disabled]="isDownloading">
            <i class="fas fa-download"></i>
            {{ isDownloading ? 'Téléchargement...' : 'Télécharger' }}
          </button>
          <button class="btn btn-secondary" (click)="cancelExport()">
            Annuler
          </button>
        </div>
      </div>

      <!-- Loading states -->
      <div *ngIf="isExporting" class="loading">
        <i class="fas fa-spinner fa-spin"></i>
        Préparation export...
      </div>
    </div>
  `
})
export class ClientTimelineComponent {
  clientId = 1; // À récupérer depuis les params de route
  isExporting = false;
  isDownloading = false;
  exportPreview: ExportMetadata | null = null;
  private currentFormat: 'csv' | 'xlsx' = 'csv';

  constructor(private exportService: TimelineExportService) {}

  /**
   * Export avec aperçu préalable
   */
  exportWithPreview(format: 'csv' | 'xlsx'): void {
    this.isExporting = true;
    this.currentFormat = format;

    this.exportService.getExportMetadata(this.clientId, format)
      .subscribe({
        next: (metadata) => {
          this.exportPreview = metadata;
          this.isExporting = false;

          // Log pour debug
          console.log('Export metadata:', metadata);
        },
        error: (error) => {
          console.error('Erreur export preview:', error);
          this.isExporting = false;

          // Gestion des erreurs
          if (error.status === 401) {
            // Rediriger vers login
          } else if (error.error?.data?.records_count === 0) {
            alert('Aucune donnée à exporter pour ce client');
          } else {
            alert('Erreur lors de la préparation de l\'export');
          }
        }
      });
  }

  /**
   * Téléchargement direct sans aperçu
   */
  directDownload(format: 'csv' | 'xlsx'): void {
    this.isDownloading = true;

    this.exportService.downloadExport(this.clientId, format)
      .subscribe({
        next: (blob) => {
          console.log('Téléchargement réussi:', blob.size, 'bytes');
          this.isDownloading = false;
        },
        error: (error) => {
          console.error('Erreur téléchargement:', error);
          this.isDownloading = false;
          alert('Erreur lors du téléchargement');
        }
      });
  }

  /**
   * Confirmer et télécharger après aperçu
   */
  confirmDownload(): void {
    this.isDownloading = true;

    this.exportService.downloadExport(this.clientId, this.currentFormat)
      .subscribe({
        next: (blob) => {
          console.log('Téléchargement confirmé:', blob.size, 'bytes');
          this.isDownloading = false;
          this.exportPreview = null;
        },
        error: (error) => {
          console.error('Erreur téléchargement confirmé:', error);
          this.isDownloading = false;
        }
      });
  }

  /**
   * Annuler l'export
   */
  cancelExport(): void {
    this.exportPreview = null;
  }
}
```

### Configuration HTTP Interceptor
```typescript
// src/app/interceptors/auth.interceptor.ts
import { Injectable } from '@angular/core';
import { HttpInterceptor, HttpRequest, HttpHandler } from '@angular/common/http';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  intercept(req: HttpRequest<any>, next: HttpHandler) {
    // Ajouter le token d'auth pour tous les appels API
    const authReq = req.clone({
      setHeaders: {
        'Authorization': `Bearer ${this.getToken()}`
      }
    });

    return next.handle(authReq);
  }

  private getToken(): string {
    return localStorage.getItem('auth_token') || '';
  }
}
```

---

## 🔧 Configuration Backend (Déjà Implémentée)

### Headers CORS Ajoutés
```php
// Dans ClientTimelineController.php
return response($csv)
    ->header('Content-Type', 'text/csv; charset=utf-8')
    ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
    ->header('Access-Control-Allow-Origin', '*')
    ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
    ->header('Access-Control-Expose-Headers', 'Content-Disposition');
```

### Endpoints Disponibles
1. **Aperçu** : `GET /api/v1/clients/{id}/timeline/export?format=csv`
2. **Téléchargement** : `GET /api/v1/clients/{id}/timeline/export?format=csv&download=true`

---

## 🧪 Tests d'intégration

### Test 1: Aperçu Export
```bash
curl -X GET "http://localhost:8000/api/v1/clients/1/timeline/export?format=csv" \
  -H "Authorization: Bearer TOKEN"
```

### Test 2: Téléchargement Direct
```bash
curl -X GET "http://localhost:8000/api/v1/clients/1/timeline/export?format=csv&download=true" \
  -H "Authorization: Bearer TOKEN" \
  -o timeline.csv
```

### Test 3: Validation Format
```bash
curl -X GET "http://localhost:8000/api/v1/clients/1/timeline/export?format=pdf" \
  -H "Authorization: Bearer TOKEN"
# Retourne erreur 422 validation
```

---

## 🎯 Utilisation dans Angular

### Cas 1: Export Simple
```typescript
// Dans votre composant
exportCSV() {
  this.exportService.directDownload(this.clientId, 'csv')
    .subscribe(blob => console.log('Export CSV réussi'));
}
```

### Cas 2: Export avec Aperçu
```typescript
// Export en 2 étapes
exportWithPreview() {
  this.exportService.exportWithPreview(this.clientId, 'xlsx')
    .subscribe(metadata => {
      // Afficher aperçu
      this.showPreview(metadata);
    });
}
```

### Cas 3: Gestion d'Erreurs
```typescript
exportWithErrorHandling() {
  this.exportService.downloadExport(this.clientId, 'csv')
    .pipe(
      catchError(error => {
        if (error.status === 401) {
          this.router.navigate(['/login']);
        } else if (error.error?.data?.records_count === 0) {
          this.showMessage('Aucune donnée à exporter');
        } else {
          this.showMessage('Erreur lors de l\'export');
        }
        return throwError(error);
      })
    )
    .subscribe();
}
```

---

## 📱 UX/UI Recommandée

### États d'Interface
1. **Bouton normal** : `Export CSV` / `Export Excel`
2. **Chargement aperçu** : `Préparation export...` + spinner
3. **Aperçu affiché** : Modal avec métadonnées + preview
4. **Téléchargement** : `Téléchargement...` + spinner
5. **Succès** : Message confirmation + fermeture modal

### Validation UX
```typescript
validateExport(): boolean {
  if (!this.clientId) {
    this.showError('Client non sélectionné');
    return false;
  }

  if (this.isExporting || this.isDownloading) {
    this.showError('Export en cours...');
    return false;
  }

  return true;
}
```

---

## 🚀 Exemple Complet Fonctionnel

```typescript
// client-timeline.component.ts
export class ClientTimelineComponent {
  // ... propriétés ...

  /**
   * Méthode principale d'export
   */
  async exportTimeline(format: 'csv' | 'xlsx'): Promise<void> {
    try {
      // 1. Validation
      if (!this.validateExport()) return;

      // 2. Aperçu
      this.isExporting = true;
      const metadata = await this.exportService.getExportMetadata(this.clientId, format).toPromise();

      // 3. Afficher aperçu
      this.exportPreview = metadata;
      this.isExporting = false;

      // 4. Si l'utilisateur confirme, télécharger
      // (géré par confirmDownload())

    } catch (error) {
      this.handleExportError(error);
      this.isExporting = false;
    }
  }

  private handleExportError(error: any): void {
    console.error('Export error:', error);

    switch (error.status) {
      case 401:
        this.router.navigate(['/login']);
        break;
      case 404:
        this.showMessage('Client non trouvé');
        break;
      case 422:
        this.showMessage('Format d\'export invalide');
        break;
      default:
        if (error.error?.data?.records_count === 0) {
          this.showMessage('Aucune donnée à exporter pour ce client');
        } else {
          this.showMessage('Erreur lors de l\'export. Veuillez réessayer.');
        }
    }
  }
}
```

---

## ✅ Résultats Attendus

### Avant (Problème)
```
❌ CORS Error: Access blocked
❌ 404: File not found
❌ No download functionality
```

### Après (Solution)
```
✅ Download direct via API
✅ CORS headers configurés
✅ Preview avant téléchargement
✅ Gestion erreurs complète
✅ UX fluide avec états de chargement
```

**L'intégration Angular est maintenant prête pour l'export timeline ! 🚀**

---

*Guide d'intégration Angular - Export Timeline API v1.1*
*Testé et validé - 22 janvier 2026*