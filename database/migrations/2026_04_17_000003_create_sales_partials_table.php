<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_partials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_period_goal_id')->constrained()->cascadeOnDelete();
            $table->date('partial_date');
            $table->boolean('is_closing')->default(false);
            $table->text('highlights')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_partials');
    }
};
