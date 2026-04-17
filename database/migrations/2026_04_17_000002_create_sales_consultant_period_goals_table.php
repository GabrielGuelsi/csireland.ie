<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_consultant_period_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_period_goal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_consultant_id')->constrained()->cascadeOnDelete();
            $table->decimal('individual_minima', 12, 2);
            $table->decimal('individual_target', 12, 2);
            $table->decimal('individual_wow', 12, 2);
            $table->timestamps();

            $table->unique(
                ['sales_period_goal_id', 'sales_consultant_id'],
                'scpg_goal_consultant_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_consultant_period_goals');
    }
};
