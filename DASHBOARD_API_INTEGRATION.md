# Dashboard API Integration Guide

## Vue d'ensemble

Cette documentation décrit l'intégration des endpoints de dashboard commercial et personnel pour le CRM TargetDesk. Les dashboards fournissent des métriques en temps réel, des graphiques d'évolution, et des widgets interactifs pour le pilotage de l'activité commerciale.

## Base URL

```
http://localhost:8000/api/v1
```

## Authentification

Tous les endpoints nécessitent une authentification Bearer Token via Laravel Sanctum :

```typescript
const headers = {
  'Authorization': 'Bearer YOUR_ACCESS_TOKEN',
  'Accept': 'application/json',
  'Content-Type': 'application/json'
};
```

## 📊 Dashboard Commercial (Manager)

### 1. Vue d'ensemble commerciale

**Endpoint :** `GET /dashboard/commercial/overview`

**Description :** Récupère les métriques clés pour le tableau de bord manager.

**Paramètres :**
- `period` (optionnel) : "month", "quarter", "year", "custom"
- `start_date` (optionnel) : Format Y-m-d pour période custom
- `end_date` (optionnel) : Format Y-m-d pour période custom

**Exemple de requête :**
```typescript
const getCommercialOverview = async (period = 'month') => {
  const response = await fetch(`/api/v1/dashboard/commercial/overview?period=${period}`, {
    headers
  });
  return response.json();
};
```

**Réponse :**
```json
{
  "success": true,
  "message": "Dashboard overview retrieved successfully",
  "data": {
    "metrics": {
      "total_active_clients": 150,
      "total_prospects": 45,
      "new_clients_this_period": 12,
      "total_suppliers": 25
    },
    "period_info": {
      "period": "month",
      "start_date": "2026-01-01",
      "end_date": "2026-01-31"
    }
  }
}
```

### 2. Statistiques détaillées

**Endpoint :** `GET /dashboard/commercial/stats`

**Description :** Statistiques de répartition pour graphiques (camemberts, barres).

**Exemple de requête :**
```typescript
const getCommercialStats = async () => {
  const response = await fetch('/api/v1/dashboard/commercial/stats', {
    headers
  });
  return response.json();
};
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "clients_by_status": [
      {"status": "active", "count": 120, "percentage": 80.5},
      {"status": "inactive", "count": 30, "percentage": 19.5}
    ],
    "clients_by_type": [
      {"type": "entreprise", "count": 85, "percentage": 60.7},
      {"type": "particulier", "count": 55, "percentage": 39.3}
    ],
    "clients_by_sector": [
      {"sector": "Technology", "count": 25, "percentage": 17.8},
      {"sector": "Commerce", "count": 20, "percentage": 14.3}
    ]
  }
}
```

### 3. Évolution des clients

**Endpoint :** `GET /dashboard/commercial/clients-evolution`

**Description :** Données d'évolution temporelle pour graphiques linéaires.

**Paramètres :**
- `period` : "month", "quarter", "year"

**Exemple de requête :**
```typescript
const getClientsEvolution = async (period = 'month') => {
  const response = await fetch(`/api/v1/dashboard/commercial/clients-evolution?period=${period}`, {
    headers
  });
  return response.json();
};
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "evolution_data": [
      {
        "date": "2026-01-01",
        "period": "2026-01",
        "total_clients": 145,
        "new_clients": 5,
        "active_clients": 140
      }
    ],
    "period": "month",
    "date_range": {
      "start": "2025-02-01",
      "end": "2026-01-31"
    }
  }
}
```

### 4. Interactions récentes

**Endpoint :** `GET /dashboard/commercial/interactions/recent`

**Description :** Dernières interactions sur l'ensemble du CRM.

**Paramètres :**
- `limit` (optionnel) : Nombre max d'interactions (défaut: 10, max: 50)

**Exemple de requête :**
```typescript
const getRecentInteractions = async (limit = 10) => {
  const response = await fetch(`/api/v1/dashboard/commercial/interactions/recent?limit=${limit}`, {
    headers
  });
  return response.json();
};
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "interactions": [
      {
        "id": 1,
        "type": "call",
        "client_id": 25,
        "client_name": "Entreprise ACME",
        "subject": "Follow-up call",
        "summary": "Client interested in our services...",
        "created_at": "2026-01-26T10:30:00Z",
        "created_by": "John Doe"
      }
    ],
    "total_found": 5
  }
}
```

### 5. Clients inactifs

**Endpoint :** `GET /dashboard/commercial/clients/inactive`

**Description :** Clients sans interactions récentes pour actions de relance.

**Paramètres :**
- `days` (optionnel) : Nombre de jours sans interaction (défaut: 30)
- `limit` (optionnel) : Nombre max de clients (défaut: 20, max: 100)

**Exemple de requête :**
```typescript
const getInactiveClients = async (days = 30, limit = 20) => {
  const response = await fetch(`/api/v1/dashboard/commercial/clients/inactive?days=${days}&limit=${limit}`, {
    headers
  });
  return response.json();
};
```

## 👤 Dashboard Personnel (Commercial)

### 1. Vue d'ensemble personnelle

**Endpoint :** `GET /dashboard/personal/overview`

**Description :** Métriques personnelles filtrées sur l'utilisateur connecté.

**Exemple de requête :**
```typescript
const getPersonalOverview = async (period = 'month') => {
  const response = await fetch(`/api/v1/dashboard/personal/overview?period=${period}`, {
    headers
  });
  return response.json();
};
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "metrics": {
      "my_active_clients": 25,
      "my_prospects": 8,
      "my_appointments_upcoming": 5,
      "my_overdue_tasks": 3,
      "my_interactions_this_period": 15
    },
    "period_info": {
      "period": "month",
      "start_date": "2026-01-01",
      "end_date": "2026-01-31"
    },
    "user_id": 1
  }
}
```

### 2. Portfolio personnel

**Endpoint :** `GET /dashboard/personal/portfolio`

**Description :** Évolution du portefeuille et métriques de performance.

**Exemple de requête :**
```typescript
const getPersonalPortfolio = async (period = 'month') => {
  const response = await fetch(`/api/v1/dashboard/personal/portfolio?period=${period}`, {
    headers
  });
  return response.json();
};
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "portfolio_evolution": [
      {
        "date": "2026-01-01",
        "period": "2026-01",
        "clients_count": 20,
        "new_clients": 2,
        "interactions_count": 15
      }
    ],
    "performance_metrics": {
      "avg_interactions_per_client": 2.5,
      "most_active_day": "Tuesday",
      "conversion_rate": 75.5,
      "total_clients": 20,
      "clients_with_interactions": 15
    }
  }
}
```

### 3. Tâches du jour

**Endpoint :** `GET /dashboard/personal/tasks/today`

**Description :** Widget "À faire aujourd'hui" avec RDV et follow-ups.

**Exemple de requête :**
```typescript
const getTodaysTasks = async () => {
  const response = await fetch('/api/v1/dashboard/personal/tasks/today', {
    headers
  });
  return response.json();
};
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "appointments_today": [
      {
        "id": 1,
        "client_id": 25,
        "client_name": "Entreprise ACME",
        "subject": "Product demo",
        "scheduled_at": "2026-01-26T14:00:00Z",
        "status": "scheduled",
        "priority": "high",
        "location": "Bureau client"
      }
    ],
    "follow_ups_due": [
      {
        "id": 2,
        "client_id": 30,
        "client_name": "Tech Solutions",
        "subject": "Follow up on proposal",
        "follow_up_date": "2026-01-26T09:00:00Z",
        "days_overdue": 2,
        "priority": "high"
      }
    ],
    "summary": {
      "total_appointments_today": 3,
      "total_follow_ups_due": 2,
      "urgent_tasks": 5,
      "completion_rate": 66.7
    }
  }
}
```

### 4. RDV à venir

**Endpoint :** `GET /dashboard/personal/appointments/upcoming`

**Description :** Rendez-vous programmés dans les prochains jours.

**Paramètres :**
- `days` (optionnel) : Nombre de jours à anticiper (défaut: 7, max: 30)
- `limit` (optionnel) : Nombre max de RDV (défaut: 10, max: 50)

**Exemple de requête :**
```typescript
const getUpcomingAppointments = async (days = 7, limit = 10) => {
  const response = await fetch(`/api/v1/dashboard/personal/appointments/upcoming?days=${days}&limit=${limit}`, {
    headers
  });
  return response.json();
};
```

### 5. Interactions récentes personnelles

**Endpoint :** `GET /dashboard/personal/interactions/recent`

**Description :** Dernières interactions créées par l'utilisateur.

**Exemple de requête :**
```typescript
const getMyRecentInteractions = async (limit = 10) => {
  const response = await fetch(`/api/v1/dashboard/personal/interactions/recent?limit=${limit}`, {
    headers
  });
  return response.json();
};
```

## 🎨 Intégration Frontend

### React Hook personnalisé

```typescript
import { useState, useEffect } from 'react';

interface DashboardData {
  metrics: any;
  loading: boolean;
  error: string | null;
}

export const useDashboard = (type: 'commercial' | 'personal', endpoint: string) => {
  const [data, setData] = useState<DashboardData>({
    metrics: null,
    loading: true,
    error: null
  });

  const fetchData = async () => {
    try {
      setData(prev => ({ ...prev, loading: true, error: null }));

      const response = await fetch(`/api/v1/dashboard/${type}/${endpoint}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Accept': 'application/json'
        }
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();

      if (result.success) {
        setData({
          metrics: result.data,
          loading: false,
          error: null
        });
      } else {
        throw new Error(result.message || 'Error fetching dashboard data');
      }
    } catch (error) {
      setData(prev => ({
        ...prev,
        loading: false,
        error: error instanceof Error ? error.message : 'Unknown error'
      }));
    }
  };

  useEffect(() => {
    fetchData();
  }, [type, endpoint]);

  return { ...data, refresh: fetchData };
};
```

### Composant Dashboard Commercial

```typescript
import React from 'react';
import { useDashboard } from './hooks/useDashboard';

interface MetricCardProps {
  title: string;
  value: number;
  onClick?: () => void;
}

const MetricCard: React.FC<MetricCardProps> = ({ title, value, onClick }) => (
  <div
    className="bg-white p-6 rounded-lg shadow cursor-pointer hover:shadow-lg transition-shadow"
    onClick={onClick}
  >
    <h3 className="text-sm font-medium text-gray-500">{title}</h3>
    <p className="text-2xl font-semibold text-gray-900">{value}</p>
  </div>
);

const CommercialDashboard: React.FC = () => {
  const { metrics, loading, error, refresh } = useDashboard('commercial', 'overview');

  if (loading) return <div className="animate-pulse">Loading...</div>;
  if (error) return <div className="text-red-500">Error: {error}</div>;

  const handleClientClick = () => {
    // Navigation vers la liste des clients
    window.location.href = '/clients';
  };

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <MetricCard
          title="Clients Actifs"
          value={metrics?.metrics?.total_active_clients || 0}
          onClick={handleClientClick}
        />
        <MetricCard
          title="Prospects"
          value={metrics?.metrics?.total_prospects || 0}
          onClick={() => window.location.href = '/clients?filter=prospects'}
        />
        <MetricCard
          title="Nouveaux ce mois"
          value={metrics?.metrics?.new_clients_this_period || 0}
          onClick={() => window.location.href = '/clients?filter=new'}
        />
        <MetricCard
          title="Fournisseurs"
          value={metrics?.metrics?.total_suppliers || 0}
          onClick={() => window.location.href = '/suppliers'}
        />
      </div>

      <button
        onClick={refresh}
        className="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
      >
        Actualiser
      </button>
    </div>
  );
};

export default CommercialDashboard;
```

### Composant Widget Tâches du Jour

```typescript
import React from 'react';
import { useDashboard } from './hooks/useDashboard';

const TodaysTasks: React.FC = () => {
  const { metrics, loading, error } = useDashboard('personal', 'tasks/today');

  if (loading) return <div>Chargement...</div>;
  if (error) return <div>Erreur: {error}</div>;

  const { appointments_today, follow_ups_due, summary } = metrics;

  return (
    <div className="bg-white rounded-lg shadow p-6">
      <h2 className="text-lg font-semibold mb-4">À faire aujourd'hui</h2>

      <div className="space-y-4">
        {/* RDV du jour */}
        <div>
          <h3 className="font-medium text-gray-700">Rendez-vous ({appointments_today?.length || 0})</h3>
          {appointments_today?.map((apt: any) => (
            <div key={apt.id} className="flex justify-between items-center py-2 border-b">
              <div>
                <span className="font-medium">{apt.client_name}</span>
                <p className="text-sm text-gray-500">{apt.subject}</p>
              </div>
              <span className="text-sm text-gray-400">
                {new Date(apt.scheduled_at).toLocaleTimeString()}
              </span>
            </div>
          ))}
        </div>

        {/* Follow-ups en retard */}
        <div>
          <h3 className="font-medium text-gray-700">Relances en retard ({follow_ups_due?.length || 0})</h3>
          {follow_ups_due?.map((followUp: any) => (
            <div key={followUp.id} className="flex justify-between items-center py-2 border-b">
              <div>
                <span className="font-medium">{followUp.client_name}</span>
                <p className="text-sm text-gray-500">{followUp.subject}</p>
              </div>
              <span className="text-sm text-red-500">
                {followUp.days_overdue} jour(s) de retard
              </span>
            </div>
          ))}
        </div>

        {/* Résumé */}
        <div className="bg-gray-50 p-3 rounded">
          <div className="grid grid-cols-2 gap-4 text-sm">
            <div>Tâches urgentes: <span className="font-semibold">{summary?.urgent_tasks || 0}</span></div>
            <div>Taux completion: <span className="font-semibold">{summary?.completion_rate || 0}%</span></div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default TodaysTasks;
```

## 📈 Intégration avec Chart.js

### Graphique d'évolution des clients

```typescript
import React, { useEffect, useRef } from 'react';
import Chart from 'chart.js/auto';
import { useDashboard } from './hooks/useDashboard';

const ClientsEvolutionChart: React.FC = () => {
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const chartRef = useRef<Chart | null>(null);
  const { metrics, loading } = useDashboard('commercial', 'clients-evolution?period=month');

  useEffect(() => {
    if (!metrics?.evolution_data || loading || !canvasRef.current) return;

    // Détruire le graphique existant
    if (chartRef.current) {
      chartRef.current.destroy();
    }

    const ctx = canvasRef.current.getContext('2d');
    if (!ctx) return;

    chartRef.current = new Chart(ctx, {
      type: 'line',
      data: {
        labels: metrics.evolution_data.map((item: any) => item.period),
        datasets: [
          {
            label: 'Total Clients',
            data: metrics.evolution_data.map((item: any) => item.total_clients),
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.1
          },
          {
            label: 'Nouveaux Clients',
            data: metrics.evolution_data.map((item: any) => item.new_clients),
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            tension: 0.1
          }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          title: {
            display: true,
            text: 'Évolution des Clients'
          }
        },
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });

    return () => {
      if (chartRef.current) {
        chartRef.current.destroy();
      }
    };
  }, [metrics, loading]);

  if (loading) return <div>Chargement du graphique...</div>;

  return (
    <div className="bg-white p-6 rounded-lg shadow">
      <canvas ref={canvasRef} />
    </div>
  );
};

export default ClientsEvolutionChart;
```

## ⚙️ Configuration et Optimisation

### Refresh automatique

```typescript
// Hook pour refresh automatique
export const useAutoRefresh = (callback: () => void, interval: number = 300000) => {
  useEffect(() => {
    const id = setInterval(callback, interval); // 5 minutes par défaut
    return () => clearInterval(id);
  }, [callback, interval]);
};

// Utilisation
const Dashboard: React.FC = () => {
  const { refresh } = useDashboard('commercial', 'overview');

  useAutoRefresh(refresh, 300000); // Refresh toutes les 5 minutes

  return <div>...</div>;
};
```

### Cache local avec React Query

```typescript
import { useQuery } from '@tanstack/react-query';

export const useDashboardQuery = (type: string, endpoint: string, options = {}) => {
  return useQuery({
    queryKey: ['dashboard', type, endpoint],
    queryFn: () => fetchDashboardData(type, endpoint),
    staleTime: 5 * 60 * 1000, // 5 minutes
    cacheTime: 10 * 60 * 1000, // 10 minutes
    refetchInterval: 5 * 60 * 1000, // Refresh auto toutes les 5 minutes
    ...options
  });
};
```

## 🎯 Personnalisation des Widgets

### Configuration des widgets

```typescript
interface WidgetConfig {
  id: string;
  title: string;
  endpoint: string;
  type: 'metric' | 'chart' | 'list';
  size: 'small' | 'medium' | 'large';
  refreshInterval?: number;
}

const defaultWidgets: WidgetConfig[] = [
  {
    id: 'overview',
    title: 'Vue d\'ensemble',
    endpoint: 'overview',
    type: 'metric',
    size: 'large'
  },
  {
    id: 'evolution',
    title: 'Évolution clients',
    endpoint: 'clients-evolution',
    type: 'chart',
    size: 'large'
  },
  {
    id: 'recent-interactions',
    title: 'Interactions récentes',
    endpoint: 'interactions/recent',
    type: 'list',
    size: 'medium'
  }
];
```

## 🔍 Gestion d'erreurs

```typescript
const ErrorBoundary: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  return (
    <div className="error-boundary">
      {children}
    </div>
  );
};

// Composant d'erreur personnalisé
const DashboardError: React.FC<{ error: string; onRetry: () => void }> = ({ error, onRetry }) => (
  <div className="bg-red-50 border border-red-200 rounded-lg p-4">
    <div className="flex items-center">
      <div className="text-red-800">
        <h3 className="text-sm font-medium">Erreur de chargement</h3>
        <p className="text-sm mt-1">{error}</p>
      </div>
      <button
        onClick={onRetry}
        className="ml-auto bg-red-100 text-red-800 px-3 py-1 rounded text-sm hover:bg-red-200"
      >
        Réessayer
      </button>
    </div>
  </div>
);
```

## 📱 Responsive Design

```css
/* CSS pour responsive dashboard */
.dashboard-grid {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
}

.widget {
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  padding: 1.5rem;
}

.widget.large {
  grid-column: span 2;
}

@media (max-width: 768px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }

  .widget.large {
    grid-column: span 1;
  }
}
```

## 🚀 Performance

### Optimisations recommandées

1. **Lazy Loading** : Charger les widgets à la demande
2. **Memoization** : Utiliser React.memo pour les composants
3. **Debouncing** : Pour les filtres en temps réel
4. **Cache** : Utiliser React Query pour la mise en cache
5. **Compression** : Gzip pour les réponses API

### Monitoring

```typescript
// Service de monitoring des performances
class DashboardMetrics {
  static trackAPICall(endpoint: string, duration: number) {
    console.log(`API Call: ${endpoint} - ${duration}ms`);
    // Envoyer vers service de monitoring
  }

  static trackUserInteraction(action: string) {
    console.log(`User Action: ${action}`);
    // Analytics
  }
}
```

## 🔐 Sécurité

### Validation côté client

```typescript
const validateToken = () => {
  const token = localStorage.getItem('token');
  if (!token) {
    window.location.href = '/login';
    return false;
  }
  return true;
};

const secureRequest = async (url: string, options = {}) => {
  if (!validateToken()) return;

  return fetch(url, {
    ...options,
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('token')}`,
      ...options.headers
    }
  });
};
```

---

Cette documentation fournit tous les éléments nécessaires pour intégrer efficacement les dashboards dans votre application frontend avec une approche modulaire et performante.