<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Call 1: pipeline + identity fields. Split per CLAUDE.md §11 SQLite rule
        // (never combine dropColumn + addColumn; here we're only adding, but we
        // also keep separate batches so any future `down()` can mirror cleanly).
        Schema::table('students', function (Blueprint $table) {
            $table->string('sales_stage')->nullable()->after('status');
            $table->foreignId('assigned_sales_agent_id')->nullable()
                  ->constrained('users')->nullOnDelete()
                  ->after('assigned_cs_agent_id');
            $table->string('primeiro_nome')->nullable()->after('name');
            $table->string('sobrenome')->nullable()->after('primeiro_nome');
            $table->string('nome_social')->nullable()->after('sobrenome');
            $table->string('temperature')->nullable()->after('priority');
            $table->unsignedTinyInteger('lead_quality')->nullable()->after('temperature');
        });

        // Call 2: sales-only tracking fields
        Schema::table('students', function (Blueprint $table) {
            $table->text('objection_reason')->nullable()->after('lead_quality');
            $table->dateTime('meeting_date')->nullable()->after('objection_reason');
            $table->dateTime('handed_off_at')->nullable()->after('meeting_date');
            $table->foreignId('handed_off_by')->nullable()
                  ->constrained('users')->nullOnDelete()
                  ->after('handed_off_at');
            $table->boolean('is_reapplication')->default(false)->after('handed_off_by');
            $table->unsignedInteger('current_journey_cycle')->default(1)->after('is_reapplication');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['handed_off_by']);
            $table->dropColumn([
                'objection_reason', 'meeting_date',
                'handed_off_at', 'handed_off_by',
                'is_reapplication', 'current_journey_cycle',
            ]);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['assigned_sales_agent_id']);
            $table->dropColumn([
                'sales_stage', 'assigned_sales_agent_id',
                'primeiro_nome', 'sobrenome', 'nome_social',
                'temperature', 'lead_quality',
            ]);
        });
    }
};
