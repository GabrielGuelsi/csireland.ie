<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modify enum only on MySQL — SQLite doesn't enforce enum and doesn't support MODIFY COLUMN
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

        Schema::table('notifications', function (Blueprint $table) {
            $table->json('data')->nullable()->after('read_at');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('data');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM(
                'new_assignment',
                'sla_breach',
                'daily_digest'
            ) NOT NULL");
        }
    }
};
