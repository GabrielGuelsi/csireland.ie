<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Existing CS-only schema has several NOT NULL columns that don't apply to
     * a sales-lead at creation time (a lead starts with just first/last name +
     * phone, builds toward fully-qualified data, then handoff fills in the rest).
     *
     * Relaxing the NOT NULL is safe for existing CS rows (they all have these
     * fields populated by the webhook). Enables sales-leads to live in the same
     * table with status = NULL until handoff — primary signal that they're not
     * in the CS pipeline yet, complementing the global scope on Student.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('product_type')->nullable()->change();
            $table->dateTime('form_submitted_at')->nullable()->change();
            $table->string('status')->nullable()->default(null)->change();
        });

        // FK relax — keep separate per CLAUDE.md SQLite-compat rule
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedBigInteger('sales_consultant_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->string('product_type')->nullable(false)->change();
            $table->dateTime('form_submitted_at')->nullable(false)->change();
            $table->string('status')->nullable(false)->default('waiting_initial_documents')->change();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->unsignedBigInteger('sales_consultant_id')->nullable(false)->change();
        });
    }
};
