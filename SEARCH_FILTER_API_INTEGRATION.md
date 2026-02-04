# API Recherche et Filtrage TargetDesk - Guide d'intégration

## Base URL
```
http://localhost:8000/api/v1
```

## Authentification
Tous les endpoints nécessitent une authentification Bearer token.

```
Authorization: Bearer {token}
```

---

## Vue d'ensemble

Le système de recherche et filtrage TargetDesk offre des fonctionnalités avancées pour localiser rapidement clients et fournisseurs :

- **Recherche globale temps réel** avec autocomplétion
- **Filtrage multi-critères** cumulatif
- **Tri dynamique** sur plusieurs colonnes
- **Recherche fuzzy** pour tolérance aux fautes de frappe
- **Highlighting** des termes trouvés
- **Statistiques** de résultats en temps réel

### Fonctionnalités principales

1. **Search-as-you-type** : Recherche instantanée dès 2 caractères
2. **Filtres cumulables** : Logique AND entre filtres multiples
3. **Tri persistant** : Conservation des préférences lors de la navigation
4. **Performance optimisée** : Requêtes optimisées pour grandes bases
5. **Highlighting intelligent** : Mise en évidence du champ correspondant

---

## Endpoints de Recherche Rapide

### 1. Recherche rapide clients

**GET** `/clients/search`

Recherche clients pour fonctionnalité d'autocomplétion.

```bash
curl -X GET "http://localhost:8000/api/v1/clients/search?q=acme&limit=10&fuzzy=true" \
  -H "Authorization: Bearer {token}"
```

**Paramètres de requête :**
- `q` (string, requis) - Terme de recherche (minimum 2 caractères)
- `limit` (integer, optionnel) - Nombre maximum de résultats (défaut: 10, max: 50)
- `fuzzy` (boolean, optionnel) - Recherche fuzzy pour les fautes de frappe (défaut: true)

**Champs recherchés :**
- Nom/Raison sociale
- Email
- Téléphone
- SIRET
- Adresse
- ID Client

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Résultats de recherche",
  "data": {
    "results": [
      {
        "id": 1,
        "client_id": "CLI-ABC123XYZ4",
        "name": "Entreprise ACME",
        "email": "contact@acme.com",
        "phone": "0123456789",
        "type": "entreprise",
        "address": "123 Rue de la Paix, 75001 Paris",
        "highlighted_field": "name",
        "match_score": 1.0
      }
    ],
    "query": "acme",
    "total_found": 1
  }
}
```

**Scoring des résultats :**
- `1.0` : Correspondance exacte au début du champ
- `0.8` : Correspondance partielle dans le champ
- `0.6` : Correspondance fuzzy (SOUNDEX)

---

### 2. Recherche rapide fournisseurs

**GET** `/suppliers/search`

Recherche fournisseurs pour fonctionnalité d'autocomplétion.

```bash
curl -X GET "http://localhost:8000/api/v1/suppliers/search?q=electro&limit=10" \
  -H "Authorization: Bearer {token}"
```

**Paramètres identiques aux clients** avec champs supplémentaires :
- Type de relation (`fournisseur`, `client_et_fournisseur`)

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Résultats de recherche",
  "data": {
    "results": [
      {
        "id": 4,
        "supplier_id": "FOUR-ABC123XY",
        "name": "ElectroTech Distribution",
        "email": "contact@electrotech.fr",
        "phone": "0234567890",
        "type": "entreprise",
        "relation_type": "fournisseur",
        "address": "Zone Industrielle, Lyon",
        "highlighted_field": "name",
        "match_score": 1.0
      }
    ],
    "query": "electro",
    "total_found": 1
  }
}
```

---

## Endpoints de Filtrage Avancé

### 3. Filtrage avancé clients

**GET** `/clients`

Liste clients avec recherche et filtres multiples.

```bash
curl -X GET "http://localhost:8000/api/v1/clients?search=entreprise&type=entreprise&sector=technologie&created_from=2026-01-01&sort_by=name&sort_order=asc&per_page=20" \
  -H "Authorization: Bearer {token}"
```

**Paramètres de filtrage :**

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `search` | string | Recherche dans tous les champs | `"acme"` |
| `type` | enum | Type de client | `"particulier"`, `"entreprise"` |
| `sector` | string | Secteur d'activité (partiel) | `"technologie"` |
| `created_from` | date | Date de création minimum | `"2026-01-01"` |
| `created_to` | date | Date de création maximum | `"2026-12-31"` |
| `updated_from` | date | Date de modification minimum | `"2026-01-01"` |
| `updated_to` | date | Date de modification maximum | `"2026-12-31"` |

**Paramètres de tri :**

| Paramètre | Type | Description | Valeurs autorisées |
|-----------|------|-------------|-------------------|
| `sort_by` | string | Champ de tri | `"name"`, `"created_at"`, `"updated_at"`, `"email"`, `"type"`, `"sector"` |
| `sort_order` | string | Ordre de tri | `"asc"`, `"desc"` |

**Paramètres de pagination :**
- `page` (integer, optionnel) - Numéro de page (défaut: 1)
- `per_page` (integer, optionnel) - Éléments par page (défaut: 15, max: 100)

**Réponse (200 OK) :**
```json
{
  "success": true,
  "message": "Clients récupérés avec succès",
  "data": {
    "clients": [
      {
        "id": 1,
        "client_id": "CLI-ABC123XYZ4",
        "name": "Entreprise ACME",
        "type": "entreprise",
        "email": "contact@acme.com",
        "phone": "0123456789",
        "address": "123 Rue de la Paix, 75001 Paris",
        "siret": "12345678901234",
        "sector": "Technologie",
        "website": "https://acme.com",
        "notes": "Client important",
        "is_active": true,
        "created_by": 1,
        "created_at": "2026-01-09T18:00:00.000000Z",
        "updated_at": "2026-01-09T18:00:00.000000Z",
        "categories_count": 2,
        "categories_summary": [
          {
            "type": "secteur",
            "count": 1,
            "categories": [
              {
                "id": 1,
                "name": "Technologie",
                "color": "#4ECDC4"
              }
            ]
          }
        ],
        "creator": {
          "id": 1,
          "name": "John Doe"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 3,
      "total_items": 45,
      "per_page": 20
    },
    "filters_applied": {
      "search": "entreprise",
      "type": "entreprise",
      "sector": "technologie",
      "created_from": "2026-01-01",
      "sort_by": "name",
      "sort_order": "asc"
    },
    "total_without_filters": 150
  }
}
```

---

### 4. Filtrage avancé fournisseurs

**GET** `/suppliers`

Liste fournisseurs avec recherche et filtres multiples.

```bash
curl -X GET "http://localhost:8000/api/v1/suppliers?search=electro&type=entreprise&relation_type=fournisseur&sort_by=name&sort_order=asc" \
  -H "Authorization: Bearer {token}"
```

**Paramètres de filtrage supplémentaires :**
- `relation_type` (enum) - Type de relation : `"fournisseur"`, `"client_et_fournisseur"`

**Champs de tri supplémentaires :**
- `relation_type` - Type de relation

**Structure de réponse identique aux clients** avec champs spécifiques aux fournisseurs.

---

## Exemples d'intégration

### JavaScript/TypeScript - Service de recherche

```typescript
export interface SearchResult {
  id: number;
  client_id?: string;
  supplier_id?: string;
  name: string;
  email: string;
  phone: string;
  type: string;
  highlighted_field: string;
  match_score: number;
}

export interface FilterOptions {
  search?: string;
  type?: 'particulier' | 'entreprise';
  sector?: string;
  relation_type?: 'fournisseur' | 'client_et_fournisseur';
  created_from?: string;
  created_to?: string;
  updated_from?: string;
  updated_to?: string;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
  page?: number;
  per_page?: number;
}

class SearchService {
  private baseURL = 'http://localhost:8000/api/v1';
  private debounceTimer: NodeJS.Timeout | null = null;

  constructor(private token: string) {}

  private getHeaders(): HeadersInit {
    return {
      'Authorization': `Bearer ${this.token}`,
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    };
  }

  /**
   * Recherche rapide avec debouncing pour autocomplétion
   */
  searchClients(
    query: string,
    options: { limit?: number; fuzzy?: boolean } = {},
    onResults?: (results: SearchResult[]) => void
  ): Promise<SearchResult[]> {
    return new Promise((resolve, reject) => {
      // Clear previous timer
      if (this.debounceTimer) {
        clearTimeout(this.debounceTimer);
      }

      // Debounce de 300ms
      this.debounceTimer = setTimeout(async () => {
        try {
          if (query.length < 2) {
            resolve([]);
            return;
          }

          const params = new URLSearchParams({
            q: query,
            limit: (options.limit || 10).toString(),
            fuzzy: (options.fuzzy !== false).toString()
          });

          const response = await fetch(
            `${this.baseURL}/clients/search?${params}`,
            { headers: this.getHeaders() }
          );

          const data = await response.json();

          if (data.success) {
            const results = data.data.results;
            onResults?.(results);
            resolve(results);
          } else {
            reject(new Error(data.message));
          }
        } catch (error) {
          reject(error);
        }
      }, 300);
    });
  }

  /**
   * Recherche rapide fournisseurs
   */
  searchSuppliers(
    query: string,
    options: { limit?: number; fuzzy?: boolean } = {}
  ): Promise<SearchResult[]> {
    return this.searchClients(query, options); // Même logique
  }

  /**
   * Filtrage avancé clients
   */
  async filterClients(filters: FilterOptions): Promise<any> {
    const params = new URLSearchParams();

    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        params.append(key, value.toString());
      }
    });

    const response = await fetch(
      `${this.baseURL}/clients?${params}`,
      { headers: this.getHeaders() }
    );

    const data = await response.json();

    if (data.success) {
      return data.data;
    } else {
      throw new Error(data.message);
    }
  }

  /**
   * Filtrage avancé fournisseurs
   */
  async filterSuppliers(filters: FilterOptions): Promise<any> {
    const params = new URLSearchParams();

    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        params.append(key, value.toString());
      }
    });

    const response = await fetch(
      `${this.baseURL}/suppliers?${params}`,
      { headers: this.getHeaders() }
    );

    const data = await response.json();

    if (data.success) {
      return data.data;
    } else {
      throw new Error(data.message);
    }
  }
}
```

### React Hook pour recherche avec highlighting

```typescript
import { useState, useEffect, useCallback } from 'react';

export function useSearch(searchService: SearchService) {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<SearchResult[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const search = useCallback(async (searchQuery: string, type: 'clients' | 'suppliers' = 'clients') => {
    if (searchQuery.length < 2) {
      setResults([]);
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const searchMethod = type === 'clients' ?
        searchService.searchClients.bind(searchService) :
        searchService.searchSuppliers.bind(searchService);

      const searchResults = await searchMethod(searchQuery, { limit: 10, fuzzy: true });
      setResults(searchResults);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erreur de recherche');
      setResults([]);
    } finally {
      setLoading(false);
    }
  }, [searchService]);

  useEffect(() => {
    search(query);
  }, [query, search]);

  const highlightTerm = useCallback((text: string, term: string): string => {
    if (!term) return text;
    const regex = new RegExp(`(${term})`, 'gi');
    return text.replace(regex, '<mark>$1</mark>');
  }, []);

  return {
    query,
    setQuery,
    results,
    loading,
    error,
    highlightTerm,
    search
  };
}

// Composant de recherche
function SearchBox({ onSelect, type = 'clients' }: {
  onSelect: (result: SearchResult) => void;
  type?: 'clients' | 'suppliers';
}) {
  const searchService = new SearchService(getToken());
  const { query, setQuery, results, loading, error, highlightTerm } = useSearch(searchService);

  return (
    <div className="search-box">
      <input
        type="text"
        value={query}
        onChange={(e) => setQuery(e.target.value)}
        placeholder={`Rechercher un ${type === 'clients' ? 'client' : 'fournisseur'}...`}
        className="search-input"
      />

      {loading && <div className="search-loading">Recherche...</div>}

      {error && <div className="search-error">{error}</div>}

      {results.length > 0 && (
        <div className="search-results">
          {results.map((result) => (
            <div
              key={result.id}
              className="search-result-item"
              onClick={() => onSelect(result)}
            >
              <div className="result-name">
                <span dangerouslySetInnerHTML={{
                  __html: highlightTerm(result.name, query)
                }} />
                <span className="result-score">{Math.round(result.match_score * 100)}%</span>
              </div>

              <div className="result-details">
                <span className="result-id">
                  {type === 'clients' ? result.client_id : result.supplier_id}
                </span>
                <span className="result-type">{result.type}</span>
              </div>

              {result.highlighted_field && (
                <div className="result-matched-field">
                  Correspondance: {result.highlighted_field}
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
```

### React Hook pour filtrage avancé

```typescript
export interface FilterState {
  search: string;
  type: string;
  sector: string;
  relation_type: string;
  created_from: string;
  created_to: string;
  sort_by: string;
  sort_order: 'asc' | 'desc';
  page: number;
  per_page: number;
}

export function useAdvancedFilter(
  searchService: SearchService,
  type: 'clients' | 'suppliers' = 'clients'
) {
  const [filters, setFilters] = useState<FilterState>({
    search: '',
    type: '',
    sector: '',
    relation_type: '',
    created_from: '',
    created_to: '',
    sort_by: 'name',
    sort_order: 'asc',
    page: 1,
    per_page: 15
  });

  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Debounced filter application
  const [debouncedFilters, setDebouncedFilters] = useState(filters);

  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedFilters(filters);
    }, 500);

    return () => clearTimeout(timer);
  }, [filters]);

  // Apply filters when debounced filters change
  useEffect(() => {
    const applyFilters = async () => {
      setLoading(true);
      setError(null);

      try {
        // Remove empty filters
        const cleanFilters = Object.fromEntries(
          Object.entries(debouncedFilters).filter(([_, value]) =>
            value !== '' && value !== null && value !== undefined
          )
        );

        const filterMethod = type === 'clients' ?
          searchService.filterClients.bind(searchService) :
          searchService.filterSuppliers.bind(searchService);

        const result = await filterMethod(cleanFilters);
        setData(result);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Erreur de filtrage');
      } finally {
        setLoading(false);
      }
    };

    applyFilters();
  }, [debouncedFilters, searchService, type]);

  const updateFilter = useCallback((key: keyof FilterState, value: any) => {
    setFilters(prev => ({
      ...prev,
      [key]: value,
      // Reset page when filters change
      ...(key !== 'page' && key !== 'per_page' ? { page: 1 } : {})
    }));
  }, []);

  const resetFilters = useCallback(() => {
    setFilters({
      search: '',
      type: '',
      sector: '',
      relation_type: '',
      created_from: '',
      created_to: '',
      sort_by: 'name',
      sort_order: 'asc',
      page: 1,
      per_page: 15
    });
  }, []);

  const getActiveFilterCount = useCallback(() => {
    return Object.entries(filters).filter(([key, value]) =>
      !['sort_by', 'sort_order', 'page', 'per_page'].includes(key) &&
      value !== '' && value !== null && value !== undefined
    ).length;
  }, [filters]);

  return {
    filters,
    updateFilter,
    resetFilters,
    data,
    loading,
    error,
    activeFilterCount: getActiveFilterCount()
  };
}

// Composant de filtrage avancé
function AdvancedFilterPanel({ type = 'clients' }: { type?: 'clients' | 'suppliers' }) {
  const searchService = new SearchService(getToken());
  const {
    filters,
    updateFilter,
    resetFilters,
    data,
    loading,
    error,
    activeFilterCount
  } = useAdvancedFilter(searchService, type);

  return (
    <div className="filter-panel">
      <div className="filter-header">
        <h3>Filtres</h3>
        <div className="filter-actions">
          {activeFilterCount > 0 && (
            <span className="filter-count">{activeFilterCount} filtre(s) actif(s)</span>
          )}
          <button onClick={resetFilters} className="reset-button">
            Réinitialiser
          </button>
        </div>
      </div>

      <div className="filter-form">
        <div className="filter-group">
          <label>Recherche</label>
          <input
            type="text"
            value={filters.search}
            onChange={(e) => updateFilter('search', e.target.value)}
            placeholder="Nom, email, téléphone..."
          />
        </div>

        <div className="filter-group">
          <label>Type</label>
          <select
            value={filters.type}
            onChange={(e) => updateFilter('type', e.target.value)}
          >
            <option value="">Tous les types</option>
            <option value="particulier">Particulier</option>
            <option value="entreprise">Entreprise</option>
          </select>
        </div>

        <div className="filter-group">
          <label>Secteur</label>
          <input
            type="text"
            value={filters.sector}
            onChange={(e) => updateFilter('sector', e.target.value)}
            placeholder="Ex: technologie"
          />
        </div>

        {type === 'suppliers' && (
          <div className="filter-group">
            <label>Type de relation</label>
            <select
              value={filters.relation_type}
              onChange={(e) => updateFilter('relation_type', e.target.value)}
            >
              <option value="">Toutes les relations</option>
              <option value="fournisseur">Fournisseur</option>
              <option value="client_et_fournisseur">Client et Fournisseur</option>
            </select>
          </div>
        )}

        <div className="filter-group">
          <label>Créé entre</label>
          <div className="date-range">
            <input
              type="date"
              value={filters.created_from}
              onChange={(e) => updateFilter('created_from', e.target.value)}
            />
            <input
              type="date"
              value={filters.created_to}
              onChange={(e) => updateFilter('created_to', e.target.value)}
            />
          </div>
        </div>

        <div className="filter-group">
          <label>Trier par</label>
          <div className="sort-controls">
            <select
              value={filters.sort_by}
              onChange={(e) => updateFilter('sort_by', e.target.value)}
            >
              <option value="name">Nom</option>
              <option value="created_at">Date de création</option>
              <option value="updated_at">Date de modification</option>
              <option value="email">Email</option>
              <option value="type">Type</option>
              <option value="sector">Secteur</option>
              {type === 'suppliers' && (
                <option value="relation_type">Type de relation</option>
              )}
            </select>
            <select
              value={filters.sort_order}
              onChange={(e) => updateFilter('sort_order', e.target.value as 'asc' | 'desc')}
            >
              <option value="asc">Croissant</option>
              <option value="desc">Décroissant</option>
            </select>
          </div>
        </div>
      </div>

      {/* Active filters display */}
      {activeFilterCount > 0 && (
        <div className="active-filters">
          <h4>Filtres actifs:</h4>
          <div className="filter-tags">
            {Object.entries(filters).map(([key, value]) => {
              if (!value || ['sort_by', 'sort_order', 'page', 'per_page'].includes(key)) return null;

              return (
                <div key={key} className="filter-tag">
                  <span>{key}: {value}</span>
                  <button onClick={() => updateFilter(key as keyof FilterState, '')}>×</button>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* Results */}
      <div className="filter-results">
        {loading && <div className="loading">Chargement...</div>}
        {error && <div className="error">{error}</div>}

        {data && (
          <>
            <div className="results-info">
              <span>{data.pagination.total_items} résultats</span>
              {data.total_without_filters > data.pagination.total_items && (
                <span> sur {data.total_without_filters} total</span>
              )}
            </div>

            <div className="results-list">
              {data[type]?.map((item: any) => (
                <div key={item.id} className="result-item">
                  <h4>{item.name}</h4>
                  <p>{item.email} • {item.type}</p>
                  {item.sector && <p>Secteur: {item.sector}</p>}
                </div>
              ))}
            </div>

            {/* Pagination */}
            <div className="pagination">
              <button
                disabled={data.pagination.current_page <= 1}
                onClick={() => updateFilter('page', data.pagination.current_page - 1)}
              >
                Précédent
              </button>

              <span>
                Page {data.pagination.current_page} sur {data.pagination.total_pages}
              </span>

              <button
                disabled={data.pagination.current_page >= data.pagination.total_pages}
                onClick={() => updateFilter('page', data.pagination.current_page + 1)}
              >
                Suivant
              </button>
            </div>
          </>
        )}
      </div>
    </div>
  );
}
```

### CSS pour styling

```css
/* Composant de recherche */
.search-box {
  position: relative;
  width: 100%;
  max-width: 400px;
}

.search-input {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 16px;
  transition: border-color 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.search-loading, .search-error {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  padding: 8px 16px;
  background: white;
  border: 1px solid #e0e0e0;
  border-top: none;
  border-radius: 0 0 8px 8px;
  z-index: 1000;
}

.search-error {
  background: #ffe6e6;
  color: #d32f2f;
}

.search-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #e0e0e0;
  border-top: none;
  border-radius: 0 0 8px 8px;
  max-height: 300px;
  overflow-y: auto;
  z-index: 1000;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.search-result-item {
  padding: 12px 16px;
  cursor: pointer;
  border-bottom: 1px solid #f0f0f0;
  transition: background-color 0.2s;
}

.search-result-item:hover {
  background-color: #f8f9fa;
}

.search-result-item:last-child {
  border-bottom: none;
}

.result-name {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 500;
  margin-bottom: 4px;
}

.result-name mark {
  background-color: #fff3cd;
  color: #856404;
  padding: 0 2px;
}

.result-score {
  font-size: 12px;
  color: #666;
  background: #e9ecef;
  padding: 2px 6px;
  border-radius: 12px;
}

.result-details {
  display: flex;
  gap: 12px;
  font-size: 14px;
  color: #666;
  margin-bottom: 4px;
}

.result-id {
  font-family: monospace;
  background: #f8f9fa;
  padding: 2px 4px;
  border-radius: 3px;
}

.result-matched-field {
  font-size: 12px;
  color: #28a745;
  font-style: italic;
}

/* Panel de filtrage */
.filter-panel {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 20px;
  margin: 20px 0;
}

.filter-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e0e0e0;
}

.filter-header h3 {
  margin: 0;
  color: #333;
}

.filter-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.filter-count {
  font-size: 14px;
  color: #007bff;
  background: #e3f2fd;
  padding: 4px 8px;
  border-radius: 4px;
}

.reset-button {
  background: #6c757d;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.2s;
}

.reset-button:hover {
  background: #5a6268;
}

.filter-form {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.filter-group label {
  font-weight: 500;
  color: #333;
  font-size: 14px;
}

.filter-group input,
.filter-group select {
  padding: 8px 12px;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.filter-group input:focus,
.filter-group select:focus {
  outline: none;
  border-color: #007bff;
}

.date-range {
  display: flex;
  gap: 8px;
}

.date-range input {
  flex: 1;
}

.sort-controls {
  display: flex;
  gap: 8px;
}

.sort-controls select {
  flex: 1;
}

.active-filters {
  margin-bottom: 20px;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 4px;
}

.active-filters h4 {
  margin: 0 0 12px 0;
  font-size: 14px;
  color: #333;
}

.filter-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.filter-tag {
  display: flex;
  align-items: center;
  background: #007bff;
  color: white;
  padding: 4px 8px;
  border-radius: 16px;
  font-size: 12px;
  gap: 8px;
}

.filter-tag button {
  background: rgba(255, 255, 255, 0.3);
  color: white;
  border: none;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}

.filter-tag button:hover {
  background: rgba(255, 255, 255, 0.5);
}

.filter-results {
  border-top: 1px solid #e0e0e0;
  padding-top: 20px;
}

.results-info {
  margin-bottom: 16px;
  font-size: 14px;
  color: #666;
  font-weight: 500;
}

.results-list {
  display: grid;
  gap: 12px;
  margin-bottom: 20px;
}

.result-item {
  padding: 16px;
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.result-item:hover {
  background: #e9ecef;
}

.result-item h4 {
  margin: 0 0 8px 0;
  color: #333;
}

.result-item p {
  margin: 4px 0;
  color: #666;
  font-size: 14px;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
}

.pagination button {
  background: #007bff;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.pagination button:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.pagination button:not(:disabled):hover {
  background: #0056b3;
}

.loading, .error {
  text-align: center;
  padding: 20px;
  font-style: italic;
}

.error {
  color: #d32f2f;
}
```

---

## Fonctionnalités avancées

### Recherche Fuzzy (Tolérance aux fautes)

Le système utilise l'algorithme **SOUNDEX** pour la recherche phonétique :

```sql
-- MySQL query example
SELECT * FROM clients
WHERE SOUNDEX(name) = SOUNDEX('Enterprize')
-- Trouvera "Entreprise"
```

### Performance et optimisation

#### Index recommandés

```sql
-- Optimisation MySQL pour recherche
CREATE INDEX idx_clients_search ON clients (name, email, phone, siret);
CREATE INDEX idx_suppliers_search ON suppliers (name, email, phone, siret);
CREATE INDEX idx_clients_type_sector ON clients (type, sector);
CREATE INDEX idx_suppliers_type_relation ON suppliers (type, relation_type);
CREATE INDEX idx_clients_dates ON clients (created_at, updated_at);
CREATE INDEX idx_suppliers_dates ON suppliers (created_at, updated_at);
```

#### Pagination optimisée

```javascript
// Utilisation de curseurs pour grandes datasets
const paginateLargeDataset = async (lastId: number, limit: number = 20) => {
  const params = new URLSearchParams({
    cursor: lastId.toString(),
    limit: limit.toString()
  });

  return fetch(`/api/v1/clients?${params}`);
};
```

### Mise en cache côté client

```typescript
class CachedSearchService extends SearchService {
  private cache = new Map<string, { data: any; timestamp: number }>();
  private cacheTimeout = 5 * 60 * 1000; // 5 minutes

  private getCacheKey(method: string, params: any): string {
    return `${method}_${JSON.stringify(params)}`;
  }

  private isValidCache(timestamp: number): boolean {
    return Date.now() - timestamp < this.cacheTimeout;
  }

  async filterClients(filters: FilterOptions): Promise<any> {
    const cacheKey = this.getCacheKey('filterClients', filters);
    const cached = this.cache.get(cacheKey);

    if (cached && this.isValidCache(cached.timestamp)) {
      return cached.data;
    }

    const result = await super.filterClients(filters);

    this.cache.set(cacheKey, {
      data: result,
      timestamp: Date.now()
    });

    return result;
  }

  clearCache(): void {
    this.cache.clear();
  }
}
```

---

## Gestion des erreurs

### Codes d'erreur courants

- `200` - Succès
- `422` - Validation échouée (terme de recherche trop court)
- `401` - Non authentifié
- `429` - Trop de requêtes (rate limiting)
- `500` - Erreur serveur

### Gestion robuste des erreurs

```typescript
async function safeSearch(searchService: SearchService, query: string) {
  try {
    return await searchService.searchClients(query);
  } catch (error) {
    if (error instanceof Error) {
      switch (error.message) {
        case 'Le terme de recherche doit contenir au moins 2 caractères':
          // Terme trop court, ne pas afficher d'erreur
          return [];

        case 'Unauthenticated':
          // Rediriger vers login
          redirectToLogin();
          return [];

        default:
          // Erreur générique
          showNotification('Erreur de recherche', 'error');
          return [];
      }
    }

    // Erreur réseau ou autre
    showNotification('Erreur de connexion', 'error');
    return [];
  }
}
```

---

## Documentation Swagger

La documentation interactive complète est disponible sur :
**http://localhost:8000/api/documentation**

Endpoints documentés :
- `GET /v1/clients/search` - Recherche rapide clients
- `GET /v1/suppliers/search` - Recherche rapide fournisseurs
- `GET /v1/clients` - Filtrage avancé clients
- `GET /v1/suppliers` - Filtrage avancé fournisseurs

---

## Bonnes pratiques

### Performance côté frontend

1. **Debouncing** : 300-500ms pour recherche temps réel
2. **Limite de résultats** : Max 50 pour autocomplétion
3. **Cache local** : 5 minutes pour filtres identiques
4. **Pagination virtuelle** : Pour grandes listes

### UX recommandées

1. **Feedback visuel** : Loading states et scores de pertinence
2. **Highlighting** : Mise en évidence des termes trouvés
3. **Suggestions** : Historique des recherches récentes
4. **Filtres persistants** : Sauvegarde des préférences utilisateur
5. **Raccourcis clavier** : Navigation rapide dans les résultats

### Sécurité

1. **Rate limiting** : Limitation des requêtes par utilisateur
2. **Validation stricte** : Tous les paramètres validés côté serveur
3. **Échappement SQL** : Protection contre injection SQL
4. **Logs d'audit** : Traçabilité des recherches sensibles

Le système de recherche et filtrage TargetDesk offre une expérience utilisateur fluide et performante pour gérer de grandes bases de clients et fournisseurs. 🔍✨