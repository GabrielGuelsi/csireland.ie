<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('name');
            $table->string('email');
            $table->string('whatsapp_phone', 30)->nullable()->index();

            // Product & enrolment
            $table->enum('product_type', [
                'higher_education',
                'first_visa',
                'reapplication',
                'insurance',
                'emergencial_tax',
                'learn_protection',
                'other',
            ]);
            $table->string('product_type_other')->nullable();
            $table->string('course')->nullable();
            $table->string('university')->nullable();
            $table->string('intake', 100)->nullable();

            // Financials
            $table->decimal('sales_price', 10, 2)->nullable();
            $table->decimal('sales_price_scholarship', 10, 2)->nullable();

            // Pending & notes from form
            $table->text('pending_documents')->nullable();

            // Reapplication
            $table->enum('reapplication_action', ['keep_previous', 'cancel_previous'])->nullable();

            // Assignment
            $table->foreignId('sales_consultant_id')->constrained('sales_consultants')->cascadeOnDelete();
            $table->foreignId('assigned_cs_agent_id')->nullable()->constrained('users')->nullOnDelete();

            // Pipeline
            $table->enum('pipeline_stage', ['first_contact', 'exam', 'payment', 'visa', 'complete'])
                  ->default('first_contact');
            $table->date('exam_date')->nullable();
            $table->enum('exam_result', ['pending', 'pass', 'fail'])->default('pending');
            $table->enum('payment_status', ['pending', 'partial', 'confirmed'])->default('pending');
            $table->enum('visa_status', ['not_started', 'material_sent', 'answered', 'complete'])->default('not_started');

            // Timestamps
            $table->dateTime('form_submitted_at');
            $table->dateTime('first_contacted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
