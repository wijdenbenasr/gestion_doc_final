# QMS Doc Control — Plateforme GED

Application web de **Gestion Électronique des Documents** développée avec **Laravel 11**.

---

## 🚀 Installation rapide

```bash
# 1. Cloner le dépôt
git clone https://github.com/wijdenbenasr/gestion_doc_wijden.git
cd gestion_doc_wijden

# 2. Installer les dépendances PHP
composer install

# 3. Configurer l'environnement
cp .env.example .env
php artisan key:generate

# 4. Configurer la base de données dans .env
#    DB_DATABASE=gestion_doc   DB_USERNAME=root   DB_PASSWORD=...

# 5. Créer la base de données et appliquer les migrations
php artisan migrate --seed

# 6. Créer le lien symbolique pour le stockage public (si nécessaire)
php artisan storage:link

# 7. Lancer le serveur de développement
php artisan serve
```

**Compte admin par défaut (dev only) :** `admin@example.com` / `admin1234`

---

## 🔁 Workflow documentaire (13 étapes)

```
[1] Créateur      → Crée le document (upload fichier)
[2] Créateur      → Envoie à l'Admin pour codification
[3] Admin         → Attribue un code unique (ex: QMS-SOP-AIO1-001)
[4] Admin         → Renvoie au Créateur avec le code
[5] Créateur      → Envoie au Vérificateur
[6] Vérificateur  → Valide (→ Approbateur) OU Rejette (→ Créateur)
[7] Approbateur   → Approuve (→ Admin) OU Rejette (→ Créateur)
[8] Admin         → Signature finale
[9] Système       → Archivage automatique + verrouillage
```

---

## 📋 Types de documents supportés

| Valeur DB | Libellé affiché |
|---|---|
| `fmea_process` | FMEA Process |
| `sop` | SOP – Standard Operating Process / Work Instruction |
| `defect_catalogue` | Defect Catalogue |
| `control_plan` | Control Plan |
| `process_flow_chart` | Process Flow Chart |
| `process_parameters_sheet` | Process Parameters Sheet |
| `control_sheet` | Control Sheet |
| `rework_instruction` | Rework Instruction |
| `quality_wall_instruction` | Quality Wall Instruction |
| `checklist_cleaning_tracking` | Checklist & Cleaning Tracking |
| `safety_sheet` | Safety Sheet at the Workstation |

## 🏭 AIO disponibles

`AIO 1` · `AIO 2` · `AIO 3` · `AIO 4` · `AIO 5`

## 📌 Phases

- **Série** : production série (champ "Numéro de série" optionnel)
- **Projet** : mode projet (champ "Nom de la phase" **OBLIGATOIRE**)

---

## 🗄️ Base de données

| Table | Description |
|---|---|
| `users` | Utilisateurs, rôles, statut d'approbation |
| `email_verification_codes` | Codes de vérification 6 chiffres |
| `documents` | Documents avec ENUM type/aio/phase/status |
| `document_versions` | Historique de toutes les versions |
| `document_signatures` | Signatures horodatées par rôle |
| `transmissions` | Historique complet des transferts entre rôles |
| `notifications` | Notifications système (Laravel standard) |
| `archives` | Documents finalisés archivés |
| `audit_logs` | Journal de toutes les actions |
| `cache`, `cache_locks` | Cache Laravel |
| `jobs`, `job_batches`, `failed_jobs` | File de travail |
| `sessions` | Sessions base de données |
| `password_reset_tokens` | Réinitialisation mot de passe |

---

## 👥 Rôles et permissions

| Rôle | Permissions |
|---|---|
| `creator` | Créer, modifier, supprimer (brouillon), soumettre |
| `validator` | Valider ou rejeter les documents en attente |
| `approver` | Approuver ou rejeter les documents validés |
| `admin` | Codifier, signer, superviser, gérer les comptes |

---

## 🔐 Sécurité

- **RBAC** : middleware `role:xxx` sur chaque groupe de routes
- **Intégrité SHA-256** : hash calculé à chaque upload, vérifié à chaque signature
- **Verrouillage optimiste** : colonne `lock_version` sur les documents
- **Audit trail** : toutes les actions journalisées dans `audit_logs`
- **Headers sécurité** : CSP, X-Frame-Options, X-XSS-Protection, Referrer-Policy
- **Stockage privé** : fichiers dans `storage/app/private`, non accessibles directement

---

## 📁 Architecture

```
app/
├── Http/Controllers/
│   ├── Admin/
│   │   ├── AdminDashboardController.php
│   │   ├── AdminCodificationController.php
│   │   └── UserApprovalController.php
│   ├── AuthController.php
│   ├── DocumentController.php
│   ├── DocumentWorkflowController.php
│   ├── DownloadController.php
│   └── ExportController.php
├── Models/
│   ├── User, Document, DocumentVersion
│   ├── DocumentSignature, AuditLog
│   ├── Archive, Transmission
│   └── EmailVerificationCode
├── Services/
│   ├── WorkflowService.php
│   ├── SignatureService.php
│   ├── DocumentService.php
│   └── AuditService.php
└── Repositories/
    └── Eloquent/EloquentDocumentRepository.php

database/migrations/  (7 fichiers ordonnés, propres, sans doublons)
resources/views/
├── layouts/app.blade.php
├── auth/  (login, register, verify-email, forgot-password, reset-password)
├── documents/  (create, creator-index, validator-index, approver-index, export/pdf)
└── admin/  (dashboard, codification/index, users/pending)
```
