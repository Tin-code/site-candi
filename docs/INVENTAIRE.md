# INVENTAIRE COMPLET — Projet CaNDI (Phase 1)

> Généré le 2026-10-22 — Source de vérité : code React/Vite et fichier `candi_flow.sql`

---

## 1. Routes (TanStack Router)

### Routes publiques (non authentifiées)

| Route | Fichier source | Description |
|---|---|---|
| `/` | `routes/index.tsx` | Page d'accueil |
| `/a-propos` | `routes/a-propos.tsx` | Page À propos |
| `/actualites` | `routes/actualites.tsx` | Liste des actualités |
| `/actualites/:slug` | `routes/actualites.$slug.tsx` | Article individuel |
| `/formations` | `routes/formations.tsx` | Catalogue des formations + inscription |
| `/produits-services` | `routes/produits-services.tsx` | Produits et services (offerings) |
| `/contact` | `routes/contact.tsx` | Page Contact |
| `/auth` | `routes/auth.tsx` | Connexion / Inscription / Réinitialisation |
| `/confirmer-telephone` | `routes/confirmer-telephone.tsx` | Confirmation de code OTP téléphone |
| `/ikodi` | `routes/ikodi.tsx` | Assistant IA CaNDI |
| `/setup` | `routes/setup.tsx` | Configuration initiale |
| `/sitemap.xml` | `routes/sitemap[.]xml.ts` | Sitemap XML dynamique |

### Webhooks API publics

| Route | Fichier source | Méthode | Description |
|---|---|---|---|
| `/api/public/cinetpay/notify` | `routes/api/public/cinetpay/notify.ts` | POST | Webhook notification paiement CinetPay |
| `/api/public/sms-dispatch` | `routes/api/public/sms-dispatch.ts` | POST | Dispatch SMS via Nimba SMS |

### Routes authentifiées (`/_authenticated`)

Toutes ces routes nécessitent un JWT valide (redirige vers `/auth` sinon).

| Route | Fichier source | Rôles autorisés | Description |
|---|---|---|---|
| `/tableau-de-bord` | `_authenticated/tableau-de-bord.tsx` | tous | Tableau de bord principal |
| `/espace-apprenant` | `_authenticated/espace-apprenant.tsx` | tous | Espace apprenant (inscriptions, emploi du temps) |
| `/inscriptions` | `_authenticated/inscriptions.tsx` | super_admin, admin, comptable, responsable_formation, commercial | Gestion des inscriptions |
| `/catalogue` | `_authenticated/catalogue.tsx` | super_admin, admin, responsable_formation, comptable, formateur | Gestion du catalogue formations |
| `/paiements` | `_authenticated/paiements.tsx` | client, tous | Paiements en ligne (CinetPay) |
| `/paiement-retour` | `_authenticated/paiement-retour.tsx` | connecté | Retour de paiement (page de confirmation) |
| `/crm` | `_authenticated/crm.tsx` | super_admin, admin, comptable | CRM : abonnements progiciels |
| `/finances` | `_authenticated/finances.tsx` | super_admin, admin, comptable | Comptabilité / trésorerie |
| `/immobilier` | `_authenticated/immobilier.tsx` | super_admin, admin, gestionnaire_immobilier | Gestion immobilière |
| `/paiements-loyers` | `_authenticated/paiements-loyers.tsx` | super_admin, admin, gestionnaire_immobilier, comptable | Paiements de loyers |
| `/mon-logement` | `_authenticated/mon-logement.tsx` | client | Espace locataire |
| `/rapports` | `_authenticated/rapports.tsx` | super_admin, admin, comptable | Rapports et exports |
| `/utilisateurs` | `_authenticated/utilisateurs.tsx` | super_admin, admin | Gestion des utilisateurs |
| `/produits` | `_authenticated/produits.tsx` | super_admin, admin | Gestion des offerings |
| `/publications` | `_authenticated/publications.tsx` | super_admin, admin | Gestion des actualités |
| `/messages-sms` | `_authenticated/messages-sms.tsx` | super_admin, admin | Historique SMS |
| `/notifications` | `_authenticated/notifications.tsx` | tous | Notifications |
| `/taches` | `_authenticated/taches.tsx` | tous | Tâches |
| `/assistant` | `_authenticated/assistant.tsx` | tous | Assistant IA interne |
| `/parametres` | `_authenticated/parametres.tsx` | super_admin | Paramètres (CinetPay, Nimba SMS, Orange Money) |
| `/profil` | `_authenticated/profil.tsx` | tous | Profil utilisateur |

---

## 2. Pages — Détail des sections et composants

### Page d'accueil `/`

**Sections :**
1. **Hero** : carrousel automatique 7 photos (intervalle 5 s), overlay dégradé, titre « Propulsez votre avenir numérique », sous-titre, 2 CTA → `/formations` et `/produits-services`
2. **À propos** : grille 2 colonnes — texte + 4 stats (2023, 3, 2, Trophée), CTA → `/a-propos`
3. **Atouts** : 3 cartes (Expertise certifiée, Solutions agiles, Accompagnement)
4. **Formations à la une** : grille 4 colonnes, 4 formations récentes depuis BDD, badge niveau + durée + prix (GNF)
5. **Actualités + Prochaines sessions** (conditionnel si posts > 0) : colonne gauche actualités (3 articles), colonne droite liste sessions (4 prochaines)

**Données chargées :** `training_programs` (4, publiés), `training_sessions` (4, à venir, publiées), `posts` (3, publiés)

**Interactivité :** Carrousel JS (setInterval), transitions CSS opacity

---

### Page Formations `/formations`

**Sections :**
1. En-tête + filtres (recherche texte, catégorie, niveau)
2. Liste programmes filtrée côté client (JS), avec sessions imbriquées
3. Bouton « Demander une inscription » par session (ou par programme sans session)
4. Message d'invitation à créer un compte si non connecté

**Formulaire d'inscription :** 1 clic → appel serveur `requestEnrollmentFn` → crée une `enrollment` en status `nouvelle_demande` → redirection `/tableau-de-bord`

**Toast :** `sonner` — succès / erreur

---

### Page À propos `/a-propos`

**Sections :**
1. En-tête hero avec image de fond
2. Chiffres clés (4 stats)
3. Notre histoire (3 paragraphes)
4. Mot du directeur (citation, photo fondateur)
5. 3 pôles d'activité (Formation, Progiciels, Immobilier)
6. 4 valeurs (Exigence, Proximité, Transparence, Innovation)
7. Distinction + Où nous trouver + Entreprise officielle
8. 2 CTA → Formations, Contact

**Éditeur en ligne :** Si rôle `super_admin` ou `admin`, un panneau `AboutEditor` permet de modifier le contenu (sauvegardé dans `app_settings` sous la clé `about_page`). Les textes par défaut sont dans `src/lib/about-content.ts`.

---

### Page Actualités `/actualites`

**Sections :**
1. En-tête gradient
2. Grille 3 colonnes : cartes articles (image de couverture, badge catégorie, badge Vidéo si url, date, titre, extrait)

---

### Article `/actualites/:slug`

**Sections :** Image de couverture, titre, date, catégorie, contenu texte, lecteur vidéo optionnel, liste des articles récents

---

### Page Produits & Services `/produits-services`

**Sections :**
1. En-tête gradient
2. Section « Nos services » (kind ≠ `produit`)
3. Section « Nos plateformes » (kind = `produit`)

**Carte offering :** image, badge type, prix, titre, résumé, description, liste features (✓), boutons Démo + Contact

---

### Page Contact `/contact`

**Sections :** Titre, 2 cartes (adresses / coordonnées téléphone+email). Pas de formulaire de contact.

---

### Page Authentification `/auth`

**Onglets de premier niveau :** Par téléphone | Par e-mail

**Onglet Téléphone :**
- 3 sous-onglets : Connexion / Créer un compte / Mot de passe oublié
- Étape 1 : saisie numéro (+ prénom/nom pour inscription) → envoi code SMS
- Étape 2 : saisie code OTP (6 chiffres) + choix mot de passe → création compte ou réinitialisation
- Cooldown 60 s avant renvoi

**Onglet E-mail :**
- Connexion : email + mot de passe
- Inscription : prénom, nom, téléphone, email, mot de passe (validé Zod)

**Redirection post-connexion :** `/tableau-de-bord`

---

### Page Tableau de bord `/tableau-de-bord`

Données : inscriptions récentes (50), prochaines sessions (5), transactions (si rôle finances). Contenu adapté au rôle de l'utilisateur. Layout via `AppShell`.

---

### AppShell (layout authentifié)

**Sidebar / menu latéral** avec groupes :
- Pilotage : Tableau de bord
- Formation : Catalogue (public), Espace apprenant, Inscriptions, Gestion catalogue
- Commerce & finances : Finances, Paiements, CRM
- Immobilier : Gestion immobilière, Paiements loyers, Mon logement
- Communication : Messages SMS, Tâches, Notifications, Publications
- Admin : Rapports, Utilisateurs, Produits, Paramètres
- Personnel : Profil

**Badge notifications non lues** en temps réel.
**Menu hamburger mobile** (Sheet Radix).

---

### Page Paiements `/paiements`

Affiche les éléments payables (inscriptions, abonnements CRM, loyers) + historique des paiements en ligne. Appel CinetPay via `startOnlinePaymentFn`.

---

### Page Mon logement `/mon-logement`

Permet à un locataire de rattacher son appartement via un code d'accès. Affiche bail actif, loyers à payer, historique des paiements.

---

### Page Paramètres `/parametres`

2 onglets : CinetPay (site_id, clé API, env., activer/désactiver, tester), Nimba SMS (clé API, sender, tester).

---

## 3. Base de données MySQL (`candi_flow.sql`)

> **Note importante :** Le fichier est un export **phpMyAdmin / MySQL 8.0.40**. Il n'y a PAS de conversion PostgreSQL → MySQL à faire — la base est déjà MySQL.

### Tables et données

| Table | Colonnes clés | PK | Contraintes uniques | Lignes |
|---|---|---|---|---|
| `apartments` | id (varchar), label, code, property_id, owner_id, monthly_rent (double), currency, rooms (int), status, notes, created_at, updated_at | id | — | 2 |
| `app_settings` | key (varchar), value (json), is_public, updated_by, updated_at | key | — | 0 |
| `audit_logs` | id, action, module, record_id, user_id, old_value (json), new_value (json), reason, ip_address, user_agent, created_at | id | — | 0 |
| `branches` | id, name, city, address, phone, is_active, created_at, updated_at | id | — | 0 |
| `clients` | id, full_name, client_number, client_type, company_name, email, phone, whatsapp, address, city, sector, notes, status, owner_id, user_id, branch_id, created_by, archived_at, created_at, updated_at | id | — | 1 |
| `course_materials` | id, program_id, session_id, title, file_path, file_name, created_by, created_at | id | — | 1 |
| `crm_payments` | id, client_id, crm_id, subscription_id, amount, currency, payment_method, period_month, paid_at, notes, account_id, transaction_id, created_by, created_at, updated_at | id | — | 0 |
| `crm_products` | id, code (unique), name, description, monthly_price, currency, is_active, created_at, updated_at | id | code | 0 |
| `crm_subscriptions` | id, client_id, crm_id, monthly_price, currency, start_month, status, notes, created_by, created_at, updated_at | id | — | 0 |
| `doc_counters` | prefix (varchar), year (int), value (int) | (prefix, year) | — | 0 |
| `enrollments` | id, enrollment_number, program_id, session_id, client_id, user_id, status (ENUM 14 valeurs), agreed_amount, paid_amount, discount, currency, payment_mode, promo_code, notes, contact_notes, last_contact_at, next_followup_at, confirmed_at, archived_at, created_by, created_at, updated_at | id | — | 1 |
| `enrollment_status_history` | id, enrollment_id, old_status (ENUM), new_status (ENUM), reason, changed_by, created_at | id | — | 0 |
| `financial_accounts` | id, name, type, currency, opening_balance (double), notes, is_active, branch_id, created_at, updated_at | id | — | 0 |
| `financial_categories` | id, name, direction (ENUM entree/sortie), is_active, created_at | id | — | 0 |
| `fund_transfers` | id, from_account_id, to_account_id, amount, currency, reference, note, status, requested_by, validated_by, validated_at, created_at, updated_at | id | — | 0 |
| `leases` | id, apartment_id, tenant_name, tenant_phone, tenant_email, tenant_user_id, monthly_rent, currency, deposit, start_month, end_date, access_code, status, notes, created_by, created_at, updated_at | id | — | 5 |
| `notifications` | id, user_id, title, body, level, module, link, is_read (tinyint), created_at | id | — | 0 |
| `offerings` | id, title, kind, summary, description (longtext), features (json), price_label, demo_url, image_url, sort_order, is_published, created_by, created_at, updated_at | id | — | 1 |
| `online_payments` | id, order_id, user_id, reference_id, purpose, provider, amount, currency, status, period_month, pay_token, notif_token, payment_url, txnid, raw_response (json), created_at, updated_at | id | — | 0 |
| `outbound_messages` | id, to_phone, body (text), channel, event, related_id, status, attempts (int), error, sent_at, created_at | id | — | 9 |
| `payment_gateway_settings` | provider (varchar), api_base_url, client_id, client_secret, merchant_key, country_code, currency, environment, is_enabled, updated_by, updated_at | provider | — | 2 (cinetpay, nimba_sms) |
| `phone_verifications` | id, phone, code, purpose, attempts (int), expires_at, consumed_at, created_at, updated_at | id | — | 2 |
| `posts` | id, slug (unique), title, content (longtext), excerpt, category, cover_url, video_url, status, author_id, published_at, created_at, updated_at | id | slug | 1 |
| `profiles` | id, first_name, last_name, email, phone, whatsapp, city, district, gender, birth_date, education_level, profession, organization, job_title, emergency_contact, notes, avatar_url, is_active, archived_at, created_at, updated_at | id | — | 8 |
| `properties` | id, name, address, city, notes, is_active, owner_id, created_at, updated_at | id | — | 3 |
| `property_owners` | id, full_name, owner_type, phone, email, address, city, commission_rate (double), notes, is_active, created_by, created_at, updated_at | id | — | 3 |
| `rent_payments` | id, lease_id, apartment_id, period_month, amount, currency, payment_method, payer_phone, paid_at, status, notes, account_id, transaction_id, provider_reference, declared_by, validated_by, validated_at, created_at, updated_at | id | — | 0 |
| `settings_options` | id, category, value, label, sort_order, is_active, created_at, updated_at | id | — | 22 |
| `tasks` | id, title, description, module, priority (ENUM 4), status (ENUM 5), client_id, enrollment_id, assigned_to, due_date, completed_at, archived_at, created_by, created_at, updated_at | id | — | 0 |
| `training_categories` | id, name, slug (unique), description, is_active, created_at, updated_at | id | slug | 0 |
| `training_programs` | id, code (unique), title, summary, description (longtext), level, price (double), promo_price, duration_label, total_hours, category_id, image_url, objectives (json), skills (json), prerequisites, target_audience, required_documents (json), allow_installments, certificate_available, is_published, archived_at, created_by, created_at, updated_at | id | code | 4 |
| `training_sessions` | id, code (unique), program_id, branch_id, start_date (varchar), end_date (varchar), start_time, end_time, days_of_week, modality (ENUM: presentiel/en_ligne/hybride), location, room, meeting_url, capacity (int), seats_taken (int), status, notes, is_published, archived_at, created_at, updated_at | id | code | 2 |
| `transactions` | id, account_id, direction (ENUM entree/sortie), amount, currency, tx_date (varchar), payment_method, category_id, client_id, enrollment_id, branch_id, description, notes, third_party, reference, proof_url, status (ENUM 4), archived_at, created_by, created_at, updated_at | id | — | 0 |
| `users` | id, email (unique), password_hash, email_verified, is_active, created_at, updated_at | id | email | 8 |
| `user_roles` | id, user_id, role (ENUM 8 rôles), created_at | id | — | 7 |
| `_prisma_migrations` | id, checksum, finished_at, migration_name, logs, rolled_back_at, started_at, applied_steps_count | id | — | 1 (à ignorer) |

### ENUMs importants

- **enrollments.status** : brouillon, nouvelle_demande, a_contacter, contacte, en_attente_documents, en_attente_paiement, paiement_partiel, confirmee, en_cours, terminee, abandonnee, annulee, refusee, remboursee
- **user_roles.role** : super_admin, admin, comptable, responsable_formation, formateur, commercial, client, gestionnaire_immobilier
- **tasks.priority** : basse, normale, haute, urgente
- **tasks.status** : a_faire, en_cours, en_attente, terminee, annulee
- **training_sessions.modality** : presentiel, en_ligne, hybride
- **transactions.direction** : entree, sortie
- **transactions.status** : brouillon, soumise, validee, annulee
- **financial_categories.direction** : entree, sortie

### Vérification croisée code ↔ SQL

✅ Toutes les tables utilisées dans le code React sont présentes dans `candi_flow.sql`.
✅ `_prisma_migrations` est ignorée dans la migration PHP.
⚠️ `training_categories` : table présente en SQL mais **0 lignes** — le filtre par catégorie sur `/formations` affiche « Toutes les catégories » par défaut.

---

## 4. Authentification

### Mécanisme

- **JWT** signé HS256 avec `jose`, expiration 7 jours, stocké en mémoire / localStorage (via `getToken()` dans `session.ts`)
- **Connexion e-mail** : email + mot de passe bcrypt (`bcryptjs`, facteur 12)
- **Connexion téléphone** : numéro → email virtuel `<digits>@tel.candi.gn` + mot de passe
- **Inscription téléphone** : code OTP 6 chiffres envoyé par SMS (Nimba SMS), valide 10 min, 3 tentatives max
- **Réinitialisation mot de passe** : code OTP par SMS, nouveau mot de passe défini à la validation

### Rôles et accès

| Rôle | Accès principales |
|---|---|
| `super_admin` | Tout, y compris paramètres passerelle paiement |
| `admin` | Tout sauf paramètres paiement |
| `comptable` | Finances, inscriptions, rapports |
| `responsable_formation` | Catalogue, inscriptions |
| `formateur` | Gestion catalogue |
| `commercial` | Inscriptions, CRM |
| `gestionnaire_immobilier` | Immobilier, paiements loyers |
| `client` | Tableau de bord, espace apprenant, paiements, mon logement |

### Normalisation téléphone

- `normalizePhoneDigits` : supprime caractères non numériques, `00` en préfixe, ajoute `224` si 9 chiffres
- Recherche par téléphone : d'abord `users.email = '<digits>@tel.candi.gn'`, puis `profiles.phone` normalisé

### Création automatique de compte locataire

À la création d'un bail avec numéro de téléphone, un compte `user` + `profile` + rôle `client` est créé automatiquement avec le numéro de téléphone comme mot de passe par défaut.

---

## 5. Fichiers uploadés (Stockage)

### État actuel

Les fichiers sont déjà stockés **localement** dans `public/uploads/` du projet React :

| Fichier | Chemin en base | Présent |
|---|---|---|
| Image offering CaNDI GESCOM | `/uploads/offering-images/1792060810765-61915481.png` | ✅ |
| Cours Informatique de base (PPTX) | `/uploads/course-materials/fc36120a.../1792150716732-58e37742.pptx` | ✅ |

### Images statiques (déjà dans `public/`)

Toutes les images héros, le logo, la photo fondateur sont dans `public/` du projet React, accessibles directement. À copier dans `public/` du projet PHP :

- `candi-logo.png`, `candi-logo-trim.png`
- `fondateur-candi.png`
- `hero-candi-1.jpg`, `hero-candi-4.jpg` → `hero-candi-8.jpg`
- `hero-candi-equipe-2.jpg`
- `favicon.png`, `apple-touch-icon.png`, `icon-192.png`, `icon-512.png`, `icon-maskable-512.png`
- `manifest.webmanifest`

> **Aucun téléchargement depuis Supabase Storage nécessaire.** Tout est déjà en local.

---

## 6. Edge Functions / Logique serveur

Ce projet utilise **TanStack Start** (SSR / server functions) — pas de Supabase Edge Functions séparées.

La logique serveur est dans `src/lib/*.server.ts` :

| Fichier | Responsabilité |
|---|---|
| `auth.server.ts` | Bcrypt, JWT sign/verify, signIn, signUp, findUserByPhone |
| `cinetpay.server.ts` | Initialisation paiement, vérification statut, finalisation |
| `nimba.server.ts` | Envoi SMS via Nimba SMS API |
| `sms.server.ts` | Dispatch SMS générique |
| `phone-auth.server.ts` | OTP téléphone (création code, envoi, vérification) |
| `data.functions.ts` | Toutes les fonctions CRUD Prisma |
| `payments.functions.ts` | Gestion paiements en ligne, liste des payables |
| `enrollments.functions.ts` | Demande d'inscription |
| `admin.functions.ts` | Paramètres passerelle |
| `rent-reminders.functions.ts` | Relances de loyer (SMS) |
| `seed.server.ts` | Données initiales |

---

## 7. Système de paiement

### Opérateur unique

- **CinetPay** : API `https://api-checkout.cinetpay.com/v2`

### Flux complet CinetPay

1. Client visite `/paiements`, voit ses éléments payables (`listPayablesFn`)
2. Clique « Payer » → `startOnlinePaymentFn` :
   - Vérifie que l'élément appartient bien à l'utilisateur (ownership check)
   - Lit le montant depuis la BDD (**jamais depuis le navigateur**)
   - Crée une ligne `online_payments` avec statut `initie`
   - Appelle CinetPay API → reçoit `payment_url`
   - Redirige le navigateur vers `payment_url`
3. CinetPay appelle le webhook `POST /api/public/cinetpay/notify` avec `cpm_trans_id`
4. Le webhook re-vérifie le statut auprès de CinetPay (`checkCinetPayStatus`)
5. Si `SUCCESS` → `finalizePayment` : update `online_payments.status = 'reussi'`, crée un `rent_payment` ou envoie une notification, et notifie les admins
6. La page `/paiement-retour?id=<id>` appelle `refreshOnlinePaymentFn` qui re-vérifie et affiche le statut final
7. **Idempotence** : `finalizePayment` utilise un `updateMany` conditionnel (`status = 'initie'`) — une même notification ne peut finaliser qu'une fois

### Types de paiements

| Purpose | Source | Action après succès |
|---|---|---|
| `enrollment` | `enrollments` | Notification admins/comptables |
| `crm_subscription` | `crm_subscriptions` | Notification admins/comptables |
| `rent` | `leases` | Crée `rent_payment` + notification gestionnaires |

---

## 8. Variables d'environnement

Identifiées dans le code source :

| Variable | Utilisée dans | Valeur par défaut / Note |
|---|---|---|
| `JWT_SECRET` | `auth.server.ts` | `'fallback-secret-change-me'` — **à changer impérativement** |
| `DATABASE_URL` | `prisma.ts` | URL de connexion MySQL Prisma |
| Clés CinetPay (site_id, apikey) | `payment_gateway_settings` table | Stockées en BDD, gérées via UI admin |
| Clés Nimba SMS (api_key, sender) | `payment_gateway_settings` table | Stockées en BDD (provider = nimba_sms) |

> Pour le projet PHP, les secrets seront dans `.env` et les clés de paiement resteront dans la table `payment_gateway_settings`.

---

## 9. Dépendances front à reproduire

| Bibliothèque React | Équivalent PHP/JS vanilla |
|---|---|
| `@radix-ui/*` (Dialog, Sheet, Tabs, Select, Accordion…) | JS vanilla (composants custom, voir ci-dessous) |
| `sonner` (toasts) | Toast CSS + JS vanilla |
| `embla-carousel-react` | JS vanilla (intersection + transform) |
| `lucide-react` (icônes SVG) | Icônes SVG inline dans les vues PHP |
| `framer-motion` | **Non utilisé** (animations via Tailwind transitions) |
| Tailwind CSS v4 | CSS compilé réutilisé depuis le build React |
| Google Fonts : Plus Jakarta Sans + Syne | `<link>` Google Fonts dans le layout PHP |

### Composants interactifs à reproduire en JS vanilla

| Composant | Comportement |
|---|---|
| Carrousel hero (accueil) | `setInterval` 5 s, transition `opacity` |
| Menu mobile (Sheet) | Overlay latéral droit, fond semi-transparent, fermeture clic extérieur |
| Onglets auth (Tabs) | Onglets CSS + JS vanilla |
| Sélecteurs formations (Select) | `<select>` natif ou custom JS |
| Filtres formations | Filtrage client JS sur la liste HTML |
| Toasts | Div positionnée, animée, supprimée après 4 s |
| Modal/Dialog | Overlay + trap focus |
| Notifications unread badge | Compteur affiché sur l'icône |

---

## 10. Design System

| Token | Valeur (oklch) |
|---|---|
| `--primary` | oklch(0.45 0.083 209) — bleu foncé |
| `--accent` | oklch(0.82 0.24 145) — vert/jaune |
| `--background` | oklch(0.985 0.006 200) — blanc cassé |
| `--sidebar` | oklch(0.32 0.065 209) — bleu très foncé |
| `--footer` | variable (couleur sombre) |
| `--radius` | 0.75rem |
| Font sans | Plus Jakarta Sans |
| Font display | Syne |

**Stratégie CSS :** Compiler Tailwind sur le projet React (`npm run build`) et copier le CSS résultant dans `public/assets/css/`. Configurer Tailwind pour scanner aussi les fichiers `.php`.

---

## Résumé des points d'attention avant Phase 2

1. ✅ **SQL déjà MySQL** — pas de conversion PostgreSQL nécessaire.
2. ✅ **Images déjà locales** — copie simple de `public/` React vers `public/` PHP.
3. ✅ **Mots de passe bcrypt** — `password_verify()` PHP compatible nativement.
4. ⚠️ **JWT → sessions PHP** — l'auth JWT sera remplacée par des sessions PHP sécurisées. Les utilisateurs existants se reconnectent normalement (mots de passe inchangés).
5. ⚠️ **OTP téléphone** — `phone_verifications` : logique OTP à reproduire en PHP + appel API Nimba SMS.
6. ⚠️ **Fournisseur de paiement unique : CinetPay** — un seul webhook `/api/public/cinetpay/notify` à implémenter.
7. ⚠️ **Contenu About éditable** — `app_settings.about_page` (JSON) : la page `/a-propos` charge ce JSON et le fusionne avec les valeurs par défaut. Un éditeur admin est prévu.
8. ⚠️ **`_prisma_migrations`** — à exclure du schéma PHP.
9. ⚠️ **`training_categories` vide** — le filtre sera présent mais sans entrées (comportement normal).
10. ⚠️ **`training_sessions.start_date` / `end_date`** — stockées en varchar (`YYYY-MM-DD`), pas en DATE.
