MiniBnB — Check-list des features
🧱 LOT 0 — Socle technique (obligatoire avant le métier)
Backend (Laravel)

 Projet Laravel 12 initialisé (sans starter kit)

 API REST JSON fonctionnelle

 Versioning actif (/api/v1)

 SQLite configurée (dev)

 Sail opérationnel (ou équivalent)

 Gestion globale des erreurs JSON (format cohérent)

 Validation centralisée (FormRequest)

Frontend (Vue)

 SPA Vue 3 fonctionnelle

 Router configuré (public / privé)

 Pinia installé

 Client API (Axios/fetch) centralisé

 Gestion globale des erreurs API

 ESLint + Prettier actifs (build clean)

Critère OK : l’app démarre, communique, retourne du JSON propre.

🔐 LOT 1 — Authentification & utilisateurs
Backend

 Inscription utilisateur

 Connexion (OAuth2 access token + PKCE)

 Middleware auth API

 Route /me (utilisateur courant)

 Déconnexion (invalidation côté client minimum)

Frontend

 Pages login / register

 Store auth (user + token)

 Routes protégées

 Persistance session (localStorage)

Tests

 Test API : register

 Test API : login

 Test API : accès route protégée

Critère OK : un utilisateur peut s’inscrire, se connecter, accéder à des pages privées.

👥 LOT 2 — Permissions & co-hôtes
Backend

 Modèle co-hôte (délégation)

 Permissions configurables :

 Lire conversations

 Répondre messages

 Modifier annonces

 Policies/Gates actives côté serveur

Frontend

 UI gestion des co-hôtes (côté hôte)

 UI conditionnelle selon permissions

Tests

 Test accès interdit sans permission

 Test accès autorisé avec délégation

Critère OK : les droits sont appliqués côté serveur, pas seulement côté front.

🏠 LOT 3 — Annonces (Listings)
Backend

 CRUD annonces

 Annonce liée à un hôte

 Lecture publique des annonces

 Accès modification restreint (hôte / co-hôte)

 Ressources API (exposition maîtrisée des données)

 Caractéristiques logement (capacité, équipements, règles)

 Images multiples + ordre persistant (upload multipart)

 Suppression d’annonce avec cascades (messages, réservations, médias)

Frontend

 Liste des annonces

 Page détail annonce

 Création / édition annonce (hôte)

 UI “attractive” (minimum visuel)

 Galerie images (lightbox)

Tests

 Test création annonce

 Test modification interdite (client)

 Test modification autorisée (hôte)

Critère OK : un hôte peut gérer ses annonces, un client peut les consulter.

📅 LOT 4 — Réservations (booking sans conflit)
Backend

 Création réservation

 Validation dates (start < end)

 Détection anti-chevauchement

 Retour 409 en cas de conflit

 Lecture réservations (client / hôte)

 Validation par l’hôte (pending → awaiting_payment → confirmed)

 Annulation + remboursement

 Clôture automatique après end_date (scheduler)

Frontend

 Sélecteur de dates

 Affichage disponibilités

 Historique réservations (client)

 Planning réservations (hôte)

 Détail réservation côté hôte + voyageur

 Empêcher réservation par hôte/co-hôte

Tests

 Test réservation valide

 Test réservation en conflit

 Test visibilité selon rôle

Critère OK : impossible de réserver sur une période déjà prise.

💬 LOT 5 — Messagerie
Backend

 Conversations liées à une annonce

 Messages dans une conversation

 Accès :

client concerné

hôte concerné

co-hôte autorisé

 Validation des droits par policy

 Realtime (Reverb/WebSockets)

Frontend

 Liste des conversations

 Lecture messages

 Envoi message

 UI temps réel (messages + notifications)

 Flux séparés hôte / voyageur

Tests

 Test accès conversation

 Test envoi message autorisé

 Test envoi message interdit

Critère OK : messagerie sécurisée, liée au contexte d’une annonce.

📝 LOT 6 — Avis & notes
Backend

 Un seul avis par réservation

 Avis post-séjour (voyageur uniquement)

 Réponse hôte / co-hôte (si droit)

 Ressources API minimalistes (listing, guest)

Frontend

 Affichage des avis sur une annonce

 Formulaire avis en fin de séjour

 Espace avis côté hôte + réponse

Tests

 Test création avis (voyageur)

 Test réponse avis (hôte / co-hôte)

Critère OK : avis fiables, droits respectés, réponse hôte possible.

💳 LOT 7 — Paiement (fake)
Backend

 Intent de paiement

 Autorisation + capture (automatique)

 Calcul TVA + frais de service + commission

 Remboursement lors annulation

 Notifications paiement

Frontend

 Checkout dédié

 Récapitulatif prix

Critère OK : le flow paiement est simulé de bout en bout.

🔔 LOT 8 — Notifications (in-app + email)
Backend

 Notifications DB + broadcast

 Email : inscription, réservation, statut, paiement

 Mailpit via Sail

Frontend

 Bell + liste temps réel

 Supprimer après lecture

Critère OK : l’utilisateur reçoit les événements clés.

🧭 LOT 9 — Recherche & carte
Backend

 Filtres (ville, capacité, texte) + pagination

 Géocodage adresse → coordonnées

Frontend

 Barre de recherche + filtres

 Page MapLibre + OpenStreetMap

 Listing filtré par bounds carte

Critère OK : recherche multi-critères + mode carte.

⚡ LOT 10 — Cache navigateur (exigé TP)
Backend

 Headers Cache-Control

 ETag sur endpoints de lecture :

 liste annonces

 détail annonce

 304 Not Modified fonctionnel

Frontend

 Revalidation conditionnelle (ETag)

 Démonstration claire (devtools)

Tests / preuve

 Capture ou explication dans la doc

Critère OK : le cache HTTP est visible et justifiable.

🧪 LOT 11 — Tests & qualité (transversal)
Backend

 Tests feature sur chaque domaine :

auth

annonces

réservations

permissions

messagerie

Frontend

 Tests unitaires (stores / services)

 Zéro erreur ESLint / Prettier

Règle repo

 Aucune feature “done” sans tests

📚 LOT 12 — Documentation & livrables
Docs (/docs)

 Architecture du projet

 Choix techniques

 Schéma d’architecture

 Documentation API (OpenAPI ou Markdown)

 Explication cache

 Instructions de lancement

Soutenance

 Slides ou support clair

 Démo fonctionnelle

 Explication des choix

Critère OK : quelqu’un d’externe peut comprendre et lancer le projet.

🎯 Règle d’or du TP

Ce qui n’est ni testé, ni documenté, n’existe pas.
