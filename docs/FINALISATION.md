# Finalisation des espaces utilisateurs — Audit complet

> Généré le 2026-10-27 — Mis à jour 2026-10-27 (toutes Cas A implémentées)

## Légende des cas

| Code | Signification | Action |
|------|---------------|--------|
| **A** | Fonctionnalité présente et complète dans React, absente ou stub dans PHP | **Implémenter** |
| **B** | Marquée "en développement" dans React ET dans PHP | Afficher message "En cours" |
| **C** | Présente dans PHP (message/stub) mais pas du tout dans React | **Supprimer / nettoyer** |

---

## Tableau d'audit

| # | Espace | Élément | Fichier PHP | Source React | Cas | Statut |
|---|--------|---------|-------------|--------------|-----|--------|
| 1 | **Tâches** `/app/taches` | Page complète (liste, créer, modifier, filtres, assignation, priorités, échéances) | `TachesController.php` + `Task.php` + `Views/app/taches/` | `taches.tsx` | **A** | ✅ Implémenté |
| 2 | **Rapports** `/app/rapports` | KPIs (inscriptions, encaissements, dépenses, résultat), tableau par statut, export CSV | `RapportsController.php` + `Views/app/rapports/` | `rapports.tsx` | **A** | ✅ Implémenté |
| 3 | **CRM** `/app/crm` | Catalogue progiciels (CRUD), clients CRM (CRUD), abonnements (créer/annuler/supprimer), paiements mensuels batch, suivi dette/avance | `CrmController.php` + 4 modèles + `Views/app/crm/` | `crm.tsx` | **A** | ✅ Implémenté |
| 4 | **Immobilier** — Dialog transfert caisse | Dialog `#dlg-transfer` avec vrai formulaire POST → `/app/finances/transferts` | `Views/app/immobilier/index.php` l.728 | `immobilier.tsx` | **A** | ✅ Implémenté |
| 5 | **SMS automatiques** `/app/messages-sms` | Liste `outbound_messages` + bouton "Envoyer maintenant" (stub gracieux si non configuré) | `MessagesSmsController.php` + `Views/app/messages-sms/` | `messages-sms.tsx` | **A** | ✅ Implémenté |
| 6 | **Quittance de loyer** `/app/paiements-loyers/{id}/quittance` | Page HTML print-only (locataire, logement, propriétaire, montant, période) | `ImmobilierController::quittance()` + `Views/app/immobilier/quittance.php` | `paiements-loyers.tsx` + `lib/quittance.ts` | **A** | ✅ Implémenté |
| 7 | **Paiements en ligne** `/app/paiements` | Message "bientôt disponible" si CinetPay non configuré | `Views/app/paiements/index.php` | Comportement conditionnel voulu | **B** | ✅ Normal |
| 8 | `Views/auth/index.php` | Fichier mort — aucune route ne le rend | ~~supprimé~~ | — | **C** | 🗑 Supprimé |
| 9 | `Views/auth/reset.php` | Fichier mort — `showReset()` redirige sans jamais rendre la vue | ~~supprimé~~ | — | **C** | 🗑 Supprimé |
| 10 | `Views/auth/new-password.php` | Fichier mort — `showNewPassword()` redirige sans jamais rendre la vue | ~~supprimé~~ | — | **C** | 🗑 Supprimé |

---

## Fichiers créés dans cette session

### Modèles
- `app/Models/Task.php` — listAll, findById, createTask, updateTask, deleteTask, staffUsers
- `app/Models/CrmProduct.php` — allAdmin, allActive, findById, upsert
- `app/Models/CrmClient.php` — allAdmin, allActive, findById, upsert, delete
- `app/Models/CrmSubscription.php` — allWithDetails, findById, create, updateStatus, delete, hasPayments
- `app/Models/CrmPayment.php` — allWithDetails, forSubscription, createBatch (atomique avec transaction comptable)

### Contrôleurs (réécrits complets)
- `app/Controllers/App/RapportsController.php` — index (KPIs + table statuts), exportCsv (3 types)
- `app/Controllers/App/TachesController.php` — index, store, update, destroy
- `app/Controllers/App/CrmController.php` — index, storeProduct, updateProduct, storeClient, updateClient, destroyClient, storeSub, updateSub, destroySub, storePayment
- `app/Controllers/App/MessagesSmsController.php` — index, dispatch (stub gracieux)

### Vues créées
- `app/Views/app/rapports/index.php`
- `app/Views/app/taches/index.php` + `form.php`
- `app/Views/app/crm/index.php` (4 onglets : Abonnements, Paiements, Catalogue, Clients)
- `app/Views/app/messages-sms/index.php`
- `app/Views/app/immobilier/quittance.php` (layout null — standalone print-page)

### Vues modifiées
- `app/Views/app/immobilier/index.php` — dialog `#dlg-transfer` remplacé par formulaire POST réel

### Routes ajoutées dans `config/routes.php`
- `GET  /rapports/export` → `exportCsv`
- `POST /messages-sms/envoyer` → `dispatch`
- `POST /taches`, `POST /taches/{id}`, `POST /taches/{id}/supprimer`
- `POST /crm/produits`, `POST /crm/produits/{id}`
- `POST /crm/clients`, `POST /crm/clients/{id}`, `POST /crm/clients/{id}/supprimer`
- `POST /crm/abonnements`, `POST /crm/abonnements/{id}/statut`, `POST /crm/abonnements/{id}/supprimer`, `POST /crm/abonnements/{id}/paiement`

---

## Statut global

**Toutes les fonctionnalités Cas A ont été implémentées. Les Cas C ont été supprimés.**

Les seuls éléments restants en stub sont les intégrations de services externes non configurés :
- Envoi SMS réel (API SMS non configurée) — interface UI complète, envoi retourne message info
- Paiements CinetPay (clés API non configurées) — message "bientôt disponible"
