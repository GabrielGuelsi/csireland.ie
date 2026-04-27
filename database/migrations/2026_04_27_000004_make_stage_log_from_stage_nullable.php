<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The first stage transition for a sales-lead is null → 'cadastro' (no prior
 * stage). The existing CS-only schema enforced NOT NULL on from_stage because
 * every CS stage change has a prior stage. Relax it so sales-lead creation can
 * insert the initial log row.
 *
 * Safe for prod: existing rows all have from_stage populated; relaxing the
 * constraint affects no existing data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_stage_logs', function (Blueprint $table) {
            $table->string('from_stage')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('student_stage_logs', function (Blueprint $table) {
            $table->string('from_stage')->nullable(false)->change();
        });
    }
};
