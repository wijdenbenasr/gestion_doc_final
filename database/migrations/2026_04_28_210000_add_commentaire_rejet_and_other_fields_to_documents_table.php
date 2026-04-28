<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'commentaire_rejet')) {
                $table->text('commentaire_rejet')->nullable()->after('pdf_signe_final');
            }
            if (!Schema::hasColumn('documents', 'deadline_correction')) {
                $table->timestamp('deadline_correction')->nullable()->after('commentaire_rejet');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'commentaire_rejet')) {
                $table->dropColumn('commentaire_rejet');
            }
            if (Schema::hasColumn('documents', 'deadline_correction')) {
                $table->dropColumn('deadline_correction');
            }
        });
    }
};
