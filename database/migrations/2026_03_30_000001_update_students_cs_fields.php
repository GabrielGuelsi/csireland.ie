<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: add new columns (separate call — SQLite can't mix drop+add in one call)
        Schema::table('students', function (Blueprint $table) {
            $table->enum('status', [
                'waiting_initial_documents',
                'waiting_offer_letter',
                'waiting_english_exam',
                'waiting_duolingo',
                'waiting_reapplication',
                'waiting_college_documents',
                'waiting_college_response',
                'waiting_payment',
                'waiting_student_response',
                'cancelled',
                'concluded',
            ])->default('waiting_initial_documents');

            $table->enum('priority', ['high', 'medium', 'low'])->nullable();
            $table->date('visa_expiry_date')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('observations')->nullable();
            $table->string('system', 20)->nullable();
            $table->timestamp('gift_received_at')->nullable();
        });

        // Step 2: drop old column
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('pipeline_stage');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->enum('pipeline_stage', ['first_contact', 'exam', 'payment', 'visa', 'complete'])
                  ->default('first_contact');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'status', 'priority', 'visa_expiry_date', 'date_of_birth',
                'observations', 'system', 'gift_received_at',
            ]);
        });
    }
};
