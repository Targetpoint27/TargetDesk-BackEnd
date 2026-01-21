# Intégration Angular - Import/Export Clients TargetDesk

Guide complet pour intégrer les endpoints d'import et d'export de clients dans une application Angular.

## Table des matières

- [Configuration initiale](#configuration-initiale)
- [Service Angular](#service-angular)
- [Composants](#composants)
- [Templates HTML](#templates-html)
- [Styles CSS](#styles-css)
- [Gestion des erreurs](#gestion-des-erreurs)
- [Tests unitaires](#tests-unitaires)

---

## Configuration initiale

### 1. Dépendances requises

```bash
npm install @angular/common @angular/forms rxjs
```

### 2. Configuration de l'environnement

```typescript
// src/environments/environment.ts
export const environment = {
  production: false,
  apiUrl: 'http://localhost:8000/api/v1',
  apiToken: 'Bearer 56|KaWGw5xvgINs8rwPl1pkGRB1Wwj8nXJRSvZnxhrQ'
};
```

### 3. Interceptor HTTP pour l'authentification

```typescript
// src/app/interceptors/auth.interceptor.ts
import { Injectable } from '@angular/core';
import { HttpInterceptor, HttpRequest, HttpHandler } from '@angular/common/http';
import { environment } from '../../environments/environment';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  intercept(req: HttpRequest<any>, next: HttpHandler) {
    const authReq = req.clone({
      headers: req.headers.set('Authorization', environment.apiToken)
    });
    return next.handle(authReq);
  }
}
```

```typescript
// src/app/app.module.ts
import { HTTP_INTERCEPTORS } from '@angular/common/http';

@NgModule({
  providers: [
    {
      provide: HTTP_INTERCEPTORS,
      useClass: AuthInterceptor,
      multi: true
    }
  ]
})
export class AppModule { }
```

---

## Service Angular

```typescript
// src/app/services/client-import-export.service.ts
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

export interface ClientImportPreview {
  success: boolean;
  message: string;
  data: {
    preview: ClientPreviewItem[];
    stats: ImportStats;
    errors: ValidationError[];
    duplicates: DuplicateItem[];
  };
}

export interface ClientPreviewItem {
  row_number: number;
  data: ClientData;
  validation: {
    is_valid: boolean;
    errors: any[];
  };
  duplicate: {
    is_duplicate: boolean;
    conflicts: DuplicateConflict[];
  };
}

export interface ImportStats {
  total_rows: number;
  valid_rows: number;
  invalid_rows: number;
  duplicates_found: number;
}

export interface ValidationError {
  row_number: number;
  data: ClientData;
  validation: {
    is_valid: boolean;
    errors: any;
  };
}

export interface DuplicateItem {
  row_number: number;
  data: ClientData;
  duplicate: {
    is_duplicate: boolean;
    conflicts: DuplicateConflict[];
  };
}

export interface DuplicateConflict {
  field: string;
  value: string;
  existing_client: {
    id: number;
    client_id: string;
    name: string;
    email?: string;
    siret?: string;
  };
}

export interface ClientData {
  name: string;
  type: 'particulier' | 'entreprise';
  email: string;
  phone?: string;
  address?: string;
  siret?: string;
  sector?: string;
  website?: string;
  notes?: string;
}

export interface ImportResult {
  success: boolean;
  message: string;
  data: {
    report: {
      total_rows: number;
      imported: number;
      updated: number;
      ignored: number;
      errors: any[];
      warnings: string[];
    };
  };
}

export interface ExportOptions {
  format?: 'csv' | 'excel';
  columns?: string[];
  filters?: {
    type?: 'particulier' | 'entreprise';
    is_active?: boolean;
    search?: string;
  };
  client_ids?: number[];
  limit?: number;
}

@Injectable({
  providedIn: 'root'
})
export class ClientImportExportService {
  private readonly baseUrl = `${environment.apiUrl}/clients`;

  constructor(private http: HttpClient) {}

  // Télécharger les templates
  downloadCsvTemplate(): Observable<Blob> {
    return this.http.get(`${this.baseUrl}/export/template`, {
      responseType: 'blob'
    });
  }

  downloadExcelTemplate(): Observable<Blob> {
    return this.http.get(`${this.baseUrl}/export/template/excel`, {
      responseType: 'blob'
    });
  }

  // Prévisualisation d'import
  previewImport(file: File, mapping?: { [key: string]: string }): Observable<ClientImportPreview> {
    const formData = new FormData();
    formData.append('file', file);

    if (mapping) {
      Object.entries(mapping).forEach(([key, value]) => {
        formData.append(`mapping[${key}]`, value);
      });
    }

    return this.http.post<ClientImportPreview>(`${this.baseUrl}/import/preview`, formData);
  }

  // Import définitif
  importClients(
    file: File,
    mapping: { [key: string]: string },
    duplicateAction: 'ignore' | 'replace' | 'update' = 'ignore'
  ): Observable<ImportResult> {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('duplicate_action', duplicateAction);

    Object.entries(mapping).forEach(([key, value]) => {
      formData.append(`mapping[${key}]`, value);
    });

    return this.http.post<ImportResult>(`${this.baseUrl}/import`, formData);
  }

  // Export de clients
  exportClients(options: ExportOptions = {}): Observable<Blob> {
    const defaultOptions: ExportOptions = {
      format: 'csv',
      columns: ['client_id', 'name', 'type', 'email', 'phone'],
      limit: 10000,
      ...options
    };

    return this.http.post(`${this.baseUrl}/export`, defaultOptions, {
      responseType: 'blob'
    });
  }

  // Utilitaires pour téléchargement de fichiers
  downloadFile(blob: Blob, filename: string): void {
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.click();
    window.URL.revokeObjectURL(url);
  }

  // Génération automatique de noms de fichiers
  generateFilename(prefix: string, format: 'csv' | 'excel' | 'xlsx' = 'csv'): string {
    const date = new Date().toISOString().split('T')[0];
    const extension = format === 'excel' || format === 'xlsx' ? 'xlsx' : 'csv';
    return `${prefix}_${date}.${extension}`;
  }
}
```

---

## Composants

### 1. Composant d'import

```typescript
// src/app/components/client-import/client-import.component.ts
import { Component, ViewChild, ElementRef } from '@angular/core';
import {
  ClientImportExportService,
  ClientImportPreview,
  ImportResult,
  ClientPreviewItem
} from '../../services/client-import-export.service';

@Component({
  selector: 'app-client-import',
  templateUrl: './client-import.component.html',
  styleUrls: ['./client-import.component.css']
})
export class ClientImportComponent {
  @ViewChild('fileInput') fileInput!: ElementRef<HTMLInputElement>;

  // État du composant
  selectedFile: File | null = null;
  previewData: ClientImportPreview | null = null;
  importResult: ImportResult | null = null;
  isLoading = false;
  isDragOver = false;
  showPreview = false;
  currentStep: 'upload' | 'preview' | 'import' | 'result' = 'upload';

  // Configuration du mapping des colonnes
  defaultMapping = {
    name: 'name',
    type: 'type',
    email: 'email',
    phone: 'phone',
    address: 'address',
    siret: 'siret',
    sector: 'sector',
    website: 'website',
    notes: 'notes'
  };

  // Action pour les doublons
  duplicateAction: 'ignore' | 'replace' | 'update' = 'ignore';
  duplicateOptions = [
    { value: 'ignore', label: 'Ignorer les doublons' },
    { value: 'replace', label: 'Remplacer complètement' },
    { value: 'update', label: 'Mettre à jour les champs non vides' }
  ];

  constructor(private importService: ClientImportExportService) {}

  // Gestion du drag & drop
  onDragOver(event: DragEvent): void {
    event.preventDefault();
    this.isDragOver = true;
  }

  onDragLeave(event: DragEvent): void {
    event.preventDefault();
    this.isDragOver = false;
  }

  onDrop(event: DragEvent): void {
    event.preventDefault();
    this.isDragOver = false;

    const files = event.dataTransfer?.files;
    if (files && files.length > 0) {
      this.handleFileSelection(files[0]);
    }
  }

  // Sélection de fichier
  onFileSelect(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
      this.handleFileSelection(input.files[0]);
    }
  }

  handleFileSelection(file: File): void {
    // Vérification du type de fichier
    const allowedTypes = [
      'text/csv',
      'application/vnd.ms-excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];

    if (!allowedTypes.includes(file.type) && !file.name.match(/\.(csv|xlsx|xls)$/i)) {
      alert('Seuls les fichiers CSV et Excel (.xlsx, .xls) sont acceptés');
      return;
    }

    // Vérification de la taille (10MB max)
    if (file.size > 10 * 1024 * 1024) {
      alert('Le fichier ne peut pas dépasser 10MB');
      return;
    }

    this.selectedFile = file;
    this.resetState();
  }

  // Téléchargement des templates
  downloadCsvTemplate(): void {
    this.isLoading = true;
    this.importService.downloadCsvTemplate().subscribe({
      next: (blob) => {
        this.importService.downloadFile(blob, 'template_clients.csv');
        this.isLoading = false;
      },
      error: (error) => {
        console.error('Erreur téléchargement template CSV:', error);
        this.isLoading = false;
      }
    });
  }

  downloadExcelTemplate(): void {
    this.isLoading = true;
    this.importService.downloadExcelTemplate().subscribe({
      next: (blob) => {
        this.importService.downloadFile(blob, 'template_clients.xlsx');
        this.isLoading = false;
      },
      error: (error) => {
        console.error('Erreur téléchargement template Excel:', error);
        this.isLoading = false;
      }
    });
  }

  // Prévisualisation
  previewImport(): void {
    if (!this.selectedFile) return;

    this.isLoading = true;
    this.currentStep = 'preview';

    this.importService.previewImport(this.selectedFile, this.defaultMapping).subscribe({
      next: (preview) => {
        this.previewData = preview;
        this.showPreview = true;
        this.isLoading = false;
      },
      error: (error) => {
        console.error('Erreur prévisualisation:', error);
        this.isLoading = false;
        this.currentStep = 'upload';
      }
    });
  }

  // Import définitif
  executeImport(): void {
    if (!this.selectedFile || !this.previewData) return;

    this.isLoading = true;
    this.currentStep = 'import';

    this.importService.importClients(
      this.selectedFile,
      this.defaultMapping,
      this.duplicateAction
    ).subscribe({
      next: (result) => {
        this.importResult = result;
        this.currentStep = 'result';
        this.isLoading = false;
      },
      error: (error) => {
        console.error('Erreur import:', error);
        this.isLoading = false;
      }
    });
  }

  // Utilitaires
  resetState(): void {
    this.previewData = null;
    this.importResult = null;
    this.showPreview = false;
    this.currentStep = 'upload';
  }

  startOver(): void {
    this.selectedFile = null;
    this.resetState();
    if (this.fileInput) {
      this.fileInput.nativeElement.value = '';
    }
  }

  // Getters pour les templates
  get fileSize(): string {
    if (!this.selectedFile) return '';
    const size = this.selectedFile.size;
    if (size < 1024) return `${size} B`;
    if (size < 1024 * 1024) return `${Math.round(size / 1024)} KB`;
    return `${Math.round(size / (1024 * 1024))} MB`;
  }

  get hasErrors(): boolean {
    return this.previewData?.data.stats.invalid_rows > 0 || false;
  }

  get hasDuplicates(): boolean {
    return this.previewData?.data.stats.duplicates_found > 0 || false;
  }

  // Méthodes d'aide pour les templates
  getRowClass(item: ClientPreviewItem): string {
    if (!item.validation.is_valid) return 'error';
    if (item.duplicate.is_duplicate) return 'warning';
    return 'success';
  }
}
```

### 2. Composant d'export

```typescript
// src/app/components/client-export/client-export.component.ts
import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup } from '@angular/forms';
import { ClientImportExportService, ExportOptions } from '../../services/client-import-export.service';

@Component({
  selector: 'app-client-export',
  templateUrl: './client-export.component.html',
  styleUrls: ['./client-export.component.css']
})
export class ClientExportComponent implements OnInit {
  exportForm!: FormGroup;
  isExporting = false;

  // Options disponibles
  availableColumns = [
    { value: 'client_id', label: 'ID Client' },
    { value: 'name', label: 'Nom/Raison sociale' },
    { value: 'type', label: 'Type' },
    { value: 'email', label: 'Email' },
    { value: 'phone', label: 'Téléphone' },
    { value: 'address', label: 'Adresse' },
    { value: 'siret', label: 'SIRET' },
    { value: 'sector', label: 'Secteur' },
    { value: 'website', label: 'Site web' },
    { value: 'notes', label: 'Notes' },
    { value: 'is_active', label: 'Statut' },
    { value: 'categories', label: 'Catégories' },
    { value: 'created_at', label: 'Date création' },
    { value: 'creator', label: 'Créé par' }
  ];

  formats = [
    { value: 'csv', label: 'CSV (.csv)' },
    { value: 'excel', label: 'Excel (.xlsx)' }
  ];

  clientTypes = [
    { value: 'particulier', label: 'Particulier' },
    { value: 'entreprise', label: 'Entreprise' }
  ];

  constructor(
    private fb: FormBuilder,
    private exportService: ClientImportExportService
  ) {}

  ngOnInit(): void {
    this.initForm();
  }

  initForm(): void {
    this.exportForm = this.fb.group({
      format: ['csv'],
      columns: [['client_id', 'name', 'type', 'email', 'phone']],
      typeFilter: [''],
      activeFilter: [''],
      search: [''],
      limit: [10000],
      specificIds: ['']
    });
  }

  // Export basique
  exportBasic(): void {
    const options: ExportOptions = {
      format: 'csv',
      columns: ['client_id', 'name', 'type', 'email', 'phone']
    };
    this.performExport(options, 'export_clients_basique');
  }

  // Export personnalisé
  exportCustom(): void {
    if (!this.exportForm.valid) return;

    const formValue = this.exportForm.value;
    const options: ExportOptions = {
      format: formValue.format,
      columns: formValue.columns,
      limit: formValue.limit
    };

    // Filtres
    const filters: any = {};
    if (formValue.typeFilter) filters.type = formValue.typeFilter;
    if (formValue.activeFilter !== '') filters.is_active = formValue.activeFilter === 'true';
    if (formValue.search) filters.search = formValue.search;

    if (Object.keys(filters).length > 0) {
      options.filters = filters;
    }

    // IDs spécifiques
    if (formValue.specificIds) {
      const ids = formValue.specificIds
        .split(',')
        .map((id: string) => parseInt(id.trim()))
        .filter((id: number) => !isNaN(id));

      if (ids.length > 0) {
        options.client_ids = ids;
      }
    }

    this.performExport(options, 'export_clients_personnalise');
  }

  // Méthode commune d'export
  private performExport(options: ExportOptions, filenamePrefix: string): void {
    this.isExporting = true;

    this.exportService.exportClients(options).subscribe({
      next: (blob) => {
        const filename = this.exportService.generateFilename(
          filenamePrefix,
          options.format || 'csv'
        );
        this.exportService.downloadFile(blob, filename);
        this.isExporting = false;
      },
      error: (error) => {
        console.error('Erreur export:', error);
        alert('Erreur lors de l\'export. Veuillez réessayer.');
        this.isExporting = false;
      }
    });
  }

  // Sélection/désélection de toutes les colonnes
  toggleAllColumns(checked: boolean): void {
    const columns = checked ? this.availableColumns.map(col => col.value) : [];
    this.exportForm.patchValue({ columns });
  }

  // Reset du formulaire
  resetForm(): void {
    this.initForm();
  }
}
```

---

## Templates HTML

### 1. Template d'import

```html
<!-- src/app/components/client-import/client-import.component.html -->
<div class="client-import-container">
  <div class="import-header">
    <h2>
      <i class="fas fa-upload"></i>
      Import de clients
    </h2>
    <p class="description">
      Importez vos clients depuis des fichiers CSV ou Excel
    </p>
  </div>

  <!-- Étape 1: Upload du fichier -->
  <div class="step-card" *ngIf="currentStep === 'upload'">
    <h3>1. Sélectionnez votre fichier</h3>

    <!-- Templates -->
    <div class="templates-section">
      <h4>Télécharger les modèles :</h4>
      <div class="template-buttons">
        <button
          type="button"
          class="btn btn-outline-primary"
          (click)="downloadCsvTemplate()"
          [disabled]="isLoading">
          <i class="fas fa-download"></i>
          Modèle CSV
        </button>
        <button
          type="button"
          class="btn btn-outline-success"
          (click)="downloadExcelTemplate()"
          [disabled]="isLoading">
          <i class="fas fa-file-excel"></i>
          Modèle Excel
        </button>
      </div>
    </div>

    <!-- Zone de drop -->
    <div
      class="drop-zone"
      [class.drag-over]="isDragOver"
      [class.has-file]="selectedFile"
      (dragover)="onDragOver($event)"
      (dragleave)="onDragLeave($event)"
      (drop)="onDrop($event)"
      (click)="fileInput.click()">

      <div class="drop-content" *ngIf="!selectedFile">
        <i class="fas fa-cloud-upload-alt"></i>
        <p><strong>Cliquez ici</strong> ou glissez-déposez votre fichier</p>
        <p class="file-info">
          Formats acceptés: CSV, Excel (.xlsx, .xls)<br>
          Taille maximum: 10MB
        </p>
      </div>

      <div class="selected-file" *ngIf="selectedFile">
        <div class="file-info">
          <i class="fas fa-file" [class.fa-file-csv]="selectedFile.name.includes('.csv')"
             [class.fa-file-excel]="selectedFile.name.includes('.xlsx') || selectedFile.name.includes('.xls')"></i>
          <div class="file-details">
            <p class="file-name">{{ selectedFile.name }}</p>
            <p class="file-size">{{ fileSize }}</p>
          </div>
        </div>
        <button
          type="button"
          class="btn btn-sm btn-outline-danger"
          (click)="startOver(); $event.stopPropagation()">
          <i class="fas fa-times"></i>
          Supprimer
        </button>
      </div>
    </div>

    <input
      #fileInput
      type="file"
      accept=".csv,.xlsx,.xls"
      (change)="onFileSelect($event)"
      style="display: none">

    <!-- Action -->
    <div class="step-actions" *ngIf="selectedFile">
      <button
        type="button"
        class="btn btn-primary"
        (click)="previewImport()"
        [disabled]="isLoading">
        <i class="fas fa-eye" *ngIf="!isLoading"></i>
        <i class="fas fa-spinner fa-spin" *ngIf="isLoading"></i>
        {{ isLoading ? 'Analyse en cours...' : 'Prévisualiser' }}
      </button>
    </div>
  </div>

  <!-- Étape 2: Prévisualisation -->
  <div class="step-card" *ngIf="currentStep === 'preview' && previewData">
    <h3>2. Prévisualisation de l'import</h3>

    <!-- Statistiques -->
    <div class="preview-stats">
      <div class="stats-grid">
        <div class="stat-card total">
          <div class="stat-number">{{ previewData.data.stats.total_rows }}</div>
          <div class="stat-label">Lignes total</div>
        </div>
        <div class="stat-card success">
          <div class="stat-number">{{ previewData.data.stats.valid_rows }}</div>
          <div class="stat-label">Valides</div>
        </div>
        <div class="stat-card error" *ngIf="hasErrors">
          <div class="stat-number">{{ previewData.data.stats.invalid_rows }}</div>
          <div class="stat-label">Erreurs</div>
        </div>
        <div class="stat-card warning" *ngIf="hasDuplicates">
          <div class="stat-number">{{ previewData.data.stats.duplicates_found }}</div>
          <div class="stat-label">Doublons</div>
        </div>
      </div>
    </div>

    <!-- Configuration doublons -->
    <div class="duplicate-config" *ngIf="hasDuplicates">
      <h4>Gestion des doublons détectés :</h4>
      <div class="radio-group">
        <label *ngFor="let option of duplicateOptions" class="radio-option">
          <input
            type="radio"
            name="duplicateAction"
            [value]="option.value"
            [(ngModel)]="duplicateAction">
          <span class="radio-label">{{ option.label }}</span>
        </label>
      </div>
    </div>

    <!-- Aperçu des données -->
    <div class="preview-table">
      <h4>Aperçu des données (premières lignes) :</h4>
      <div class="table-container">
        <table class="table">
          <thead>
            <tr>
              <th>Ligne</th>
              <th>Nom</th>
              <th>Type</th>
              <th>Email</th>
              <th>Statut</th>
              <th>Problèmes</th>
            </tr>
          </thead>
          <tbody>
            <tr *ngFor="let item of previewData.data.preview"
                [class]="getRowClass(item)">
              <td>{{ item.row_number }}</td>
              <td>{{ item.data.name }}</td>
              <td>{{ item.data.type }}</td>
              <td>{{ item.data.email }}</td>
              <td>
                <span class="badge"
                      [class.badge-success]="item.validation.is_valid && !item.duplicate.is_duplicate"
                      [class.badge-danger]="!item.validation.is_valid"
                      [class.badge-warning]="item.validation.is_valid && item.duplicate.is_duplicate">
                  <span *ngIf="item.validation.is_valid && !item.duplicate.is_duplicate">✓ Valide</span>
                  <span *ngIf="!item.validation.is_valid">✗ Erreur</span>
                  <span *ngIf="item.validation.is_valid && item.duplicate.is_duplicate">⚠ Doublon</span>
                </span>
              </td>
              <td>
                <div *ngIf="!item.validation.is_valid" class="errors">
                  <div *ngFor="let error of item.validation.errors | keyvalue" class="error-item">
                    <small>{{ error.key }}: {{ error.value[0] }}</small>
                  </div>
                </div>
                <div *ngIf="item.duplicate.is_duplicate" class="duplicates">
                  <div *ngFor="let conflict of item.duplicate.conflicts" class="duplicate-item">
                    <small>{{ conflict.field }} déjà utilisé par: {{ conflict.existing_client.name }}</small>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Actions -->
    <div class="step-actions">
      <button
        type="button"
        class="btn btn-outline-secondary"
        (click)="currentStep = 'upload'">
        <i class="fas fa-arrow-left"></i>
        Retour
      </button>
      <button
        type="button"
        class="btn btn-success"
        (click)="executeImport()"
        [disabled]="isLoading || previewData.data.stats.valid_rows === 0">
        <i class="fas fa-download" *ngIf="!isLoading"></i>
        <i class="fas fa-spinner fa-spin" *ngIf="isLoading"></i>
        {{ isLoading ? 'Import en cours...' : 'Confirmer l\'import' }}
      </button>
    </div>
  </div>

  <!-- Étape 3: Résultat -->
  <div class="step-card" *ngIf="currentStep === 'result' && importResult">
    <h3>3. Résultat de l'import</h3>

    <div class="result-summary" [class.success]="importResult.success" [class.error]="!importResult.success">
      <div class="result-icon">
        <i class="fas fa-check-circle" *ngIf="importResult.success"></i>
        <i class="fas fa-exclamation-circle" *ngIf="!importResult.success"></i>
      </div>
      <div class="result-message">
        <h4>{{ importResult.message }}</h4>
      </div>
    </div>

    <div class="result-stats" *ngIf="importResult.success">
      <div class="stats-grid">
        <div class="stat-card total">
          <div class="stat-number">{{ importResult.data.report.total_rows }}</div>
          <div class="stat-label">Total traité</div>
        </div>
        <div class="stat-card success">
          <div class="stat-number">{{ importResult.data.report.imported }}</div>
          <div class="stat-label">Importés</div>
        </div>
        <div class="stat-card info" *ngIf="importResult.data.report.updated > 0">
          <div class="stat-number">{{ importResult.data.report.updated }}</div>
          <div class="stat-label">Mis à jour</div>
        </div>
        <div class="stat-card warning" *ngIf="importResult.data.report.ignored > 0">
          <div class="stat-number">{{ importResult.data.report.ignored }}</div>
          <div class="stat-label">Ignorés</div>
        </div>
        <div class="stat-card error" *ngIf="importResult.data.report.errors.length > 0">
          <div class="stat-number">{{ importResult.data.report.errors.length }}</div>
          <div class="stat-label">Erreurs</div>
        </div>
      </div>
    </div>

    <!-- Erreurs détaillées -->
    <div class="result-errors" *ngIf="importResult.data.report.errors.length > 0">
      <h4>Erreurs rencontrées :</h4>
      <div class="error-list">
        <div *ngFor="let error of importResult.data.report.errors" class="error-item">
          <strong>Ligne {{ error.row }}:</strong> {{ error.error || error.errors }}
        </div>
      </div>
    </div>

    <!-- Avertissements -->
    <div class="result-warnings" *ngIf="importResult.data.report.warnings.length > 0">
      <h4>Avertissements :</h4>
      <div class="warning-list">
        <div *ngFor="let warning of importResult.data.report.warnings" class="warning-item">
          {{ warning }}
        </div>
      </div>
    </div>

    <!-- Actions finales -->
    <div class="step-actions">
      <button
        type="button"
        class="btn btn-primary"
        (click)="startOver()">
        <i class="fas fa-plus"></i>
        Nouvel import
      </button>
    </div>
  </div>

  <!-- Loader global -->
  <div class="loading-overlay" *ngIf="isLoading && currentStep !== 'upload'">
    <div class="loading-content">
      <i class="fas fa-spinner fa-spin fa-3x"></i>
      <p *ngIf="currentStep === 'preview'">Analyse du fichier en cours...</p>
      <p *ngIf="currentStep === 'import'">Import des données en cours...</p>
    </div>
  </div>
</div>
```

### 2. Template d'export

```html
<!-- src/app/components/client-export/client-export.component.html -->
<div class="client-export-container">
  <div class="export-header">
    <h2>
      <i class="fas fa-download"></i>
      Export de clients
    </h2>
    <p class="description">
      Exportez vos clients vers des fichiers CSV ou Excel
    </p>
  </div>

  <!-- Export rapide -->
  <div class="quick-export-section">
    <h3>Export rapide</h3>
    <div class="quick-buttons">
      <button
        type="button"
        class="btn btn-outline-primary"
        (click)="exportBasic()"
        [disabled]="isExporting">
        <i class="fas fa-file-csv"></i>
        Export CSV basique
      </button>
    </div>
  </div>

  <hr>

  <!-- Export personnalisé -->
  <div class="custom-export-section">
    <h3>Export personnalisé</h3>

    <form [formGroup]="exportForm" (ngSubmit)="exportCustom()">
      <div class="row">
        <!-- Format -->
        <div class="col-md-6 mb-3">
          <label class="form-label">Format d'export</label>
          <select class="form-control" formControlName="format">
            <option *ngFor="let format of formats" [value]="format.value">
              {{ format.label }}
            </option>
          </select>
        </div>

        <!-- Limite -->
        <div class="col-md-6 mb-3">
          <label class="form-label">Limite d'export</label>
          <input
            type="number"
            class="form-control"
            formControlName="limit"
            min="1"
            max="10000">
        </div>
      </div>

      <!-- Colonnes -->
      <div class="mb-3">
        <label class="form-label">Colonnes à exporter</label>
        <div class="columns-selection">
          <div class="mb-2">
            <button
              type="button"
              class="btn btn-sm btn-outline-secondary me-2"
              (click)="toggleAllColumns(true)">
              Tout sélectionner
            </button>
            <button
              type="button"
              class="btn btn-sm btn-outline-secondary"
              (click)="toggleAllColumns(false)">
              Tout désélectionner
            </button>
          </div>
          <div class="columns-grid">
            <label *ngFor="let column of availableColumns" class="column-option">
              <input
                type="checkbox"
                [value]="column.value"
                [checked]="exportForm.value.columns?.includes(column.value)"
                (change)="$event.target.checked ?
                  exportForm.patchValue({columns: [...(exportForm.value.columns || []), column.value]}) :
                  exportForm.patchValue({columns: (exportForm.value.columns || []).filter((c: string) => c !== column.value)})">
              {{ column.label }}
            </label>
          </div>
        </div>
      </div>

      <!-- Filtres -->
      <div class="filters-section">
        <h4>Filtres</h4>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Type de client</label>
            <select class="form-control" formControlName="typeFilter">
              <option value="">Tous les types</option>
              <option *ngFor="let type of clientTypes" [value]="type.value">
                {{ type.label }}
              </option>
            </select>
          </div>

          <div class="col-md-4 mb-3">
            <label class="form-label">Statut</label>
            <select class="form-control" formControlName="activeFilter">
              <option value="">Tous les statuts</option>
              <option value="true">Actifs uniquement</option>
              <option value="false">Inactifs uniquement</option>
            </select>
          </div>

          <div class="col-md-4 mb-3">
            <label class="form-label">Recherche</label>
            <input
              type="text"
              class="form-control"
              formControlName="search"
              placeholder="Nom, email ou ID client">
          </div>
        </div>

        <!-- IDs spécifiques -->
        <div class="mb-3">
          <label class="form-label">IDs clients spécifiques (optionnel)</label>
          <input
            type="text"
            class="form-control"
            formControlName="specificIds"
            placeholder="1, 5, 10, 15... (séparés par des virgules)">
          <small class="form-text text-muted">
            Laissez vide pour exporter selon les filtres ci-dessus
          </small>
        </div>
      </div>

      <!-- Actions -->
      <div class="export-actions">
        <button
          type="button"
          class="btn btn-outline-secondary me-2"
          (click)="resetForm()">
          <i class="fas fa-undo"></i>
          Réinitialiser
        </button>
        <button
          type="submit"
          class="btn btn-success"
          [disabled]="!exportForm.valid || isExporting">
          <i class="fas fa-download" *ngIf="!isExporting"></i>
          <i class="fas fa-spinner fa-spin" *ngIf="isExporting"></i>
          {{ isExporting ? 'Export en cours...' : 'Exporter' }}
        </button>
      </div>
    </form>
  </div>

  <!-- Loader -->
  <div class="loading-overlay" *ngIf="isExporting">
    <div class="loading-content">
      <i class="fas fa-spinner fa-spin fa-3x"></i>
      <p>Génération du fichier d'export...</p>
    </div>
  </div>
</div>
```

---

## Styles CSS

### 1. Styles d'import

```scss
// src/app/components/client-import/client-import.component.scss
.client-import-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.import-header {
  text-align: center;
  margin-bottom: 2rem;

  h2 {
    color: #2c3e50;
    margin-bottom: 0.5rem;

    i {
      margin-right: 0.5rem;
      color: #3498db;
    }
  }

  .description {
    color: #7f8c8d;
    font-size: 1.1rem;
  }
}

.step-card {
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 2rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);

  h3 {
    color: #2c3e50;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid #3498db;
    padding-bottom: 0.5rem;
  }
}

// Zone de drop
.drop-zone {
  border: 2px dashed #bdc3c7;
  border-radius: 8px;
  padding: 3rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  background: #f8f9fa;
  margin: 1rem 0;

  &:hover, &.drag-over {
    border-color: #3498db;
    background: #e3f2fd;
  }

  &.has-file {
    border-color: #27ae60;
    background: #e8f5e8;
  }

  .drop-content {
    i {
      font-size: 3rem;
      color: #7f8c8d;
      margin-bottom: 1rem;
    }

    p {
      margin: 0.5rem 0;
      color: #2c3e50;
    }

    .file-info {
      font-size: 0.9rem;
      color: #7f8c8d;
    }
  }

  .selected-file {
    display: flex;
    justify-content: space-between;
    align-items: center;

    .file-info {
      display: flex;
      align-items: center;
      gap: 1rem;

      i {
        font-size: 2rem;
        color: #3498db;
      }

      .file-details {
        text-align: left;

        .file-name {
          font-weight: 600;
          margin: 0;
          color: #2c3e50;
        }

        .file-size {
          margin: 0;
          color: #7f8c8d;
          font-size: 0.9rem;
        }
      }
    }
  }
}

// Templates
.templates-section {
  margin-bottom: 2rem;

  h4 {
    margin-bottom: 1rem;
    color: #2c3e50;
  }

  .template-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;

    .btn {
      i {
        margin-right: 0.5rem;
      }
    }
  }
}

// Statistiques
.preview-stats {
  margin-bottom: 2rem;

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;

    .stat-card {
      text-align: center;
      padding: 1.5rem;
      border-radius: 8px;

      .stat-number {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
      }

      .stat-label {
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      &.total {
        background: #e3f2fd;
        color: #1976d2;
      }

      &.success {
        background: #e8f5e8;
        color: #27ae60;
      }

      &.error {
        background: #ffebee;
        color: #e74c3c;
      }

      &.warning {
        background: #fff3e0;
        color: #f39c12;
      }

      &.info {
        background: #f3e5f5;
        color: #9c27b0;
      }
    }
  }
}

// Configuration doublons
.duplicate-config {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: #fff3e0;
  border-radius: 8px;
  border-left: 4px solid #f39c12;

  h4 {
    color: #f39c12;
    margin-bottom: 1rem;
  }

  .radio-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;

    .radio-option {
      display: flex;
      align-items: center;
      cursor: pointer;

      input {
        margin-right: 0.5rem;
      }

      .radio-label {
        color: #2c3e50;
      }
    }
  }
}

// Table de prévisualisation
.preview-table {
  margin-bottom: 2rem;

  .table-container {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 8px;

    .table {
      margin: 0;

      thead {
        position: sticky;
        top: 0;
        background: #f8f9fa;

        th {
          border-top: none;
          font-weight: 600;
          color: #2c3e50;
        }
      }

      tbody {
        tr {
          &.success {
            background-color: #f8fff8;
          }

          &.error {
            background-color: #fff5f5;
          }

          &.warning {
            background-color: #fffbf0;
          }
        }
      }

      .badge {
        font-size: 0.75rem;

        &.badge-success {
          background: #27ae60;
        }

        &.badge-danger {
          background: #e74c3c;
        }

        &.badge-warning {
          background: #f39c12;
        }
      }

      .errors, .duplicates {
        .error-item, .duplicate-item {
          margin-bottom: 0.25rem;

          small {
            color: #e74c3c;
            display: block;
          }
        }
      }
    }
  }
}

// Actions
.step-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
  padding-top: 1rem;
  border-top: 1px solid #e9ecef;

  .btn {
    min-width: 150px;

    i {
      margin-right: 0.5rem;
    }
  }
}

// Résultats
.result-summary {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 2rem;
  border-radius: 8px;
  margin-bottom: 2rem;

  &.success {
    background: #e8f5e8;
    border: 1px solid #27ae60;

    .result-icon i {
      color: #27ae60;
    }
  }

  &.error {
    background: #ffebee;
    border: 1px solid #e74c3c;

    .result-icon i {
      color: #e74c3c;
    }
  }

  .result-icon i {
    font-size: 3rem;
  }

  .result-message h4 {
    margin: 0;
    color: #2c3e50;
  }
}

.result-errors, .result-warnings {
  margin-bottom: 2rem;

  h4 {
    color: #2c3e50;
    margin-bottom: 1rem;
  }

  .error-list, .warning-list {
    .error-item, .warning-item {
      padding: 0.75rem;
      border-radius: 4px;
      margin-bottom: 0.5rem;
    }

    .error-item {
      background: #ffebee;
      border-left: 3px solid #e74c3c;
      color: #2c3e50;
    }

    .warning-item {
      background: #fff3e0;
      border-left: 3px solid #f39c12;
      color: #2c3e50;
    }
  }
}

// Overlay de chargement
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;

  .loading-content {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    text-align: center;

    i {
      color: #3498db;
      margin-bottom: 1rem;
    }

    p {
      margin: 0;
      color: #2c3e50;
      font-size: 1.1rem;
    }
  }
}

// Responsive
@media (max-width: 768px) {
  .client-import-container {
    padding: 1rem;
  }

  .step-card {
    padding: 1.5rem;
  }

  .stats-grid {
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
  }

  .step-actions {
    flex-direction: column;

    .btn {
      min-width: auto;
    }
  }

  .template-buttons {
    justify-content: center;
  }

  .radio-group {
    .radio-option {
      padding: 0.5rem 0;
    }
  }
}
```

### 2. Styles d'export

```scss
// src/app/components/client-export/client-export.component.scss
.client-export-container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 2rem;
}

.export-header {
  text-align: center;
  margin-bottom: 2rem;

  h2 {
    color: #2c3e50;
    margin-bottom: 0.5rem;

    i {
      margin-right: 0.5rem;
      color: #27ae60;
    }
  }

  .description {
    color: #7f8c8d;
    font-size: 1.1rem;
  }
}

.quick-export-section, .custom-export-section {
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 2rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);

  h3 {
    color: #2c3e50;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid #27ae60;
    padding-bottom: 0.5rem;
  }
}

.quick-buttons {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;

  .btn {
    i {
      margin-right: 0.5rem;
    }
  }
}

.columns-selection {
  .columns-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 0.5rem;
    margin-top: 1rem;
    max-height: 300px;
    overflow-y: auto;
    padding: 1rem;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    background: #f8f9fa;

    .column-option {
      display: flex;
      align-items: center;
      cursor: pointer;
      padding: 0.25rem 0;

      input {
        margin-right: 0.5rem;
      }

      &:hover {
        color: #3498db;
      }
    }
  }
}

.filters-section {
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid #e9ecef;

  h4 {
    color: #2c3e50;
    margin-bottom: 1.5rem;
  }
}

.export-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
  padding-top: 1rem;
  border-top: 1px solid #e9ecef;

  .btn {
    min-width: 150px;

    i {
      margin-right: 0.5rem;
    }
  }
}

// Form styling
.form-label {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.form-control {
  border: 1px solid #ced4da;
  border-radius: 4px;

  &:focus {
    border-color: #27ae60;
    box-shadow: 0 0 0 0.2rem rgba(39, 174, 96, 0.25);
  }
}

// Loading overlay
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;

  .loading-content {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    text-align: center;

    i {
      color: #27ae60;
      margin-bottom: 1rem;
    }

    p {
      margin: 0;
      color: #2c3e50;
      font-size: 1.1rem;
    }
  }
}

// Responsive
@media (max-width: 768px) {
  .client-export-container {
    padding: 1rem;
  }

  .quick-export-section, .custom-export-section {
    padding: 1.5rem;
  }

  .columns-grid {
    grid-template-columns: 1fr;
  }

  .export-actions {
    flex-direction: column;

    .btn {
      min-width: auto;
    }
  }

  .quick-buttons {
    justify-content: center;
  }
}

hr {
  border: none;
  border-top: 1px solid #e9ecef;
  margin: 2rem 0;
}
```

---

## Gestion des erreurs

```typescript
// src/app/services/error-handler.service.ts
import { Injectable } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';

@Injectable({
  providedIn: 'root'
})
export class ErrorHandlerService {

  handleImportError(error: HttpErrorResponse): string {
    if (error.status === 401) {
      return 'Erreur d\'authentification. Veuillez vous reconnecter.';
    }

    if (error.status === 422) {
      const validationErrors = error.error?.errors;
      if (validationErrors) {
        const messages = Object.values(validationErrors).flat();
        return `Erreurs de validation: ${messages.join(', ')}`;
      }
      return 'Erreur de validation des données.';
    }

    if (error.status === 413) {
      return 'Le fichier est trop volumineux. Maximum 10MB autorisé.';
    }

    if (error.status === 500) {
      return 'Erreur serveur. Veuillez réessayer plus tard.';
    }

    return error.error?.message || 'Une erreur inattendue s\'est produite.';
  }

  handleExportError(error: HttpErrorResponse): string {
    if (error.status === 401) {
      return 'Erreur d\'authentification. Veuillez vous reconnecter.';
    }

    if (error.status === 422) {
      return 'Paramètres d\'export invalides.';
    }

    if (error.status === 500) {
      return 'Erreur lors de la génération de l\'export.';
    }

    return 'Erreur lors de l\'export. Veuillez réessayer.';
  }
}
```

## Tests unitaires

### 1. Test du service

```typescript
// src/app/services/client-import-export.service.spec.ts
import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { ClientImportExportService } from './client-import-export.service';

describe('ClientImportExportService', () => {
  let service: ClientImportExportService;
  let httpMock: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule]
    });
    service = TestBed.inject(ClientImportExportService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  describe('downloadCsvTemplate', () => {
    it('should download CSV template', () => {
      const mockBlob = new Blob(['csv content'], { type: 'text/csv' });

      service.downloadCsvTemplate().subscribe(blob => {
        expect(blob).toEqual(mockBlob);
      });

      const req = httpMock.expectOne('http://localhost:8000/api/v1/clients/export/template');
      expect(req.request.method).toBe('GET');
      req.flush(mockBlob);
    });
  });

  describe('previewImport', () => {
    it('should preview import with file', () => {
      const file = new File(['test'], 'test.csv', { type: 'text/csv' });
      const mockResponse = {
        success: true,
        message: 'Prévisualisation générée',
        data: {
          preview: [],
          stats: { total_rows: 0, valid_rows: 0, invalid_rows: 0, duplicates_found: 0 },
          errors: [],
          duplicates: []
        }
      };

      service.previewImport(file).subscribe(response => {
        expect(response).toEqual(mockResponse);
      });

      const req = httpMock.expectOne('http://localhost:8000/api/v1/clients/import/preview');
      expect(req.request.method).toBe('POST');
      expect(req.request.body instanceof FormData).toBeTruthy();
      req.flush(mockResponse);
    });
  });

  describe('generateFilename', () => {
    it('should generate CSV filename with date', () => {
      const filename = service.generateFilename('test', 'csv');
      const today = new Date().toISOString().split('T')[0];
      expect(filename).toBe(`test_${today}.csv`);
    });

    it('should generate Excel filename with date', () => {
      const filename = service.generateFilename('test', 'excel');
      const today = new Date().toISOString().split('T')[0];
      expect(filename).toBe(`test_${today}.xlsx`);
    });
  });
});
```

### 2. Test du composant d'import

```typescript
// src/app/components/client-import/client-import.component.spec.ts
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';
import { ClientImportComponent } from './client-import.component';
import { ClientImportExportService } from '../../services/client-import-export.service';

describe('ClientImportComponent', () => {
  let component: ClientImportComponent;
  let fixture: ComponentFixture<ClientImportComponent>;
  let mockService: jasmine.SpyObj<ClientImportExportService>;

  beforeEach(async () => {
    const spy = jasmine.createSpyObj('ClientImportExportService', [
      'downloadCsvTemplate',
      'downloadExcelTemplate',
      'previewImport',
      'importClients',
      'downloadFile'
    ]);

    await TestBed.configureTestingModule({
      declarations: [ClientImportComponent],
      providers: [
        { provide: ClientImportExportService, useValue: spy }
      ]
    }).compileComponents();

    fixture = TestBed.createComponent(ClientImportComponent);
    component = fixture.componentInstance;
    mockService = TestBed.inject(ClientImportExportService) as jasmine.SpyObj<ClientImportExportService>;
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  describe('handleFileSelection', () => {
    it('should accept valid CSV file', () => {
      const file = new File(['test'], 'test.csv', { type: 'text/csv' });
      component.handleFileSelection(file);
      expect(component.selectedFile).toBe(file);
      expect(component.currentStep).toBe('upload');
    });

    it('should reject invalid file type', () => {
      spyOn(window, 'alert');
      const file = new File(['test'], 'test.txt', { type: 'text/plain' });
      component.handleFileSelection(file);
      expect(window.alert).toHaveBeenCalledWith('Seuls les fichiers CSV et Excel (.xlsx, .xls) sont acceptés');
      expect(component.selectedFile).toBeNull();
    });

    it('should reject file too large', () => {
      spyOn(window, 'alert');
      const largeFile = new File(['x'.repeat(11 * 1024 * 1024)], 'large.csv', { type: 'text/csv' });
      component.handleFileSelection(largeFile);
      expect(window.alert).toHaveBeenCalledWith('Le fichier ne peut pas dépasser 10MB');
      expect(component.selectedFile).toBeNull();
    });
  });

  describe('downloadCsvTemplate', () => {
    it('should download CSV template successfully', () => {
      const mockBlob = new Blob(['csv'], { type: 'text/csv' });
      mockService.downloadCsvTemplate.and.returnValue(of(mockBlob));
      mockService.downloadFile.and.stub();

      component.downloadCsvTemplate();

      expect(mockService.downloadCsvTemplate).toHaveBeenCalled();
      expect(mockService.downloadFile).toHaveBeenCalledWith(mockBlob, 'template_clients.csv');
    });

    it('should handle download error', () => {
      spyOn(console, 'error');
      mockService.downloadCsvTemplate.and.returnValue(throwError('Network error'));

      component.downloadCsvTemplate();

      expect(console.error).toHaveBeenCalledWith('Erreur téléchargement template CSV:', 'Network error');
      expect(component.isLoading).toBeFalse();
    });
  });

  describe('previewImport', () => {
    it('should preview import successfully', () => {
      const file = new File(['test'], 'test.csv', { type: 'text/csv' });
      component.selectedFile = file;

      const mockResponse = {
        success: true,
        message: 'Prévisualisation générée',
        data: {
          preview: [],
          stats: { total_rows: 1, valid_rows: 1, invalid_rows: 0, duplicates_found: 0 },
          errors: [],
          duplicates: []
        }
      };

      mockService.previewImport.and.returnValue(of(mockResponse));

      component.previewImport();

      expect(mockService.previewImport).toHaveBeenCalledWith(file, component.defaultMapping);
      expect(component.previewData).toBe(mockResponse);
      expect(component.currentStep).toBe('preview');
      expect(component.showPreview).toBeTrue();
    });
  });

  describe('getRowClass', () => {
    it('should return error class for invalid row', () => {
      const item = {
        validation: { is_valid: false, errors: [] },
        duplicate: { is_duplicate: false, conflicts: [] }
      } as any;

      expect(component.getRowClass(item)).toBe('error');
    });

    it('should return warning class for duplicate row', () => {
      const item = {
        validation: { is_valid: true, errors: [] },
        duplicate: { is_duplicate: true, conflicts: [] }
      } as any;

      expect(component.getRowClass(item)).toBe('warning');
    });

    it('should return success class for valid row', () => {
      const item = {
        validation: { is_valid: true, errors: [] },
        duplicate: { is_duplicate: false, conflicts: [] }
      } as any;

      expect(component.getRowClass(item)).toBe('success');
    });
  });
});
```

---

## Intégration dans l'application

### 1. Module principal

```typescript
// src/app/app.module.ts
import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { HttpClientModule, HTTP_INTERCEPTORS } from '@angular/common/http';
import { ReactiveFormsModule, FormsModule } from '@angular/forms';

import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { ClientImportComponent } from './components/client-import/client-import.component';
import { ClientExportComponent } from './components/client-export/client-export.component';
import { AuthInterceptor } from './interceptors/auth.interceptor';

@NgModule({
  declarations: [
    AppComponent,
    ClientImportComponent,
    ClientExportComponent
  ],
  imports: [
    BrowserModule,
    AppRoutingModule,
    HttpClientModule,
    ReactiveFormsModule,
    FormsModule
  ],
  providers: [
    {
      provide: HTTP_INTERCEPTORS,
      useClass: AuthInterceptor,
      multi: true
    }
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
```

### 2. Routing

```typescript
// src/app/app-routing.module.ts
import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { ClientImportComponent } from './components/client-import/client-import.component';
import { ClientExportComponent } from './components/client-export/client-export.component';

const routes: Routes = [
  { path: 'clients/import', component: ClientImportComponent },
  { path: 'clients/export', component: ClientExportComponent },
  { path: '', redirectTo: '/clients/import', pathMatch: 'full' }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
```

### 3. Navigation

```html
<!-- src/app/app.component.html -->
<div class="app-container">
  <nav class="navbar">
    <div class="nav-brand">
      <h1>TargetDesk - Gestion Clients</h1>
    </div>
    <div class="nav-links">
      <a routerLink="/clients/import" routerLinkActive="active" class="nav-link">
        <i class="fas fa-upload"></i>
        Import
      </a>
      <a routerLink="/clients/export" routerLinkActive="active" class="nav-link">
        <i class="fas fa-download"></i>
        Export
      </a>
    </div>
  </nav>

  <main class="main-content">
    <router-outlet></router-outlet>
  </main>
</div>
```

---

## Utilisation

1. **Installation des dépendances**
2. **Configuration de l'environnement** avec l'URL de l'API et le token
3. **Import du module** dans votre application
4. **Navigation vers les composants** via le routing Angular

Les composants sont entièrement autonomes et gèrent toutes les interactions avec l'API TargetDesk, offrant une expérience utilisateur complète pour l'import et l'export de clients.
