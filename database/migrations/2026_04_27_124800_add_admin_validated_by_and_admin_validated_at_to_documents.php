<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_validated_by')->nullable();
            $table->timestamp('admin_validated_at')->nullable();
            $table->foreign('admin_validated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['admin_validated_by']);
            $table->dropColumn(['admin_validated_by', 'admin_validated_at']);
        });
    }
};