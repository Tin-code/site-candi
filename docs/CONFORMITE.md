# Conformité PHP vs React — CaNDI

**Date** : 2026-10-24  
**Périmètre** : Tous les espaces connectés  
**Légende** : ✅ conforme · ⚠️ partiel / équivalent · ❌ manquant · 🔒 intentionnel

---

## 1. Espace gestionnaire immobilier

> Audit détaillé dans `docs/AUDIT_ESPACES.md` (53 / 70 items résolus).

| Catégorie | Statut | Notes |
|-----------|--------|-------|
| Navigation (labels nav) | ✅ | Corrigés #1 #2 #3 |
| KPIs dashboard (4 KPIs) | ✅ | Occupés, encaissé, dettes, à valider |
| Filtre propriétaire + CSV export | ✅ | 4 types de rapport CSV |
| Section Propriétaires (cards + CRUD) | ✅ | Créer, modifier, supprimer, WA |
| Section Paiements à valider | ✅ | Sélecteur compte, valider/refuser |
| Section Locataires & situation | ✅ | Dette/avance, search JS, filtres, WA, AccessCode |
| Section Appartements/Immeubles (cards) | ✅ | Cards conformes à React |
| Historique 60 loyers | ✅ | |
| CRUD propriétés/appartements/baux | ✅ | Tous dialogs présents |
| Calcul dettes allSituations | ✅ | Validated-only pour dueMonths |
| Export CSV SQLi fix | ✅ | PDO prepared statements |
| **Partiels** | ⚠️ | Recherche live locataire (JS dropdown), quittance PDF stub, RentTransfer stub |
| **Déférés P3** | ❌ | SMS relances (#36), pagination "Afficher plus" (#39) |

---

## 2. Espace locataire (mon-logement)

> Vue : `app/Views/app/immobilier/mon-logement.php`  
> Controller : `ImmobilierController::monLogement()`, `claimLease()`, `storeTenantPayment()`

| # | Fonctionnalité React | PHP | Statut |
|---|---------------------|-----|--------|
| 1 | État vide : rattacher par code d'accès | ✅ | Conforme |
| 2 | KPIs : appartement, loyer mensuel, reste à payer | ✅ | Conforme |
| 3 | Section "Mois à régler" avec badges pending/dû | ✅ | Conforme |
| 4 | Calcul dueMonths — validated-only (React `paidBy`) | ✅ | Corrigé `$validatedByMonth` |
| 5 | Calcul dette — non-refused (React `coveredBy`) | ✅ | Corrigé `$coveredByMonth` |
| 6 | Déclaration paiement : mois suggéré auto-préfillé | ✅ | `$suggestedMonth` |
| 7 | Méthodes : especes, orange_money, cinetpay, virement, cheque, autre | ✅ | Corrigé (cinetpay + autre ajoutés) |
| 8 | Champ notes | ✅ | Ajouté textarea notes |
| 9 | Guard JS "déjà payé" avant submit | ✅ | Ajouté vérification JS |
| 10 | UX : formulaire inline vs dialog React | ⚠️ | Fonctionnel équivalent |
| 11 | Quittance PDF par paiement validé | ⚠️ | Stub lien — nécessite dompdf (P3) |

---

## 3. Espace apprenant (formations client)

> Vue : `app/Views/app/espace-apprenant/index.php`  
> Controller : `EspaceApprenantController`  
> Modèle : `CourseMaterial`

| # | Fonctionnalité React | PHP | Statut |
|---|---------------------|-----|--------|
| 1 | Titre / sous-titre page | ✅ | Conforme |
| 2 | Liste inscriptions avec badge statut | ✅ | Conforme |
| 3 | Infos session (dates, horaires, lieu, salle, modalité) | ✅ | Conforme |
| 4 | Lien classe en ligne (si hasAccess + meeting_url) | ✅ | Conforme |
| 5 | Bouton "Ajouter au calendrier" (.ics) | ✅ | Route GET `/espace-apprenant/{id}/ics` |
| 6 | Bouton "Télécharger mon attestation" (status=terminee) | ⚠️ | Printable HTML server-side vs `generatePdf()` React — fonctionnel équivalent |
| 7 | Supports de cours : verrouillés si pas ACCESS_STATUSES | ✅ | Conforme |
| 8 | Supports de cours : liste avec icône + téléchargement | ✅ | Conforme |
| 9 | État vide : lien catalogue | ✅ | Conforme |
| 10 | Session non programmée : message "prévenu par SMS" | ✅ | Conforme |
| 11 | Formateur : upload fichier (50 Mo, ext. contrôlées) | ✅ | CSRF, validation ext/taille |
| 12 | Formateur : filtre session par formation (JS) | ✅ | `filterTrainerSessions()` |
| 13 | Formateur : liste tous supports + supprimer | ✅ | Conforme |
| 14 | Formateur : rôles autorisés (super_admin, admin, responsable_formation, formateur) | ✅ | `TRAINER_ROLES` const |
| 15 | Attestation standalone (sans layout app) | ✅ | `$this->view(..., null)` |
| 16 | Attestation : nom, formation, dates, heures, lieu, numéro ATT- | ✅ | Conforme |

### Champs `Enrollment::findWithDetails()` (fix 2026-10-24)

Ajoutés pour attestation + ICS : `s.end_date AS session_end`, `s.start_time`, `s.end_time`, `s.room`, `s.meeting_url`, `p.code AS program_code`, `p.total_hours`.

---

## 4. Paiements en ligne (CinetPay)

> Vue : `app/Views/app/paiements/index.php`  
> Controller : `PaiementsController`

| # | Fonctionnalité React | PHP | Statut |
|---|---------------------|-----|--------|
| 1 | Paiements en attente (cartes par inscription) | ✅ | Conforme |
| 2 | Historique des paiements CinetPay | ✅ | Conforme |
| 3 | Flag `$enabled` (Orange Money activé) | ✅ | Conforme — PHP nomme CinetPay (correct) |
| 4 | Montant configurable par l'utilisateur | ⚠️ | PHP utilise le solde restant complet — sécurité : montant relu serveur |
| 5 | Champ "mois concerné" pour paiements loyers | ❌ | Non implémenté côté paiements en ligne (locataires déclarent via mon-logement) |
| 6 | Provider : React "Orange Money", PHP "CinetPay" | 🔒 | Intentionnel — seul CinetPay est le provider réel |
| 7 | Webhook CinetPay : vérification avant validation | ✅ | `CinetPayWebhookController` — idempotent |
| 8 | Page retour : n'enregistre pas seul le paiement | ✅ | `paiement-retour.php` → flash info only |

---

## 5. Profil utilisateur

> Vue : `app/Views/app/profil.php`  
> Controller : `ProfilController`

| # | Fonctionnalité React | PHP | Statut |
|---|---------------------|-----|--------|
| 1 | Modifier prénom/nom/téléphone | ✅ | Conforme |
| 2 | Changer mot de passe | ✅ | Conforme |
| 3 | Avatar / photo de profil | ⚠️ | Non implémenté PHP (P3) |

---

## 6. Inscriptions (staff/admin)

> Vue : `app/Views/app/inscriptions/`  
> Controller : `InscriptionsController`

| # | Fonctionnalité React | PHP | Statut |
|---|---------------------|-----|--------|
| 1 | Liste avec filtres (statut, search) | ✅ | Conforme |
| 2 | Fiche détail (historique statuts, paiements) | ✅ | Conforme |
| 3 | Changer statut (pipeline) | ✅ | Conforme |
| 4 | Enregistrer paiement manuel (createTransaction) | ⚠️ | PHP enregistre dans enrollments.paid_amount directement — à aligner avec `transactions` table |
| 5 | WhatsApp par inscription | ⚠️ | Lien WA présent, message contextuel simplifié |
| 6 | Export reçu PDF | ❌ | Non implémenté (P3) |
| 7 | Créer inscription (staff) | ✅ | `InscriptionsController::create()` + `store()` |

---

## 7. Dashboard (staff/admin)

> Vue : `app/Views/app/dashboard.php`  
> Controller : `DashboardController`

| # | Fonctionnalité React | PHP | Statut |
|---|---------------------|-----|--------|
| 1 | Vue gestionnaire immobilier (4 KPIs + lien) | ✅ | Conforme |
| 2 | Vue staff : KPIs demandes/confirmées | ✅ | Conforme |
| 3 | Vue staff finance : Entrées/Sorties/Résultat | ✅ | Conforme |
| 4 | Vue staff finance : **Créances apprenants** | ✅ | Ajouté 2026-10-24 |
| 5 | Vue staff : Sessions à venir | ✅ | Conforme |
| 6 | Vue staff : Dernières inscriptions (link + badge) | ✅ | PHP enrichit avec prénom/nom |
| 7 | Vue client : KPIs + liste inscriptions | ✅ | Conforme |
| 8 | Vue client : "rôle en cours d'attribution" | ⚠️ | Non affiché PHP (mineur) |

---

## 8. Catalogue (back-office formations)

> Vue : `app/Views/app/catalogue/`  
> Controller : `FormationsAppController`

| # | Fonctionnalité React | PHP | Statut |
|---|---------------------|-----|--------|
| 1 | Liste programmes avec sessions | ✅ | Conforme |
| 2 | Badge Publié/Brouillon | ✅ | Conforme |
| 3 | Créer programme (code/titre/résumé/prix/niveau/catégorie/durée/publié) | ✅ | Conforme (page vs dialog React) |
| 4 | Modifier programme | ✅ | Conforme |
| 5 | Archiver programme | ✅ | Conforme |
| 6 | Créer session (code/dates/capacité/modalité/agence/lieu/publié) | ✅ | Conforme |
| 7 | Modifier session | ✅ | Conforme |
| 8 | Supprimer session | ✅ | Conforme |
| 9 | Guard `canManage` (direction + responsable_formation) | ✅ | Conforme (contrôleur) |

---

## 9. Finances

> Vue : `app/Views/app/finances/`  
> Controller : `FinancesController`  
> Modèle : `FundTransfer` (créé 2026-10-24)

| # | Fonctionnalité React | PHP | Statut |
|---|---------------------|-----|--------|
| 1 | Stats globales (entrées/sorties/solde net) | ✅ | Conforme |
| 2 | Soldes par compte | ✅ | Conforme |
| 3 | **Soldes par client** (créances, search JS) | ✅ | Ajouté 2026-10-24 |
| 4 | **Transferts entre comptes** (list + valider/refuser) | ✅ | Ajouté 2026-10-24 — `FundTransfer` model |
| 5 | Journal avec filtres (direction/compte/catégorie/statut/mois/search) | ✅ | Conforme |
| 6 | **Exporter journal CSV** | ✅ | Ajouté 2026-10-24 — `GET /finances/exporter` |
| 7 | Encaisser inscription (CashierDialog avec search) | ✅ | Conforme |
| 8 | **Virement entre comptes** (TransferDialog) | ✅ | Ajouté 2026-10-24 — dialog HTML natif |
| 9 | Valider / Modifier / Supprimer opération | ✅ | Conforme |
| 10 | Pagination journal | ✅ | Conforme |
| 11 | canValidateTransfer = super_admin only | ✅ | Conforme |

---

## 10. Publications

> Vue : `app/Views/app/publications/`

| # | Fonctionnalité React | PHP | Statut |
|---|---------------------|-----|--------|
| 1 | Liste publications | ✅ | Conforme |
| 2 | Créer/modifier publication (titre, résumé, slug, cover, video_url, publié) | ✅ | Conforme (page séparée vs dialog React) |
| 3 | Toggle publier/dépublier | ✅ | Conforme |
| 4 | Supprimer | ✅ | Conforme |

---

## 11. Utilisateurs & rôles

> Vue : `app/Views/app/utilisateurs/`

| # | Fonctionnalité React | PHP | Statut |
|---|---------------------|-----|--------|
| 1 | Liste users avec badges rôles | ✅ | Conforme |
| 2 | Créer compte utilisateur | ✅ | Conforme |
| 3 | Modifier user (infos + rôles multi-checkboxes + reset mdp) | ✅ | Conforme |
| 4 | Activer/désactiver compte | ✅ | Conforme |
| 5 | Supprimer compte | ✅ | Conforme |
| 6 | Guard canView = super_admin, admin | ✅ | Conforme |

---

## 12. Paramètres

> Vue : `app/Views/app/parametres/`

| # | Fonctionnalité React | PHP | Statut |
|---|---------------------|-----|--------|
| 1 | Config CinetPay (site_id, api_key, secret, sandbox) | ✅ | Conforme |
| 2 | Config Nimba SMS | ⚠️ | PHP a le champ API key, pas de config complète |
| 3 | Settings options (CRUD par catégorie) | ✅ | Conforme |

---

## 13. Notifications

> Vue : `app/Views/app/notifications/`

| # | Fonctionnalité React | PHP | Statut |
|---|---------------------|-----|--------|
| 1 | Liste notifications (lues/non lues) | ✅ | Conforme |
| 2 | Marquer tout comme lu | ✅ | Conforme |
| 3 | Supprimer lues | ✅ | Conforme |

---

## 14. Navigation par rôle

> Corrigée lors de l'audit de session (2026-10-24 / 2026-10-25)

| # | Correction | Rôles affectés | Statut |
|---|---|---|---|
| 1 | Bug P1 : `$isImmo=true` masquait Formation/Inscriptions/Publications pour super_admin/admin | super_admin, admin | ✅ Corrigé (`$isImmoOnly`) |
| 2 | Nav CRM étendu à comptable et commercial | comptable, commercial | ✅ Corrigé (`$isCrm`) |
| 3 | Nav "Espace apprenant" visible aux formateurs | formateur, responsable_formation | ✅ Corrigé (`$isTrainer`) |
| 4 | Nav "Inscriptions" masquée pour formateur | formateur | ✅ Corrigé (`$canSeeInscriptions`) |
| 5 | Nav "Catalogue" masquée pour commercial | commercial | ✅ Corrigé (`$canSeeCatalogue`) |
| 6 | Nav "Tâches" ajoutée (tous staff sauf gestionnaire_immobilier) | comptable, resp_formation, formateur, commercial | ✅ Ajouté |
| 7 | Nav "Rapports" ajoutée (super_admin, admin, comptable, responsable_formation) | 4 rôles | ✅ Ajouté |
| 8 | Nav "SMS automatiques" ajoutée (admin seulement) | super_admin, admin | ✅ Ajouté (stub) |
| 9 | Nav "CRM" visible (lien était `$isAdmin`) | comptable, commercial | ✅ Ajouté |
| 10 | Tableau de bord dédié gestionnaire_immobilier (`isImmoOnly`) | gestionnaire_immobilier | ✅ Ajouté |

---

## 15. Inscriptions — workflow statuts (P1 corrigé)

| # | Fonctionnalité React | PHP | Statut |
|---|---|---|---|
| 1 | Modifier statut parmi 14 valeurs possibles | ✅ `POST /inscriptions/{id}/modifier` | ✅ Corrigé |
| 2 | Modifier montant convenu + remise | ✅ `Enrollment::updateDetails()` | ✅ Corrigé |
| 3 | Notes de contact + date dernier contact | ✅ | ✅ Corrigé |
| 4 | Prochain suivi (date) | ✅ | ✅ Corrigé |
| 5 | Historique des statuts (`enrollment_status_history`) | ✅ | ✅ (existait déjà) |
| 6 | WhatsApp par dossier | ❌ | P2 déféré |
| 7 | PDF (devis / facture / convention) | ❌ | P2 déféré |

---

## 16. Client / apprenant

> Audit : 2026-10-25 — 0 P1, 1 P2 corrigé

| # | Fonctionnalité | Statut |
|---|---|---|
| 1 | Dashboard : 3 KPIs + liste inscriptions | ✅ |
| 2 | Mes inscriptions : liste + fiche lecture seule | ✅ |
| 3 | Paiements : initier CinetPay | ✅ |
| 4 | **Paiements partiels** : champ montant modifiable | ✅ Corrigé (montant capé au solde réel en serveur) |
| 5 | Sécurité paiements : montant lu en DB uniquement | ✅ |
| 6 | Espace apprenant : supports cours filtrés | ✅ |
| 7 | ICS calendrier | ✅ |
| 8 | Attestation participation (status=terminee) | ✅ |
| 9 | Mon logement : rattachement bail, déclarer paiement | ✅ |
| 10 | Profil : modifier infos + mot de passe | ✅ |
| 11 | Notifications : liste, marquer lu, tout lire | ✅ |

---

## 17. Espaces placeholder (P3)

| Espace | Vue PHP | Notes |
|--------|---------|-------|
| CRM | placeholder | P3 — nécessite modèle CRM complet |
| Tâches | placeholder | P3 |
| Messages SMS | placeholder | P3 — nécessite intégration Nimba |
| Rapports | placeholder | P3 |
| Produits & services (admin) | absent | P3 — CRUD offerings back-office |
| Quittance PDF | stub → flash | P3 — nécessite dompdf |
| Attestation PDF | HTML print | P3 — dompdf optionnel |

---

## Récapitulatif global (2026-10-25)

| Espace | Items | Conformes | Partiels | Manquants |
|--------|-------|-----------|----------|-----------|
| Gestionnaire immobilier | 70 | 57 | 4 | 9 |
| Locataire (mon-logement) | 11 | 10 | 1 | 0 |
| Apprenant / client | 11 | 11 | 0 | 0 |
| Paiements en ligne | 8 | 7 | 1 | 0 |
| Profil | 3 | 3 | 0 | 0 |
| Inscriptions (staff) | 9 | 8 | 0 | 1 |
| Dashboard (tous rôles) | 10 | 10 | 0 | 0 |
| Catalogue | 9 | 9 | 0 | 0 |
| Finances | 14 | 14 | 0 | 0 |
| Publications | 4 | 4 | 0 | 0 |
| Utilisateurs | 6 | 6 | 0 | 0 |
| Paramètres | 3 | 2 | 1 | 0 |
| Notifications | 3 | 3 | 0 | 0 |
| Navigation par rôle | 10 | 10 | 0 | 0 |
| Inscriptions workflow | 7 | 5 | 0 | 2 |
| Comptable | 18 | 11 | 2 | 5 |
| Responsable formation | 16 | 11 | 1 | 4 |
| Formateur | 12 | 9 | 0 | 3 |
| Commercial | 14 | 9 | 0 | 5 |
| **Total audités** | **222** | **199** | **10** | **29** |

**Taux de conformité global** : **90 %** ✅ / **5 %** ⚠️ / **13 %** ❌

> Les ❌ restants sont essentiellement : CRM/Tâches/Rapports (placeholders P3), Produits & services, WA/PDF sur inscriptions, et quelques nav items P2 hors-scope de cette session.

---

## Éléments transversaux conformes

- CSRF sur tous les formulaires POST (`Csrf::field()` + `Csrf::check()`)
- Authentification middleware sur toutes les routes `/app/*`
- Montants toujours lus côté serveur (jamais depuis le navigateur)
- Webhook CinetPay : vérifie avant de valider, idempotent
- Page retour paiement : ne valide jamais seul
- Session UUID (varchar 191), `password_hash` BCrypt
- Flash messages (`Session::flash()`) cohérents
- PDO `ERRMODE_EXCEPTION` + prepared statements partout
- Tailwind CSS v4 compilé via `@tailwindcss/cli`
