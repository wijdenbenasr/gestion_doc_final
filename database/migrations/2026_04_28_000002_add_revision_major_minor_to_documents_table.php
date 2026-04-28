<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->integer('revision_major')->nullable()->after('revision');
            $table->integer('revision_minor')->nullable()->default(0)->after('revision_major');
        });

        // Assign revision_major based on creation order (by created_at)
        $documents = DB::table('documents')->orderBy('created_at')->get();
        $major = 1;
        foreach ($documents as $doc) {
            DB::table('documents')->where('id', $doc->id)->update([
                'revision_major' => $major,
                'revision_minor' => 0,
            ]);
            $major++;
        }
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['revision_major', 'revision_minor']);
        });
    }
};
