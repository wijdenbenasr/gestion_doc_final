<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_versions', function (Blueprint $table) {
            $table->string('type')->nullable()->after('file_path')->comment('Type de version: original, pdf_signe_createur, pdf_signe_validateur, pdf_signe_approbateur, pdf_signe_final');
        });
    }

    public function down(): void
    {
        Schema::table('document_versions', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
