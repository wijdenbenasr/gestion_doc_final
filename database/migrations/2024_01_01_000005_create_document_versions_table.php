<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->string('revision');                // Ex: 1.0, 1.1, 2.0
            $table->string('file_path');
            $table->string('hash');                    // SHA-256 de cette version
            $table->foreignId('created_by')->constrained('users');
            $table->text('comment')->nullable();       // Commentaire de version
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
