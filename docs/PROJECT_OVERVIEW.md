# MiniBnB - Documentation Technique Complète

## Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Architecture du projet](#architecture-du-projet)
3. [Stack technique](#stack-technique)
4. [Backend - API Laravel](#backend---api-laravel)
5. [Frontend - SPA Vue](#frontend---spa-vue)
6. [Base de données](#base-de-données)
7. [Authentification](#authentification)
8. [Fonctionnalités métier](#fonctionnalités-métier)
9. [Infrastructure](#infrastructure)
10. [Tests](#tests)
11. [Commandes de développement](#commandes-de-développement)

---

## Vue d'ensemble

**MiniBnB** est une application web full-stack de type Airbnb développée dans le cadre d'un TP. Elle permet la gestion d'**annonces de logements**, de **réservations**, d'une **messagerie** entre hôtes et voyageurs, et d'un système de **délégation via co-hôtes**.

### Objectifs du projet

- SPA (Single Page Application) obligatoire
- API REST JSON avec bonnes pratiques HTTP
- Tests automatisés sur toutes les features
- Documentation d'API
- Schéma d'architecture
- Soutenance prévue le 10/02

---

## Architecture du projet

Le projet est organisé en **monorepo** :

```
fakebnb/
├── apps/
│   ├── api/          # Laravel 12 REST API
│   └── app/          # Vue 3 SPA
├── docs/             # Documentation
│   ├── architechture.md
│   ├── features.md
│   └── PROJECT_OVERVIEW.md (ce fichier)
├── infra/            # Docker, scripts, déploiement
├── AGENTS.md         # Guidelines pour agents IA
└── Makefile          # Raccourcis Docker
```

### Schéma d'architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                           NAVIGATEUR                                │
│  ┌────────────────────────────────────────────────────────────────┐ │
│  │                      Vue 3 SPA (port 5173)                     │ │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌───────────────────┐  │ │
│  │  │ Vue      │ │ Pinia    │ │ Services │ │ Laravel Echo      │  │ │
│  │  │ Router   │ │ Stores   │ │ API      │ │ (WebSockets)      │  │ │
│  │  └──────────┘ └──────────┘ └──────────┘ └───────────────────┘  │ │
│  └────────────────────────────────────────────────────────────────┘ │
└───────────────────────────────┬─────────────────────────────────────┘
                                │ HTTP / WebSocket
                                ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         BACKEND SERVICES                            │
│  ┌─────────────────────┐  ┌─────────────────────┐  ┌─────────────┐  │
│  │  Laravel API        │  │  Laravel Reverb     │  │  Queue      │  │
│  │  (port 8989)        │  │  (port 8080)        │  │  Worker     │  │
│  │                     │  │  WebSocket Server   │  │             │  │
│  │  - Controllers      │  │                     │  │  Jobs       │  │
│  │  - Services         │  │  - Notifications    │  │  Emails     │  │
│  │  - Policies         │  │  - Messages RT      │  │             │  │
│  └─────────┬───────────┘  └─────────────────────┘  └─────────────┘  │
│            │                                                        │
│            ▼                                                        │
│  ┌─────────────────────┐  ┌─────────────────────┐  ┌─────────────┐  │
│  │     SQLite          │  │      Redis          │  │   Mailpit   │  │
│  │     Database        │  │      Cache          │  │   (dev)     │  │
│  └─────────────────────┘  └─────────────────────┘  └─────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Stack technique

### Backend (Laravel)

| Technologie | Version | Usage |
|-------------|---------|-------|
| PHP | 8.4 | Langage serveur |
| Laravel | 12 | Framework PHP |
| Laravel Reverb | - | WebSockets temps réel |
| Pest | 4 | Framework de tests |
| Laravel Pint | 1 | Formateur de code |
| SQLite | - | Base de données (dev) |

### Frontend (Vue)

| Technologie | Version | Usage |
|-------------|---------|-------|
| Vue.js | 3.5 | Framework JavaScript |
| TypeScript | 5.9 | Typage statique |
| Vite | 7 | Bundler / Dev server |
| Vue Router | 4 | Routing SPA |
| Pinia | 3 | State management |
| Tailwind CSS | 4 | Styles utilitaires |
| MapLibre GL | 5 | Cartographie |
| Laravel Echo | 2 | Client WebSocket |
| Vitest | 4 | Tests unitaires |

### Infrastructure

| Service | Usage |
|---------|-------|
| Docker Compose | Orchestration containers |
| FrankenPHP | Serveur PHP performant |
| Nginx | Serveur web pour SPA |
| Redis | Cache et sessions |
| Mailpit | Serveur mail de test |

---

## Backend - API Laravel

### Structure des dossiers

```
apps/api/
├── app/
│   ├── Events/                  # Événements broadcast
│   │   └── BookingUpdated.php
│   ├── Http/
│   │   ├── Controllers/Api/V1/  # Contrôleurs versionnés
│   │   │   ├── AuthController.php
│   │   │   ├── BookingController.php
│   │   │   ├── CohostController.php
│   │   │   ├── ConversationController.php
│   │   │   ├── ListingController.php
│   │   │   ├── ListingImageController.php
│   │   │   ├── MeController.php
│   │   │   ├── MessageController.php
│   │   │   ├── NotificationController.php
│   │   │   └── PaymentController.php
│   │   ├── Middleware/
│   │   │   ├── ApiTokenAuth.php
│   │   │   └── OptionalApiTokenAuth.php
│   │   ├── Requests/            # Validation FormRequest
│   │   └── Resources/           # Transformateurs API
│   ├── Models/                  # Modèles Eloquent
│   ├── Notifications/           # Notifications email/DB
│   ├── Policies/                # Politiques d'autorisation
│   └── Services/                # Logique métier
├── database/
│   ├── factories/               # Factories pour tests
│   ├── migrations/              # Migrations DB
│   └── seeders/                 # Seeders
├── routes/
│   ├── api.php                  # Routes API
│   └── channels.php             # Canaux broadcast
└── tests/
    └── Feature/                 # Tests fonctionnels
```

### Modèles et relations

```
┌─────────────────────────────────────────────────────────────────────┐
│                           MODÈLES                                   │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌──────────┐                                                       │
│  │   User   │◄────────────────────────────────────────────┐         │
│  └────┬─────┘                                             │         │
│       │ hasMany                                           │         │
│       ▼                                                   │         │
│  ┌──────────┐    hasMany    ┌───────────┐    hasMany     │         │
│  │ Listing  │──────────────►│  Booking  │◄───────────────┘         │
│  └────┬─────┘               └─────┬─────┘   (guest)                │
│       │                           │                                 │
│       │ hasMany                   │ hasOne                          │
│       ▼                           ▼                                 │
│  ┌──────────────┐           ┌───────────┐                          │
│  │ListingImage  │           │  Payment  │                          │
│  └──────────────┘           └───────────┘                          │
│       │                                                             │
│       │ hasMany                                                     │
│       ▼                                                             │
│  ┌──────────────┐    hasMany    ┌───────────┐                      │
│  │ Conversation │──────────────►│  Message  │                      │
│  └──────────────┘               └───────────┘                      │
│       │                                                             │
│       │ hasMany                                                     │
│       ▼                                                             │
│  ┌──────────┐                                                       │
│  │  Cohost  │ (délégation host → cohost sur listing)               │
│  └──────────┘                                                       │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

#### Détail des modèles

| Modèle | Table | Attributs principaux | Relations |
|--------|-------|---------------------|-----------|
| **User** | `users` | `name`, `email`, `password`, `address`, `profile_photo_path` | hasMany: listings, bookings, conversations, messages, cohosts, apiTokens |
| **Listing** | `listings` | `host_user_id`, `title`, `description`, `city`, `address`, `full_address`, `latitude`, `longitude`, `guest_capacity`, `price_per_night`, `rules`, `amenities` (JSON) | belongsTo: host (User), hasMany: bookings, conversations, images, cohosts |
| **Booking** | `bookings` | `listing_id`, `guest_user_id`, `start_date`, `end_date`, `status` | belongsTo: listing, guest (User), hasOne: payment |
| **Payment** | `payments` | `booking_id`, `guest_user_id`, `host_user_id`, `amount_total`, `amount_base`, `amount_vat`, `amount_service`, `commission_amount`, `payout_amount`, `status`, timestamps | belongsTo: booking, guest, host |
| **Conversation** | `conversations` | `listing_id`, `host_user_id`, `guest_user_id` | belongsTo: listing, host, guest, hasMany: messages |
| **Message** | `messages` | `conversation_id`, `sender_user_id`, `body` | belongsTo: conversation, sender (User) |
| **Cohost** | `cohosts` | `host_user_id`, `cohost_user_id`, `listing_id`, `can_read_conversations`, `can_reply_messages`, `can_edit_listings` | belongsTo: host, cohost (User), listing |
| **ListingImage** | `listing_images` | `listing_id`, `path`, `position` | belongsTo: listing |

### Services (couche métier)

| Service | Responsabilités |
|---------|-----------------|
| `AuthService` | Inscription, déconnexion (révocation tokens) |
| `ListingService` | CRUD annonces, géocodage, filtrage par bounds/ville/capacité |
| `BookingService` | Création, confirmation, rejet, annulation, détection de conflits |
| `PaymentService` | Création d'intent, autorisation, capture, calcul TVA/frais |
| `ConversationService` | Gestion des conversations |
| `MessageService` | Envoi de messages |
| `CohostService` | Délégation et permissions co-hôtes |
| `ProfileService` | Mise à jour du profil utilisateur |
| `NotificationService` | Envoi de notifications (DB + email) |
| `GeocodingService` | Conversion adresse → coordonnées GPS |
| `ListingImageService` | Upload et réordonnancement d'images |
| `HostStatsService` | Statistiques du tableau de bord hôte |

### Endpoints API

#### Routes publiques

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/v1/health` | Health check |
| `GET` | `/api/v1/ping` | Ping avec timestamp |
| `POST` | `/api/v1/auth/register` | Inscription utilisateur |
| `GET` | `/api/v1/listings` | Liste des annonces (filtres: city, capacity, bounds) |
| `GET` | `/api/v1/listings/{id}` | Détail d'une annonce |
| `GET` | `/api/v1/listings/{id}/bookings` | Réservations confirmées d'une annonce |

#### Routes authentifiées

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| **Auth** | | |
| `POST` | `/api/v1/auth/logout` | Déconnexion (révocation token) |
| `GET` | `/api/v1/me` | Utilisateur courant |
| `PATCH` | `/api/v1/me/profile` | Mise à jour profil |
| `GET` | `/api/v1/me/listings` | Mes annonces (hôte) |
| `GET` | `/api/v1/me/cohost-listings` | Annonces co-hébergées |
| `GET` | `/api/v1/me/host-stats` | Statistiques hôte |
| **Bookings** | | |
| `GET` | `/api/v1/bookings` | Mes réservations |
| `POST` | `/api/v1/bookings` | Créer une réservation |
| `PATCH` | `/api/v1/bookings/{id}/confirm` | Confirmer (hôte) |
| `PATCH` | `/api/v1/bookings/{id}/reject` | Rejeter (hôte) |
| `POST` | `/api/v1/bookings/{id}/cancel` | Annuler |
| **Payments** | | |
| `POST` | `/api/v1/payments/intent` | Créer un intent de paiement |
| `POST` | `/api/v1/payments/{id}/authorize` | Autoriser le paiement |
| **Notifications** | | |
| `GET` | `/api/v1/notifications` | Liste des notifications |
| `GET` | `/api/v1/notifications/unread-count` | Nombre non lues |
| `POST` | `/api/v1/notifications/{id}/read` | Marquer comme lue |
| `POST` | `/api/v1/notifications/read-all` | Tout marquer comme lu |
| **Conversations** | | |
| `GET` | `/api/v1/conversations` | Liste des conversations |
| `POST` | `/api/v1/conversations` | Créer une conversation |
| `GET` | `/api/v1/conversations/{id}/messages` | Messages d'une conversation |
| `POST` | `/api/v1/conversations/{id}/messages` | Envoyer un message |
| **Listings** | | |
| `POST` | `/api/v1/listings` | Créer une annonce |
| `PATCH` | `/api/v1/listings/{id}` | Modifier une annonce |
| `DELETE` | `/api/v1/listings/{id}` | Supprimer une annonce |
| `POST` | `/api/v1/listings/{id}/images` | Upload images |
| `PATCH` | `/api/v1/listings/{id}/images/reorder` | Réordonner images |
| **Cohosts** | | |
| `GET` | `/api/v1/cohosts` | Liste des co-hôtes |
| `POST` | `/api/v1/cohosts` | Ajouter un co-hôte |
| `PATCH` | `/api/v1/cohosts/{id}` | Modifier permissions |
| `DELETE` | `/api/v1/cohosts/{id}` | Retirer un co-hôte |

### Codes de réponse HTTP

| Code | Signification |
|------|---------------|
| `200` | Succès (lecture/modification) |
| `201` | Ressource créée |
| `204` | Succès sans contenu |
| `401` | Non authentifié |
| `403` | Non autorisé (permissions) |
| `404` | Ressource non trouvée |
| `409` | Conflit (dates de réservation) |
| `422` | Erreur de validation |

### Politiques d'autorisation (Policies)

| Policy | Règles |
|--------|--------|
| `ListingPolicy` | Lecture publique ; CRUD réservé à l'hôte ; co-hôte avec `can_edit_listings` peut modifier |
| `BookingPolicy` | Création par guest (sauf sur sa propre annonce) ; confirm/reject par hôte/co-hôte ; cancel par guest ou hôte |
| `ConversationPolicy` | Accès par hôte, guest, ou co-hôte avec `can_read_conversations` |
| `MessagePolicy` | Envoi par hôte, guest, ou co-hôte avec `can_reply_messages` |
| `PaymentPolicy` | Autorisation uniquement par le guest de la réservation |
| `CohostPolicy` | Gestion uniquement par l'hôte propriétaire |

---

## Frontend - SPA Vue

### Structure des dossiers

```
apps/app/
├── src/
│   ├── assets/
│   │   └── main.css              # Styles Tailwind
│   ├── components/
│   │   ├── Breadcrumbs.vue       # Fil d'Ariane
│   │   └── NotificationBell.vue  # Cloche notifications
│   ├── router/
│   │   └── index.ts              # Configuration routes
│   ├── services/                 # Clients API
│   │   ├── api.ts                # Fetch wrapper générique
│   │   ├── bookings.ts
│   │   ├── cohosts.ts
│   │   ├── conversations.ts
│   │   ├── echo.ts               # Laravel Echo config
│   │   ├── hostStats.ts
│   │   ├── listings.ts
│   │   ├── messages.ts
│   │   ├── notifications.ts
│   │   ├── payments.ts
│   │   └── profile.ts
│   ├── stores/                   # Stores Pinia
│   │   ├── auth.ts               # État authentification
│   │   └── notifications.ts      # État notifications
│   ├── utils/
│   │   └── gravatar.ts           # Génération avatar
│   ├── views/                    # Pages/Vues
│   ├── App.vue                   # Composant racine
│   └── main.ts                   # Point d'entrée
├── public/                       # Assets statiques
├── .env.example
├── package.json
├── tsconfig.json
├── vite.config.ts
└── vitest.config.ts
```

### Pages (Views)

| Vue | Route | Description |
|-----|-------|-------------|
| `HomeView.vue` | `/` | Page d'accueil avec liste des annonces |
| `MapSearchView.vue` | `/map` | Recherche sur carte MapLibre |
| `ListingDetailView.vue` | `/listings/:id` | Détail d'une annonce avec formulaire réservation |
| `LoginView.vue` | `/login` | Formulaire de connexion |
| `RegisterView.vue` | `/register` | Formulaire d'inscription |
| `ProfileView.vue` | `/profile` | Gestion du profil |
| `BookingsView.vue` | `/bookings` | Historique des réservations (voyageur) |
| `CheckoutView.vue` | `/checkout/:bookingId` | Paiement d'une réservation |
| `MessagesView.vue` | `/messages` | Liste des conversations |
| `MessageThreadView.vue` | `/messages/:id` | Fil de discussion |
| **Espace hôte** | | |
| `DashboardView.vue` | `/host` | Tableau de bord hôte |
| `MyListingsView.vue` | `/host/listings` | Mes annonces |
| `ListingCreateView.vue` | `/host/listings/new` | Créer une annonce |
| `ListingEditView.vue` | `/host/listings/:id/edit` | Modifier une annonce |
| `HostBookingsView.vue` | `/host/bookings` | Réservations reçues |
| `CohostsView.vue` | `/host/cohosts` | Gestion des co-hôtes |
| `HostListingMessagesView.vue` | `/host/listings/:id/messages` | Messages d'une annonce |
| `NotFoundView.vue` | `*` | Page 404 |

### Stores Pinia

#### `auth.ts`

```typescript
// État
token: string | null        // Access token OAuth2
user: User | null           // Utilisateur connecté

// Actions
login(email, password)      // Connexion
register(name, email, password)  // Inscription
logout()                    // Déconnexion
fetchMe()                   // Récupérer l'utilisateur courant

// Persistance: localStorage
```

#### `notifications.ts`

```typescript
// État
notifications: Notification[]
unreadCount: number

// Actions
hydrate()                   // Charger les notifications
refreshUnreadCount()        // Rafraîchir le compteur
markRead(id)               // Marquer comme lue
markAllRead()              // Tout marquer comme lu
bindToUser(userId)         // S'abonner aux événements temps réel
```

### Guards de navigation

| Meta | Comportement |
|------|--------------|
| `requiresAuth: true` | Redirige vers `/login` si non authentifié |
| `guestOnly: true` | Redirige vers `/` si déjà authentifié |
| `layout: 'host'` | Utilise le layout hôte avec sidebar |

---

## Base de données

### Schéma des tables

#### `users`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint | Clé primaire |
| `name` | string | Nom de l'utilisateur |
| `email` | string (unique) | Email |
| `password` | string | Mot de passe hashé |
| `address` | string (nullable) | Adresse |
| `profile_photo_path` | string (nullable) | Photo de profil |
| `email_verified_at` | timestamp (nullable) | Date vérification email |
| `created_at` | timestamp | Date création |
| `updated_at` | timestamp | Date modification |

#### `listings`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint | Clé primaire |
| `host_user_id` | bigint (FK) | Propriétaire |
| `title` | string | Titre |
| `description` | text | Description |
| `city` | string | Ville |
| `address` | string | Adresse |
| `full_address` | string (nullable) | Adresse complète |
| `latitude` | decimal (nullable) | Latitude GPS |
| `longitude` | decimal (nullable) | Longitude GPS |
| `guest_capacity` | integer (default: 1) | Capacité |
| `price_per_night` | decimal | Prix par nuit |
| `rules` | text (nullable) | Règles du logement |
| `amenities` | json (nullable) | Équipements |
| `created_at` | timestamp | Date création |
| `updated_at` | timestamp | Date modification |

#### `listing_images`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint | Clé primaire |
| `listing_id` | bigint (FK) | Annonce |
| `path` | string | Chemin de l'image |
| `position` | integer (default: 0) | Ordre d'affichage |
| `created_at` | timestamp | Date création |
| `updated_at` | timestamp | Date modification |

#### `bookings`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint | Clé primaire |
| `listing_id` | bigint (FK) | Annonce |
| `guest_user_id` | bigint (FK) | Voyageur |
| `start_date` | date | Date de début |
| `end_date` | date | Date de fin |
| `status` | string | Statut (voir ci-dessous) |
| `created_at` | timestamp | Date création |
| `updated_at` | timestamp | Date modification |

**Statuts de réservation :**

| Statut | Description |
|--------|-------------|
| `pending` | En attente de confirmation par l'hôte |
| `awaiting_payment` | Confirmée, en attente de paiement |
| `confirmed` | Payée et confirmée |
| `rejected` | Rejetée par l'hôte |
| `completed` | Séjour terminé |
| `cancelled` | Annulée |

#### `payments`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint | Clé primaire |
| `booking_id` | bigint (FK) | Réservation |
| `guest_user_id` | bigint (FK) | Voyageur |
| `host_user_id` | bigint (FK) | Hôte |
| `amount_total` | decimal | Montant total |
| `amount_base` | decimal | Montant de base |
| `amount_vat` | decimal | TVA (20%) |
| `amount_service` | decimal | Frais de service (7%) |
| `commission_amount` | decimal | Commission (12%) |
| `payout_amount` | decimal | Versement à l'hôte |
| `status` | string | pending/authorized/captured/refunded |
| `authorized_at` | timestamp (nullable) | Date autorisation |
| `captured_at` | timestamp (nullable) | Date capture |
| `refunded_at` | timestamp (nullable) | Date remboursement |
| `created_at` | timestamp | Date création |
| `updated_at` | timestamp | Date modification |

#### `conversations`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint | Clé primaire |
| `listing_id` | bigint (FK) | Annonce concernée |
| `host_user_id` | bigint (FK) | Hôte |
| `guest_user_id` | bigint (FK) | Voyageur |
| `created_at` | timestamp | Date création |
| `updated_at` | timestamp | Date modification |

#### `messages`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint | Clé primaire |
| `conversation_id` | bigint (FK) | Conversation |
| `sender_user_id` | bigint (FK) | Expéditeur |
| `body` | text | Contenu du message |
| `created_at` | timestamp | Date création |
| `updated_at` | timestamp | Date modification |

#### `cohosts`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint | Clé primaire |
| `host_user_id` | bigint (FK) | Hôte propriétaire |
| `cohost_user_id` | bigint (FK) | Co-hôte délégué |
| `listing_id` | bigint (FK) | Annonce concernée |
| `can_read_conversations` | boolean | Peut lire les conversations |
| `can_reply_messages` | boolean | Peut répondre aux messages |
| `can_edit_listings` | boolean | Peut modifier l'annonce |
| `created_at` | timestamp | Date création |
| `updated_at` | timestamp | Date modification |

---

## Authentification

### Système OAuth2 (Passport + PKCE)

Le projet utilise **Laravel Passport** avec le flux **Authorization Code + PKCE** pour la SPA.

#### Flux d'authentification

```
1. Inscription (API)
   POST /api/v1/auth/register

2. Connexion (OAuth2 + PKCE)
   SPA -> /oauth/authorize (code_challenge + state)

3. Retour SPA
   /auth/callback?code=...&state=...

4. Échange du code
   POST /oauth/token (code_verifier)

5. Requêtes authentifiées
   Authorization: Bearer <access_token>
```

#### Client OAuth (PKCE)

Créer un client public Passport et renseigner l'ID côté SPA :

```
php artisan passport:client --public
```

#### Middleware

- **`auth:api`** : Guard Passport pour les routes protégées
- **`auth.api.optional`** : Renseigne l'utilisateur si un token est présent

---

## Fonctionnalités métier

### 1. Réservations (Anti-conflit)

Le système empêche les doubles réservations :

```php
// BookingService.php - Détection de conflit
$hasConflict = Booking::query()
    ->where('listing_id', $data['listing_id'])
    ->where('status', 'confirmed')
    ->where('start_date', '<', $end->toDateString())
    ->where('end_date', '>', $start->toDateString())
    ->exists();

if ($hasConflict) {
    throw new ConflictHttpException('Dates indisponibles.');
}
```

**Règles :**
- Seules les réservations `confirmed` bloquent les dates
- Un hôte ne peut pas réserver sa propre annonce
- Un co-hôte ne peut pas réserver une annonce qu'il co-héberge
- Code HTTP 409 en cas de conflit

### 2. Flow de réservation complet

```
┌──────────────────────────────────────────────────────────────────┐
│                     CYCLE DE VIE RÉSERVATION                     │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Guest crée réservation                                          │
│           │                                                      │
│           ▼                                                      │
│     ┌──────────┐                                                 │
│     │ PENDING  │ ◄─── En attente confirmation hôte               │
│     └────┬─────┘                                                 │
│          │                                                       │
│     ┌────┴────┐                                                  │
│     ▼         ▼                                                  │
│ ┌────────┐  ┌──────────────────┐                                 │
│ │REJECTED│  │ AWAITING_PAYMENT │ ◄─── Confirmé par hôte          │
│ └────────┘  └────────┬─────────┘                                 │
│                      │                                           │
│                      ▼                                           │
│               ┌──────────┐                                       │
│               │CONFIRMED │ ◄─── Paiement autorisé                │
│               └────┬─────┘                                       │
│                    │                                             │
│               ┌────┴────┐                                        │
│               ▼         ▼                                        │
│         ┌───────────┐ ┌──────────┐                               │
│         │ CANCELLED │ │COMPLETED │                               │
│         └───────────┘ └──────────┘                               │
│               │                                                  │
│               ▼                                                  │
│         Si paiement effectué → REMBOURSEMENT automatique         │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

### 3. Système de paiement (fake)

Calcul des montants :

| Élément | Calcul |
|---------|--------|
| **Base** | `nuits * prix_par_nuit` |
| **TVA** | `base * 20%` |
| **Frais de service** | `base * 7%` |
| **Total guest** | `base + TVA + frais` |
| **Commission** | `base * 12%` |
| **Payout hôte** | `base - commission` |

### 4. Système de co-hôtes

Permissions configurables par annonce :

| Permission | Description |
|------------|-------------|
| `can_read_conversations` | Voir les conversations de l'annonce |
| `can_reply_messages` | Répondre aux messages |
| `can_edit_listings` | Modifier les détails de l'annonce |

### 5. Notifications

Types de notifications :

- Nouvelle demande de réservation (hôte)
- Changement de statut réservation (guest)
- Paiement autorisé
- Remboursement effectué
- Nouveau message

Canaux :
- **Database** : Notifications in-app
- **Email** : Via Mailpit (dev)
- **Broadcast** : Temps réel via Laravel Reverb

---

## Infrastructure

### Docker Compose Services

| Service | Image | Port | Description |
|---------|-------|------|-------------|
| `api` | `fakebnb-api` | 8989 | API Laravel (FrankenPHP) |
| `api.queue` | `fakebnb-api` | - | Worker de queue |
| `api.reverb` | `fakebnb-api` | 8080 | Serveur WebSocket |
| `app` | `fakebnb-app` | 5173 | SPA Vue (Nginx) |
| `mailpit` | `axllent/mailpit` | 1025, 8025 | Serveur mail test |
| `redis` | `redis:alpine` | - | Cache |

### Variables d'environnement

```env
# API
APP_URL=http://localhost:8989
APP_KEY=base64:...
DB_CONNECTION=sqlite
QUEUE_CONNECTION=database
BROADCAST_CONNECTION=reverb

# Reverb (WebSockets)
REVERB_APP_ID=local
REVERB_APP_KEY=local
REVERB_APP_SECRET=local
REVERB_HOST=localhost
REVERB_PORT=8080

# Frontend
VITE_API_URL=http://localhost:8989/api/v1
VITE_REVERB_APP_KEY=local
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_OAUTH_CLIENT_ID=your-public-client-id
VITE_OAUTH_AUTHORIZE_URL=http://localhost:8989/oauth/authorize
VITE_OAUTH_TOKEN_URL=http://localhost:8989/oauth/token
VITE_OAUTH_REDIRECT_URI=http://localhost:5173/auth/callback
```

### Commandes Makefile

```bash
make init      # Initialiser le projet
make up        # Démarrer les services
make down      # Arrêter les services
make rebuild   # Reconstruire les images
make logs      # Voir les logs
make ps        # Lister les containers
```

---

## Tests

### Tests API (Pest PHP)

| Fichier | Couverture |
|---------|------------|
| `AuthTest.php` | Inscription, connexion, credentials invalides, /me, logout |
| `ListingsTest.php` | Liste publique, détail, CRUD, permissions co-hôte |
| `BookingsTest.php` | Création, détection conflit (409), confirm/reject, annulation, remboursement |
| `ConversationsTest.php` | Création par guest, restrictions hôte/co-hôte |
| `CohostsTest.php` | Délégation et permissions |
| `PaymentsTest.php` | Flow de paiement complet |
| `ListingImagesTest.php` | Upload et réordonnancement |
| `ListingDeletionTest.php` | Suppression avec cascades |

### Exécution des tests

```bash
# API (depuis apps/api/)
composer run test
# ou
php artisan test

# SPA (depuis apps/app/)
npm run test:unit
```

---

## Commandes de développement

### API Laravel

```bash
cd apps/api

# Installation
composer install
composer run setup     # Setup complet (env, migrate, build)

# Développement
composer run dev       # Serveur + queue + logs + Vite

# Tests
composer run test
php artisan test --filter=BookingsTest

# Base de données
php artisan migrate
php artisan migrate:fresh --seed

# Cache
php artisan cache:clear
php artisan config:clear
```

### SPA Vue

```bash
cd apps/app

# Installation
npm install

# Développement
npm run dev           # Serveur Vite

# Build
npm run build         # Build production
npm run build-only    # Build sans type-check

# Tests
npm run test:unit     # Vitest

# Qualité
npm run lint          # ESLint
npm run format        # Prettier
npm run type-check    # vue-tsc
```

### Docker

```bash
cd infra

# Démarrer tout
docker compose up -d

# Voir les logs
docker compose logs -f api

# Reconstruire
docker compose build --no-cache

# Arrêter
docker compose down
```

---

## Checklist des fonctionnalités

Voir le fichier [`features.md`](./features.md) pour la liste complète organisée par LOT :

- **LOT 0** : Socle technique
- **LOT 1** : Authentification
- **LOT 2** : Permissions & co-hôtes
- **LOT 3** : Annonces
- **LOT 4** : Réservations (anti-conflit)
- **LOT 5** : Messagerie
- **LOT 6** : Paiement (fake)
- **LOT 7** : Notifications
- **LOT 8** : Recherche & carte
- **LOT 9** : Cache navigateur
- **LOT 10** : Tests & qualité
- **LOT 11** : Documentation

---

## Règle d'or

> **Ce qui n'est ni testé, ni documenté, n'existe pas.**
