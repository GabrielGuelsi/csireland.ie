<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Realized values captured when application_status moves to 'enrolled'.
            // Distinct from sales_price/course/university/intake (which are estimates from the form),
            // because the actual course/uni/intake/price the student ends up at can differ.
            $table->string('completed_course')->nullable();
            $table->string('completed_university')->nullable();
            $table->string('completed_intake')->nullable();
            $table->decimal('completed_price', 10, 2)->nullable();
            $table->timestamp('completed_at')->nullable()->index();

            // Captured when application_status moves to 'cancelled'.
            // Prefixed with `application_` to avoid colliding with the existing
            // free-text `cancellation_reason` used by the CS workflow.
            $table->string('application_cancellation_reason')->nullable();
            $table->string('application_cancellation_stage')->nullable();
            $table->timestamp('application_cancelled_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'completed_course',
                'completed_university',
                'completed_intake',
                'completed_price',
                'completed_at',
                'application_cancellation_reason',
                'application_cancellation_stage',
                'application_cancelled_at',
            ]);
        });
    }
};
