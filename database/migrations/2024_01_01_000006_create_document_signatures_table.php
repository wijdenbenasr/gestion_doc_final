<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');

            $table->string('role');
            // creator | validator | approver | admin

            $table->string('hash')->nullable();
            // Hash SHA-256 au moment de la signature

            $table->integer('order');
            // Ordre de signature (1→4)

            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->unique(array('document_id', 'role'));
            // 1 signature par rôle par document
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_signatures');
    }
};
