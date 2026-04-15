<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('application_status')->nullable()->index();
            $table->text('application_notes')->nullable();
            $table->date('college_application_date')->nullable();
            $table->date('college_response_date')->nullable();
            $table->timestamp('offer_letter_received_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'application_status',
                'application_notes',
                'college_application_date',
                'college_response_date',
                'offer_letter_received_at',
            ]);
        });
    }
};
