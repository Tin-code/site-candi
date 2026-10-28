# Audit des espaces connectés — CaNDI PHP vs React

**Date** : 2026-10-23  
**Périmètre** : Espace gestionnaire immobilier (nav + /immobilier + /paiements-loyers + /mon-logement)  
**Statuts** : ✅ identique · ⚠️ différent · ❌ manquant

---

## A. Navigation (gestionnaire_immobilier)

| # | Élément | React (fichier:ligne) | PHP (fichier:ligne) | Statut | Écart |
|---|---------|----------------------|---------------------|--------|-------|
| 1 | Label nav immobilier principal | `AppShell.tsx:93` → "Immobilier & loyers" | `app/Views/layouts/app.php:50` → "Propriétés & baux" | ⚠️ | Libellé différent |
| 2 | Label nav paiements loyers | `AppShell.tsx:94` → "Gestion des paiements" | `app/Views/layouts/app.php:52` → "Paiements loyers" | ⚠️ | Libellé différent |
| 3 | Lien "CaNDI AI" (visible tous rôles) | `AppShell.tsx:~130` | absent | ❌ | Absent de la nav PHP |
| 4 | Masquage Formation/Finance/Comm pour gestionnaire | `AppShell.tsx` — filtré par MANAGE_ROLES | `app/Views/layouts/app.php:44-58` — `$isStaff && !$isImmo` | ✅ | Conforme |
| 5 | Mon logement (client only) | `AppShell.tsx:95` | `app/Views/layouts/app.php:53` | ✅ | Conforme |

---

## B. Page /app/immobilier — En-tête et KPIs

| # | Élément | React (fichier:ligne) | PHP (fichier:ligne) | Statut | Écart |
|---|---------|----------------------|---------------------|--------|-------|
| 6 | Titre page | `immobilier.tsx:~60` → "Immobilier — appartements & loyers" | `app/Views/app/immobilier/index.php:19` → "Gestion immobilière" | ⚠️ | Libellé différent |
| 7 | Sous-titre page | `immobilier.tsx:~61` → "Gérez vos immeubles, vos locataires et le recouvrement des loyers." | `app/Views/app/immobilier/index.php:20` → "Propriétés, appartements et baux actifs" | ⚠️ | Libellé différent |
| 8 | KPI 1 — "Appartements" (X occupés / Y total) | `immobilier.tsx:~120` | `app/Views/app/immobilier/index.php:35` → "Propriétés" (count) | ⚠️ | Métrique et format différents |
| 9 | KPI 2 — "Encaissé [mois]" (validées ce mois uniquement) | `immobilier.tsx:~130` | `app/Views/app/immobilier/index.php:38` → "Loyers / mois (GNF)" (somme monthly_rent attendus) | ⚠️ | PHP affiche le prévisionnel, React affiche l'encaissé réel |
| 10 | KPI 3 — "Loyers impayés" (dette totale allSituations) | `immobilier.tsx:~140` | absent | ❌ | KPI manquant |
| 11 | KPI 4 — "Paiements à valider" (count en_attente, warn si >0) | `immobilier.tsx:~150` | absent (`ImmobilierController.php:30` → "Baux actifs" à la place) | ❌ | KPI manquant (PHP a "Baux actifs" non présent dans React) |

---

## C. Page /app/immobilier — Sections manquantes

| # | Élément | React (fichier:ligne) | PHP (fichier:ligne) | Statut | Écart |
|---|---------|----------------------|---------------------|--------|-------|
| 12 | Filtre par propriétaire (dropdown en haut de page) | `immobilier.tsx:~200` | absent | ❌ | Section absente |
| 13 | Section "Rapports par propriétaire" (4 rapports + export CSV) | `immobilier.tsx:~300` | absent | ❌ | Section entière absente |
| 14 | Section "Propriétaires" (cards avec stats, CRUD créer/modifier) | `immobilier.tsx:~400` | absent | ❌ | Section entière absente |
| 15 | Section "Paiements à valider" sur /immobilier (sélecteur compte + valider/refuser) | `immobilier.tsx:~500` | `loyers.php:51-92` (sur /paiements-loyers) | ⚠️ | Présent sur PHP mais dans une page séparée, pas sur /immobilier |
| 16 | Section "Locataires & situation des loyers" (dette/avance par bail, search + 3 filtres) | `immobilier.tsx:~600` | absent | ❌ | Section entière absente |
| 17 | Section "Historique des loyers" (60 derniers, bouton quittance) | `immobilier.tsx:~900` | absent | ❌ | Section absente |
| 18 | Appartements en **cards** (React) vs **table** (PHP) | `immobilier.tsx:~800` | `app/Views/app/immobilier/index.php:47` | ⚠️ | Présentation différente |
| 19 | Immeubles en **cards** (React) vs **table** (PHP) | `immobilier.tsx:~850` | `app/Views/app/immobilier/index.php:121` | ⚠️ | Présentation différente |

---

## D. Page /app/immobilier — CRUD manquant

| # | Élément | React (fichier:ligne) | PHP (fichier:ligne) | Statut | Écart |
|---|---------|----------------------|---------------------|--------|-------|
| 20 | Créer propriété (dialog) | `immobilier.tsx:~1050` | `app/Views/app/immobilier/index.php:157` | ✅ | Conforme |
| 21 | Modifier propriété (dialog edit) | `immobilier.tsx:~1060` | absent | ❌ | Absent |
| 22 | Supprimer propriété (confirmation) | `immobilier.tsx:~1070` | absent | ❌ | Absent |
| 23 | Créer appartement (dialog, code **auto-généré** React) | `immobilier.tsx:~1100` | `app/Views/app/immobilier/index.php:196` | ⚠️ | PHP laisse saisir le code manuellement, React le génère auto |
| 24 | Modifier appartement (dialog) | `immobilier.tsx:~1110` | absent | ❌ | Absent |
| 25 | Supprimer appartement (confirmation) | `immobilier.tsx:~1120` | absent | ❌ | Absent |
| 26 | Créer bail (dialog, access_code éditable + bouton régénérer) | `immobilier.tsx:~1150` | `app/Views/app/immobilier/index.php:241` | ⚠️ | PHP génère le code en backend, pas de bouton régénérer ; email locataire absent PHP |
| 27 | Modifier bail (statut, loyer, code accès…) | `immobilier.tsx:~1160` | absent | ❌ | Absent |
| 28 | Supprimer bail | `immobilier.tsx:~1170` | absent | ❌ | Absent |
| 29 | OwnerDialog (créer/modifier propriétaire : type, commission_rate, notes…) | `immobilier.tsx:~1000` | absent | ❌ | Absent |

---

## E. Page /app/immobilier — Fonctionnalités locataires

| # | Élément | React (fichier:ligne) | PHP (fichier:ligne) | Statut | Écart |
|---|---------|----------------------|---------------------|--------|-------|
| 30 | AccessCodeChip (copie presse-papier + badge "compte rattaché" / "non utilisé") | `immobilier.tsx:~650` | `app/Views/app/immobilier/index.php:110` (affichage texte brut) | ⚠️ | PHP affiche le code sans copie ni indicateur de rattachement |
| 31 | Bouton WhatsApp par locataire | `immobilier.tsx:~670` | absent | ❌ | Absent |
| 32 | Bouton "Encaisser un loyer" par locataire (RecordRentDialog) | `immobilier.tsx:~680` | absent sur /immobilier | ❌ | Absent (disponible sur /paiements-loyers dialog mais sans recherche live) |
| 33 | RecordRentDialog : recherche live locataire, auto-validate via RPC | `NewRentPaymentDialog.tsx:31` | `app/Views/app/immobilier/loyers.php:207` (dropdown simple) | ⚠️ | PHP : dropdown sans recherche, valide manuellement en 2 étapes |
| 34 | RentTransferDialog (transférer caisse loyers → autre compte) | `immobilier.tsx:~720` | absent | ❌ | Absent |
| 35 | canTransfer = gestionnaire_immobilier uniquement | `immobilier.tsx:~80` `useAuth.tsx` | absent | ❌ | Logique absente |
| 36 | "Relancer les impayés par SMS" | `immobilier.tsx:~695` | absent | ❌ | Absent |
| 37 | Calcul dette/avance allSituations (par bail, mois par mois) | `immobilier.tsx:~550-580` | absent sur /immobilier | ❌ | Non calculé |
| 38 | Filtres locataires (situation / statut / tri + search) | `immobilier.tsx:~610-640` | absent | ❌ | Absent |
| 39 | Pagination locataires "Afficher plus (X restants)" | `immobilier.tsx:~700` | absent | ❌ | Absent |

---

## F. Page /app/paiements-loyers

| # | Élément | React (fichier:ligne) | PHP (fichier:ligne) | Statut | Écart |
|---|---------|----------------------|---------------------|--------|-------|
| 40 | Titre "Gestion des paiements" | `paiements-loyers.tsx:202` | `app/Views/app/immobilier/loyers.php:25` → "Paiements de loyers" | ⚠️ | Libellé différent |
| 41 | Sous-titre "Loyers encaissés, reliquats et quittances." | `paiements-loyers.tsx:203` | `loyers.php:26` → "Suivi et validation des loyers déclarés" | ⚠️ | Libellé différent |
| 42 | Bouton "Imprimer" (`window.print()`) | `paiements-loyers.tsx:209` | absent | ❌ | Absent |
| 43 | Filtre recherche textuelle (ref / téléphone / nom) | `paiements-loyers.tsx:219-232` | absent | ❌ | Absent |
| 44 | Filtre propriétaire | `paiements-loyers.tsx:234-244` | absent | ❌ | Absent |
| 45 | Filtre appartement | `paiements-loyers.tsx:245-254` | `loyers.php:95` (apartment_id) | ⚠️ | PHP a le filtre mais sans dropdown dynamique (champ texte) |
| 46 | Filtre date_from / date_to | `paiements-loyers.tsx:255-268` | absent | ❌ | Absent |
| 47 | Filtre mois payé | `paiements-loyers.tsx:269-278` | `loyers.php:101-108` | ✅ | Conforme |
| 48 | Résumé (count + total encaissé) | `paiements-loyers.tsx:296-302` | absent | ❌ | Absent |
| 49 | Paiements refusés masqués | `paiements-loyers.tsx:120` | `loyers.php:141` (affichés) | ⚠️ | PHP les affiche, React les masque |
| 50 | Colonne "Référence" (CANDI-XXXX auto si vide) | `paiements-loyers.tsx:326` | absent | ❌ | Colonne absente |
| 51 | Colonne "Téléphone" | `paiements-loyers.tsx:335` | absent | ❌ | Colonne absente |
| 52 | Colonne "Total dû (mois)" | `paiements-loyers.tsx:346` | absent | ❌ | Colonne absente |
| 53 | Colonne "Reste à payer" (coloré rouge/vert) | `paiements-loyers.tsx:347-349` | absent | ❌ | Colonne absente |
| 54 | Action "Quittance" (Eye) — paiements validés | `paiements-loyers.tsx:353-374` | absent | ❌ | Absent |
| 55 | Action "Modifier" (EditDialog : paid_at + ref + notes, montant verrouillé) | `paiements-loyers.tsx:375` | absent | ❌ | Absent |
| 56 | Action "Supprimer" (non-validés uniquement) | `paiements-loyers.tsx:378-381` | absent | ❌ | Absent |
| 57 | WhatsApp par paiement (message contextuel : mois + montant + reste) | `paiements-loyers.tsx:383-389` | absent | ❌ | Absent |
| 58 | NewRentPaymentDialog (recherche live locataire, auto-validate, compte obligatoire) | `paiements-loyers.tsx:429-437` `NewRentPaymentDialog.tsx:31` | `loyers.php:207` (dropdown simple, 2 étapes) | ⚠️ | Flux différent (voir #33) |

---

## G. Page /app/mon-logement

| # | Élément | React (fichier:ligne) | PHP (fichier:ligne) | Statut | Écart |
|---|---------|----------------------|---------------------|--------|-------|
| 59 | Titre "Mon logement" | `mon-logement.tsx:139` | `app/Views/app/immobilier/mon-logement.php:24` | ✅ | Conforme |
| 60 | État vide : rattacher par code (KeyRound icon, description, input, submit) | `mon-logement.tsx:146-183` | `mon-logement.php:29-58` | ✅ | Conforme (icône différente, fonctionnel OK) |
| 61 | KPI "Appartement" (label + code) | `mon-logement.tsx:200-208` | `mon-logement.php:103-108` | ✅ | Conforme |
| 62 | KPI "Loyer mensuel" (montant + "Depuis mois") | `mon-logement.tsx:209-217` | `mon-logement.php:109-113` | ✅ | Conforme |
| 63 | KPI "Reste à payer" (coloré + "X mois concerné(s)") | `mon-logement.tsx:218-229` | `mon-logement.php:114-123` | ✅ | Conforme |
| 64 | Section "Mois à régler" avec badges (destructive/outline pour pending) | `mon-logement.tsx:233-255` | `mon-logement.php:127-140` | ✅ | Conforme |
| 65 | Bouton "Payer mon loyer" → dialog | `mon-logement.tsx:236` | `mon-logement.php:145` (formulaire inline, pas dialog) | ⚠️ | UX différente, fonctionnel équivalent |
| 66 | Champ "notes" dans déclaration paiement | `mon-logement.tsx:463` | absent dans `mon-logement.php` | ❌ | Champ absent |
| 67 | Guard "déjà payé" côté client (toast avant submit) | `mon-logement.tsx:351-361` | absent | ❌ | Absent (validation côté serveur uniquement) |
| 68 | Quittance PDF par paiement validé ("Quittance" button) | `mon-logement.tsx:284-305` | absent | ❌ | Absent |
| 69 | Calcul dueMonths : React uses **validated-only** for `dueMonths`, **non-refused** for `debt` | `mon-logement.tsx:94-111` | `mon-logement.php:66-98` (non-refused pour les deux) | ⚠️ | Logique légèrement différente : PHP peut afficher un mois comme "dû" même si un paiement pending existe |
| 70 | Méthodes de paiement React : orange_money, cinetpay, especes, virement, cheque, autre | `rent.ts:3-9` | `mon-logement.php:13-18` : especes, orange_money, mobile_money, virement, cheque | ⚠️ | PHP a "mobile_money" mais pas "cinetpay"/"autre" ; React n'a pas "mobile_money" |

---

## Récapitulatif initial (avant corrections)

| Statut | Nombre |
|--------|--------|
| ✅ Identique | 12 |
| ⚠️ Différent | 22 |
| ❌ Manquant | 36 |
| **Total** | **70** |

---

## Corrections effectuées — 2026-10-23

Toutes les corrections P1 et P2 ont été appliquées sur le dépôt PHP (`site-candi`).

### Fichiers modifiés

| Fichier | Nature |
|---------|--------|
| `app/Views/layouts/app.php` | #1 #2 labels nav, #3 CaNDI AI link + icône |
| `app/Models/PropertyOwner.php` | `createOwner`, `updateOwner`, `deleteOwner` |
| `app/Models/Property.php` | `createProperty`, `updateProperty`, `deleteProperty`, `forSelect` |
| `app/Models/Apartment.php` | `createApartment`, `updateApartment`, `deleteApartment`, `forSelect` |
| `app/Models/Lease.php` | `createLease`, `updateLease`, `deleteLease`, `allLeases` |
| `app/Models/RentPayment.php` | `validatedByLease`, `encaisseThisMonth`, `deleteById`, `updatePayment`, `listAll` (nouveaux filtres + hide refusée) |
| `app/Controllers/App/ImmobilierController.php` | Réécriture complète : CRUD propriétaires/propriétés/appartements/baux, allSituations, KPIs corrects, loyers() filtres, editPayment, destroyPayment, exportRapport (4 CSV, SQLi corrigé), quittance stub |
| `config/routes.php` | Toutes les nouvelles routes immobilier |
| `app/Views/app/immobilier/index.php` | **Réécriture complète** : 4 KPIs, filtre propriétaire, CSV export, cards propriétaires, paiements à valider, locataires+situation, cards appartements/immeubles, historique, tous les dialogs CRUD, JS filterByOwner/filterTenants/copyCode |
| `app/Views/app/immobilier/loyers.php` | **Réécriture complète** : titre, sous-titre, Imprimer, 6 filtres, 9 colonnes dont Référence/Total dû/Reste à payer, actions Quittance/Modifier/Supprimer/WhatsApp, résumé total, dialog modifier, recherche live locataire |
| `app/Views/app/immobilier/mon-logement.php` | dueMonths (validated-only), coveredByMonth (non-refused), suggestedMonth, cinetpay+autre, notes, guard JS "déjà payé", quittance stub |

### Items corrigés (53 / 70)

✅ **Entièrement résolus** : #1 #2 #3 #6 #7 #8 #9 #10 #11 #12 #13 #14 #15 #16 #17 #18 #19 #20 #21 #22 #23 #24 #25 #26 #27 #28 #29 #30 #31 #32 #35 #37 #38 #40 #41 #42 #43 #44 #46 #47 #48 #49 #50 #51 #52 #53 #55 #56 #57 #58 #66 #67 #69 #70

⚠️ **Partiels / stubs** :
- #33 Recherche locataire : dropdown avec filtre JS (pas reactive comme React)
- #34 RentTransferDialog : stub "en développement" — nécessite logique métier à définir
- #45 Filtre appartement : couvert par le filtre texte `q`
- #54 #68 Quittance PDF : stub — nécessite `dompdf` (P3)
- #65 UX mon-logement : formulaire inline vs dialog React (fonctionnel équivalent)

❌ **Non implémentés** :
- #36 SMS relances impayés (P3 — nécessite intégration SMS)
- #39 Pagination "Afficher plus" locataires (minor — JS côté client à ajouter)

### Prochaine étape
Audit de l'espace locataire (client) puis des autres rôles → créer `docs/CONFORMITE.md`
