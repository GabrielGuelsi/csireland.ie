<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('type', 30);                         // paid | gov_free | gov_50 | other_bonificado
            $table->string('source', 10);                       // form | admin
            $table->string('status', 30)->default('pending');   // awaiting_payment | pending | issued | received | sent_to_cs
            $table->unsignedInteger('price_cents')->nullable(); // what the student paid (paid only)
            $table->unsignedInteger('cost_cents')->nullable();  // what it costs us (for bonificado report)
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->json('form_payload')->nullable();           // raw form fields for unmatched cases
            $table->string('matched_by', 20)->nullable();       // phone | email | name | null (unmatched)
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_policies');
    }
};
