<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('condition_type', ['no_contact_days', 'sla_overdue', 'exam_approaching_days']);
            $table->unsignedInteger('condition_value')->nullable(); // days threshold (null for sla_overdue)
            $table->enum('priority_filter', ['high', 'medium', 'low'])->nullable(); // null = any
            $table->json('status_filter')->nullable(); // null = all active statuses
            $table->string('notification_message'); // supports {name} and {status} placeholders
            $table->boolean('auto_escalate_to_high')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Add new notification types to enum (MySQL only)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM(
                'new_assignment',
                'sla_breach',
                'daily_digest',
                'birthday',
                'exam_today',
                'exam_approaching',
                'visa_expiry',
                'first_contact_overdue',
                'no_contact_overdue',
                'scheduled_message',
                'gift_ready'
            ) NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rules');

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM(
                'new_assignment',
                'sla_breach',
                'daily_digest',
                'birthday',
                'exam_today',
                'visa_expiry',
                'first_contact_overdue',
                'scheduled_message',
                'gift_ready'
            ) NOT NULL");
        }
    }
};
