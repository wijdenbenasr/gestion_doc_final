<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('fichier_signe_path')->nullable()->after('file_original_name');
            $table->boolean('pdf_converti')->default(false)->after('fichier_signe_path');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['fichier_signe_path', 'pdf_converti']);
        });
    }
};