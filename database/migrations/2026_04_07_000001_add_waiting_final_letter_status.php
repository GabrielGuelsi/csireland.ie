<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE students MODIFY COLUMN status ENUM(
                'waiting_initial_documents',
                'first_contact_made',
                'waiting_offer_letter',
                'waiting_english_exam',
                'waiting_duolingo',
                'waiting_reapplication',
                'waiting_college_documents',
                'waiting_college_response',
                'waiting_final_letter',
                'waiting_payment',
                'waiting_student_response',
                'cancelled',
                'concluded'
            ) NOT NULL DEFAULT 'waiting_initial_documents'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE students MODIFY COLUMN status ENUM(
                'waiting_initial_documents',
                'first_contact_made',
                'waiting_offer_letter',
                'waiting_english_exam',
                'waiting_duolingo',
                'waiting_reapplication',
                'waiting_college_documents',
                'waiting_college_response',
                'waiting_payment',
                'waiting_student_response',
                'cancelled',
                'concluded'
            ) NOT NULL DEFAULT 'waiting_initial_documents'");
        }
    }
};
