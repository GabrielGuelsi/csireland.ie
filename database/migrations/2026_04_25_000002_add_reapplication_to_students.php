<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Snapshot of the first application — frozen when a reapplication takes over the active fields.
            $table->string('original_product_type', 30)->nullable()->after('product_type');
            $table->string('original_course')->nullable()->after('original_product_type');
            $table->string('original_university')->nullable()->after('original_course');
            $table->string('original_intake', 10)->nullable()->after('original_university');
            $table->decimal('original_sales_price', 10, 2)->nullable()->after('original_intake');

            $table->unsignedSmallInteger('reapplication_count')->default(0)->after('original_sales_price');
            $table->timestamp('last_reapplied_at')->nullable()->after('reapplication_count');
        });

        // Index added separately so dropIndex works deterministically on SQLite rollback.
        Schema::table('students', function (Blueprint $table) {
            $table->index('reapplication_count');
        });

        Schema::create('pending_reapplications', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('whatsapp_phone')->nullable()->index();
            $table->string('product_raw', 100)->nullable();
            $table->json('form_payload');
            $table->string('status', 20)->default('pending')->index();   // pending | matched | rejected
            $table->foreignId('matched_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('matched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('matched_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_reapplications');

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['reapplication_count']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'original_product_type',
                'original_course',
                'original_university',
                'original_intake',
                'original_sales_price',
                'reapplication_count',
                'last_reapplied_at',
            ]);
        });
    }
};
