<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM(
                'new_assignment','birthday','exam_today','visa_expiry',
                'first_contact_overdue','scheduled_message','gift_ready',
                'exam_approaching','no_contact_overdue','followup_due',
                'additional_form_submission','application_dispatch'
            ) NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM(
                'new_assignment','birthday','exam_today','visa_expiry',
                'first_contact_overdue','scheduled_message','gift_ready',
                'exam_approaching','no_contact_overdue','followup_due',
                'additional_form_submission'
            ) NOT NULL");
        }
    }
};
