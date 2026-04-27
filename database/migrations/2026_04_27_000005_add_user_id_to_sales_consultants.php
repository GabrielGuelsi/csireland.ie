<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link a SalesConsultant to a User (when that user is a sales_agent). Lets a
 * sales agent see their historical book of business — students that came in
 * via the Google Form webhook with their name as "Sales Advisor" and were
 * routed to a CS agent without ever passing through the new in-CRM handoff.
 *
 * Nullable FK: existing SalesConsultant rows stay un-linked until an admin
 * creates a sales_agent user with the matching name (the AgentController
 * does the link automatically on store/update).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_consultants', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()
                  ->after('id')
                  ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_consultants', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
