<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->date('next_followup_date')->nullable()->after('last_contacted_at');
            $table->string('next_followup_note', 500)->nullable()->after('next_followup_date');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['next_followup_date', 'next_followup_note']);
        });
    }
};
