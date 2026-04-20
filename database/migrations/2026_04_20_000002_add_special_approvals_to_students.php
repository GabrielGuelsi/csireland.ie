<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Condição diferenciada
            $table->json('special_condition_options')->nullable();
            $table->string('special_condition_other')->nullable();
            $table->string('special_condition_status', 20)->nullable()->index();
            $table->foreignId('special_condition_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('special_condition_reviewed_at')->nullable();
            $table->string('special_condition_review_notes', 1000)->nullable();

            // Entrada reduzida
            $table->decimal('reduced_entry_amount', 10, 2)->nullable();
            $table->string('reduced_entry_other')->nullable();
            $table->string('reduced_entry_status', 20)->nullable()->index();
            $table->foreignId('reduced_entry_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reduced_entry_reviewed_at')->nullable();
            $table->string('reduced_entry_review_notes', 1000)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['special_condition_reviewed_by']);
            $table->dropForeign(['reduced_entry_reviewed_by']);
            $table->dropColumn([
                'special_condition_options',
                'special_condition_other',
                'special_condition_status',
                'special_condition_reviewed_by',
                'special_condition_reviewed_at',
                'special_condition_review_notes',
                'reduced_entry_amount',
                'reduced_entry_other',
                'reduced_entry_status',
                'reduced_entry_reviewed_by',
                'reduced_entry_reviewed_at',
                'reduced_entry_review_notes',
            ]);
        });
    }
};
