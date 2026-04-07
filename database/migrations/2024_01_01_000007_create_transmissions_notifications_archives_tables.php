<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Transmissions ─────────────────────────────────────────────────────
        // Historique complet des transferts entre rôles
        Schema::create('transmissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->string('from_role')->nullable();   // Rôle expéditeur
            $table->string('to_role');                 // Rôle destinataire
            $table->string('action');                  // submit | codify | validate | reject | sign
            $table->string('status')->default('done'); // done | pending
            $table->text('comment')->nullable();        // Commentaire ou motif de rejet
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ── Notifications ─────────────────────────────────────────────────────
        // Système de notifications Laravel standard
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // ── Archives ─────────────────────────────────────────────────────────
        // Documents finalisés — archivage automatique après signature admin
        Schema::create('archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->useCurrent();
            $table->string('archive_code')->nullable(); // Code d'archivage physique optionnel
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archives');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('transmissions');
    }
};
