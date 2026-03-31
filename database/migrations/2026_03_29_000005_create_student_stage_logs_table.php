<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_stage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('from_stage', 50);
            $table->string('to_stage', 50);
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('changed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_stage_logs');
    }
};
