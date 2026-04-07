<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Types de documents autorisés.
     * Valeurs utilisées dans la colonne ENUM 'type'.
     *
     * fmea_process                 → FMEA Process
     * sop                          → SOP – Standard Operating Process / Work Instruction
     * defect_catalogue             → Defect Catalogue
     * control_plan                 → Control Plan
     * process_flow_chart           → Process Flow Chart
     * process_parameters_sheet     → Process Parameters Sheet
     * control_sheet                → Control Sheet
     * rework_instruction           → Rework Instruction
     * quality_wall_instruction     → Quality Wall Instruction
     * checklist_cleaning_tracking  → Checklist & Cleaning Tracking
     * safety_sheet                 → Safety Sheet at the Workstation
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            // ── Identification ───────────────────────────────────────────────
            $table->string('name');                    // Nom du document
            $table->string('code')->nullable()->unique(); // Code attribué par l'admin (ex: QMS-SOP-001)

            // ── Classification ───────────────────────────────────────────────
            $table->enum('type', [
                'fmea_process',
                'sop',
                'defect_catalogue',
                'control_plan',
                'process_flow_chart',
                'process_parameters_sheet',
                'control_sheet',
                'rework_instruction',
                'quality_wall_instruction',
                'checklist_cleaning_tracking',
                'safety_sheet',
            ]);

            $table->enum('aio', [
                'aio1',
                'aio2',
                'aio3',
                'aio4',
                'aio5',
            ]);

            $table->string('ligne');                   // Ligne de production

            // ── Phase / Série ────────────────────────────────────────────────
            // 'serie'  → production série (champ nom_serie optionnel)
            // 'projet' → mode projet     (champ nom_phase OBLIGATOIRE)
            $table->enum('phase', ['serie', 'projet']);
            $table->string('nom_phase')->nullable();   // Nom de la phase (obligatoire si phase=projet)
            $table->string('nom_serie')->nullable();   // Numéro/nom de série (optionnel si phase=serie)

            // ── Fichier ───────────────────────────────────────────────────────
            $table->string('file_path');               // Chemin stockage privé
            $table->string('file_original_name');      // Nom original du fichier uploadé
            $table->string('hash')->nullable();        // SHA-256 du fichier pour intégrité

            // ── Workflow ─────────────────────────────────────────────────────
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('current_owner_id')->nullable()->constrained('users');

            $table->integer('version')->default(1);
            $table->string('revision')->default('1.0');

            $table->enum('status', [
                'draft',                // Brouillon (chez le créateur)
                'pending_codification', // En attente de code par l'admin
                'in_validation',        // En cours de validation (checker → approver → admin)
                'rejected',             // Rejeté, retour au créateur
                'finalized',            // Signé par tous, archivé, verrouillé
            ])->default('draft')->index();

            $table->string('current_role')->nullable()->index(); // creator | validator | approver | admin
            $table->timestamp('deadline')->nullable();           // Délai de traitement

            // ── Drapeaux ─────────────────────────────────────────────────────
            $table->boolean('is_pdf')->default(false);
            $table->boolean('is_fully_signed')->default(false);
            $table->integer('lock_version')->default(0);        // Optimistic locking

            $table->softDeletes();
            $table->timestamps();
        });

        // ── Journal d'audit ───────────────────────────────────────────────────
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');                  // Ex: document_submitted, user_approved …
            $table->string('auditable_type');          // Classe du modèle (App\Models\Document)
            $table->unsignedBigInteger('auditable_id');
            $table->json('payload')->nullable();       // Données additionnelles
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('documents');
    }
};
