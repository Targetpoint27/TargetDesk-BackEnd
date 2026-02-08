# Ajustements Client Angular - Déconnexion Automatique

## 🎯 Objectif
Gérer la déconnexion automatique des utilisateurs désactivés côté Angular lorsque le backend retourne une erreur 403 avec le code `ACCOUNT_DEACTIVATED`.

## 🔧 Modifications Requises

### 1. Service d'Authentification

```typescript
// auth.service.ts
export class AuthService {

  handleAccountDeactivated(): void {
    // Nettoyer les données locales
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    sessionStorage.clear();

    // Rediriger vers login
    this.router.navigate(['/login']);

    // Afficher message d'erreur
    this.toastr.error('Votre compte a été désactivé. Veuillez contacter l\'administrateur.');
  }

  logout(): void {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    sessionStorage.clear();
    this.router.navigate(['/login']);
  }
}
```

### 2. Intercepteur HTTP Global

```typescript
// auth.interceptor.ts
@Injectable()
export class AuthInterceptor implements HttpInterceptor {

  constructor(
    private authService: AuthService,
    private router: Router,
    private toastr: ToastrService
  ) {}

  intercept(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    return next.handle(req).pipe(
      catchError((error: HttpErrorResponse) => {
        // Gestion spécifique pour utilisateur désactivé
        if (error.status === 403 && error.error?.error_code === 'ACCOUNT_DEACTIVATED') {
          this.authService.handleAccountDeactivated();
          return throwError(() => error);
        }

        // Gestion générale des erreurs d'authentification
        if (error.status === 401) {
          this.authService.logout();
          return throwError(() => error);
        }

        return throwError(() => error);
      })
    );
  }
}
```

### 3. Composant de Connexion

```typescript
// login.component.ts
export class LoginComponent {

  onSubmit(): void {
    this.authService.login(this.loginForm.value).subscribe({
      next: (response) => {
        // Connexion réussie
        localStorage.setItem('token', response.token);
        localStorage.setItem('user', JSON.stringify(response.user));
        this.router.navigate(['/dashboard']);
      },
      error: (error) => {
        if (error.status === 403 && error.error?.error_code === 'ACCOUNT_INACTIVE') {
          this.toastr.error('Votre compte n\'est pas actif. Veuillez contacter l\'administrateur.');
        } else {
          this.toastr.error('Identifiants incorrects');
        }
      }
    });
  }
}
```

### 4. Service de Notification (optionnel)

```typescript
// notification.service.ts
export class NotificationService {

  showAccountDeactivatedMessage(): void {
    // Message persistant jusqu'à confirmation utilisateur
    Swal.fire({
      title: 'Compte Désactivé',
      text: 'Votre compte a été désactivé par un administrateur. Vous avez été automatiquement déconnecté.',
      icon: 'warning',
      confirmButtonText: 'Compris',
      allowOutsideClick: false,
      allowEscapeKey: false
    });
  }
}
```

### 5. Guard de Route (optionnel)

```typescript
// auth.guard.ts
@Injectable()
export class AuthGuard implements CanActivate {

  canActivate(): Observable<boolean> | boolean {
    const token = localStorage.getItem('token');

    if (!token) {
      this.router.navigate(['/login']);
      return false;
    }

    // Vérification périodique du statut
    return this.authService.checkUserStatus().pipe(
      map(() => true),
      catchError((error) => {
        if (error.status === 403 && error.error?.error_code === 'ACCOUNT_DEACTIVATED') {
          this.authService.handleAccountDeactivated();
        }
        return of(false);
      })
    );
  }
}
```

## 🚀 Vérification Périodique (Optionnel)

Pour une vérification en temps réel sans attendre une requête :

```typescript
// app.component.ts
export class AppComponent implements OnInit {

  ngOnInit(): void {
    if (this.authService.isAuthenticated()) {
      // Vérification toutes les 5 minutes
      setInterval(() => {
        this.checkUserStatus();
      }, 300000);
    }
  }

  private checkUserStatus(): void {
    this.authService.checkUserStatus().subscribe({
      error: (error) => {
        if (error.status === 403 && error.error?.error_code === 'ACCOUNT_DEACTIVATED') {
          this.authService.handleAccountDeactivated();
        }
      }
    });
  }
}
```

## 📋 Codes d'Erreur à Gérer

| Code | Statut | Message | Action |
|------|--------|---------|---------|
| `ACCOUNT_DEACTIVATED` | 403 | Compte désactivé pendant la session | Déconnexion automatique |
| `ACCOUNT_INACTIVE` | 403 | Compte inactif à la connexion | Blocage de la connexion |
| Token expiré | 401 | Token non valide | Redirection vers login |

## ✅ Points Clés

1. **Gestion immédiate** : L'intercepteur gère automatiquement toutes les réponses 403
2. **Nettoyage complet** : Suppression de toutes les données locales d'authentification
3. **Message utilisateur** : Information claire sur la désactivation du compte
4. **Prévention des boucles** : Éviter les tentatives de reconnexion automatique
5. **UX cohérente** : Messages d'erreur uniformes dans toute l'application

## 🔧 Installation

1. Implementer l'intercepteur dans `app.module.ts`
2. Ajouter les méthodes au service d'authentification existant
3. Tester la gestion d'erreur avec un compte désactivé
4. Vérifier la redirection et le nettoyage des données