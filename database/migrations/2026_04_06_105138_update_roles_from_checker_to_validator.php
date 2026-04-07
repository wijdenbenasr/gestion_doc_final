<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update users table enum
        DB::statement(
            "ALTER TABLE users MODIFY COLUMN `role` ENUM('creator', 'validator', 'approver', 'admin') NULL"
        );

        // Update documents table enum
        DB::statement(
            "ALTER TABLE documents MODIFY COLUMN `current_role` ENUM('creator', 'validator', 'approver', 'admin') NULL"
        );

        // Update document_signatures table enum
        DB::statement(
            "ALTER TABLE document_signatures MODIFY COLUMN `role` ENUM('creator', 'validator', 'approver', 'admin') NOT NULL"
        );

        // Update transmissions table enums
        DB::statement(
            "ALTER TABLE transmissions MODIFY COLUMN `from_role` ENUM('creator', 'validator', 'approver', 'admin', 'archive') NULL"
        );
        DB::statement(
            "ALTER TABLE transmissions MODIFY COLUMN `to_role` ENUM('creator', 'validator', 'approver', 'admin', 'archive') NOT NULL"
        );

        // Update data
        DB::table('users')->where('role', 'checker')->update(array('role' => 'validator'));
        DB::table('documents')->where('current_role', 'checker')->update(array('current_role' => 'validator'));
        DB::table('document_signatures')->where('role', 'checker')->update(array('role' => 'validator'));
        DB::table('transmissions')->where('from_role', 'checker')->update(array('from_role' => 'validator'));
        DB::table('transmissions')->where('to_role', 'checker')->update(array('to_role' => 'validator'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse data
        DB::table('users')->where('role', 'validator')->update(array('role' => 'checker'));
        DB::table('documents')->where('current_role', 'validator')->update(array('current_role' => 'checker'));
        DB::table('document_signatures')->where('role', 'validator')->update(array('role' => 'checker'));
        DB::table('transmissions')->where('from_role', 'validator')->update(array('from_role' => 'checker'));
        DB::table('transmissions')->where('to_role', 'validator')->update(array('to_role' => 'checker'));

        // Reverse enums
        DB::statement(
            "ALTER TABLE users MODIFY COLUMN role ENUM('creator', 'checker', 'approver', 'admin')"
        );
        DB::statement(
            "ALTER TABLE documents MODIFY COLUMN current_role ENUM('creator', 'checker', 'approver', 'admin')"
        );
        DB::statement(
            "ALTER TABLE document_signatures MODIFY COLUMN role ENUM('creator', 'checker', 'approver', 'admin')"
        );
        DB::statement(
            "ALTER TABLE transmissions MODIFY COLUMN from_role ENUM('creator', 'checker', 'approver', 'admin', 'archive')"
        );
        DB::statement(
            "ALTER TABLE transmissions MODIFY COLUMN to_role ENUM('creator', 'checker', 'approver', 'admin', 'archive')"
        );
    }
};
