# MiniBnB — Document d’architecture (Monorepo Laravel + Vue)

## Contexte du projet

Ce dépôt contient le projet **MiniBnB**, une application web full-stack de type Airbnb : **annonces**, **réservations**, **messagerie**, et **gestion des rôles**. Le TP impose une **SPA** côté front, une **API REST JSON** côté back, des **tests automatisés**, une **documentation d’API**, ainsi qu’un **schéma d’architecture**. 

Un export SQL “Airbnb” est fourni pour démarrer avec des données réalistes (optionnel) : l’application doit dans tous les cas contenir des données crédibles. 

Livrables : **Git du projet** + **support de présentation**, avec une **soutenance le 10/02**. 

---

## Architecture du dépôt

Le projet est structuré en **monorepo** afin de centraliser la stack front + back + documentation + infrastructure.

```
/apps
  /api   -> Laravel 12 (API REST) - SQLite, Sail, sans starterkit
  /app   -> Vue 3 (SPA) - TypeScript, Router, Pinia, Vitest, ESLint, Prettier
/docs    -> Documentation (API, architecture, choix techniques, conventions)
/infra   -> Infrastructure (docker compose, scripts, tooling)
```

### Rôle de chaque dossier

* **/apps/api** : source de vérité métier (règles de réservation, permissions, sécurité). Expose une API REST versionnée.
* **/apps/app** : interface utilisateur (SPA) : navigation, affichage attractif, formulaires, calendrier, messagerie. 
* **/docs** : documentation projet : exigences, architecture, endpoints, conventions, décisions. 
* **/infra** : exécution locale reproductible (Sail, scripts, variables, aides).

---

## Exigences du TP (rappel)

### Stack & contraintes

* **SPA obligatoire** (Vue/React/Angular au choix). 
* **API REST en JSON** avec bonnes pratiques HTTP : ressources, méthodes, statuts, erreurs. 
* **Base de données libre** (ici SQLite en dev). 
* **Tests automatisés** + **documentation d’API** + **schéma d’architecture**. 

### Exigences fonctionnelles

L’application doit permettre :

* Inscription + authentification. 
* Gestion **rôles & permissions** :

  * Client par défaut à l’inscription
  * Hôte (publier / gérer ses annonces)
  * Co-hôte : rôle délégué avec droits configurables (accès conversations, répondre, modifier annonces) 
* Gestion des **annonces** : création / consultation / modification + contenu libre + affichage attractif. 
* Gestion des **réservations** : calendrier/disponibilités + cohérence des dates (pas de conflits) + visibilité côté loueur/hôte. 
* **Messagerie** entre loueurs et hôtes, accessible aux co-hôtes. 

### Exigences techniques

* API REST conforme (ressources, méthodes, statuts, exposition maîtrisée des données). 
* **Versioning obligatoire** (une version livrée, architecture prête pour plusieurs). 
* **Sécurité** : JWT access token + droits côté serveur (refresh token en bonus). 
* **Cache navigateur** sur lectures fréquentes, démontrable (headers + stratégie front). Cache serveur en bonus. 
* Qualité : validation, gestion d’erreurs, tests, doc, schéma d’architecture. 

---

## Fonctionnalités à développer

### 1) Authentification & utilisateurs

* Inscription, connexion, déconnexion
* Gestion du profil (minimum : identité / infos de base)
* Passage “client → hôte” (activation du rôle hôte)

### 2) Rôles, permissions, délégations co-hôte

* Client par défaut
* Hôte : propriétaire des annonces
* Co-hôte : délégation par un hôte avec droits configurables :

  * lire conversations liées aux annonces
  * répondre aux messages
  * modifier annonces 

### 3) Annonces

* CRUD annonces (au minimum : titre, description, adresse/ville, prix, visuels, règles)
* Consultation publique + page détail
* Gestion côté hôte/co-hôte (selon droits)
* Affichage “attractif” côté SPA 

### 4) Réservations (anti-conflits)

* Création réservation sur une annonce
* Calendrier/disponibilités
* Règles : dates valides + **aucun chevauchement**
* Visibilité côté client + côté hôte 

### 5) Messagerie

* Conversations loueur ↔ hôte
* Messages dans une conversation
* Accès co-hôte selon délégation 

### 6) Cache navigateur (démontrable)

Cibler au minimum :

* `GET /listings` (liste annonces)
* `GET /listings/{id}` (détail annonce)
  Avec stratégie :
* headers (ETag et/ou Cache-Control)
* logique SPA (Pinia + TTL / revalidation) 

---

## Bonnes pratiques et standards du repo

### 1) Convention API (Laravel)

* Routes versionnées : `/api/v1/...` (préparer `/api/v2` sans refonte) 
* REST : ressources claires (`listings`, `bookings`, `conversations`, `messages`)
* Statuts cohérents :

  * 200/201/204 succès
  * 401 non authentifié
  * 403 interdit (permissions)
  * 404 introuvable
  * 409 conflit (réservation qui chevauche)
  * 422 validation
* Erreurs JSON uniformes (message + erreurs par champ)

### 2) Sécurité

* JWT access token obligatoire 
* Autorisation **côté serveur** via Policies/Gates (ne jamais faire confiance au front) 
* Bonus : refresh token (si temps)

### 3) Qualité & validation

* Validation systématique des payloads (FormRequest côté Laravel)
* Transactions DB sur les opérations sensibles (réservation)
* “Exposition maîtrisée des données” : ressources API (Laravel Resources) pour filtrer ce qui sort 

### 4) Tests (règle repo)

Chaque feature livrée doit être **testée** :

* **API (Laravel)** : tests feature sur

  * auth (login/register)
  * permissions (host/cohost)
  * booking anti-conflit (cas chevaûchement)
  * messagerie (accès et envoi)
* **Front (Vue)** :

  * Vitest sur stores/services (auth store, api client, cache store)
  * E2E optionnel (bonus), sinon au moins tests unitaires 

Critère : une PR/feature n’est considérée “done” que si :

* endpoints documentés
* tests verts
* linter/prettier passent

### 5) Documentation

Dans `/docs` :

* `architecture.md` : schéma + explications
* `api.md` ou OpenAPI : endpoints, auth, erreurs
* `conventions.md` : branches/commits, naming, règles

La doc d’API est obligatoire. 

### 6) CI locale (minimum)

* Lancer en une commande :

  * API : via Sail
  * SPA : `pnpm dev` / `npm run dev`
* Scripts recommandés (à mettre à la racine) :

  * `make test` : lance tests api + web
  * `make lint` : eslint + phpstan (optionnel) + pint (optionnel)

---

## Stratégie d’implémentation conseillée (ordre logique)

1. **Auth JWT + users**
2. **Listings CRUD**
3. **Bookings + anti-conflits**
4. **Messaging**
5. **Co-host délégations & permissions**
6. **Cache navigateur**
7. **Doc + durcissement tests + polish UI**

---
