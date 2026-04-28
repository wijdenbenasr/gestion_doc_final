<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `documents` MODIFY `status` ENUM(
            'draft',
            'pending_codification',
            'in_validation',
            'approbation',
            'validation_admin',
            'ready_for_pdf',
            'pdf_converti',
            'signing_validator',
            'signing_approver',
            'signing_admin',
            'archived',
            'rejected',
            'EN_MODIFICATION'
        ) DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `documents` MODIFY `status` ENUM(
            'draft',
            'pending_codification',
            'in_validation',
            'approbation',
            'validation_admin',
            'ready_for_pdf',
            'pdf_converti',
            'signing_validator',
            'signing_approver',
            'signing_admin',
            'finalized',
            'rejected'
        ) DEFAULT 'draft'");
    }
};
