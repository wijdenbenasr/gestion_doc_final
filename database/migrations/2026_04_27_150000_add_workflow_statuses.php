<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE documents MODIFY COLUMN status ENUM('draft', 'pending_codification', 'in_validation', 'in_approbation', 'validation_admin', 'ready_for_pdf', 'pdf_converted', 'signing_validator', 'signing_approver', 'signing_admin', 'finalized', 'archived', 'rejected') DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE documents MODIFY COLUMN status ENUM('draft', 'pending_codification', 'in_validation', 'ready_for_pdf', 'rejected', 'finalized') DEFAULT 'draft'");
    }
};
