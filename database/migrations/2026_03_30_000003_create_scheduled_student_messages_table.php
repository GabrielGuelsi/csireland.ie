<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_student_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('message_sequence_id')->constrained('message_sequences')->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('message_templates')->cascadeOnDelete();
            $table->date('scheduled_for');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'scheduled_for']);
            $table->index(['scheduled_for', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_student_messages');
    }
};
