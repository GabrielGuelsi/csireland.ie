<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('days_after_first_contact');
            $table->foreignId('template_id')->constrained('message_templates')->cascadeOnDelete();
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_sequences');
    }
};
