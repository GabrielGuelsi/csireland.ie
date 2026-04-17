<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_period_goals', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('period_year');
            $table->tinyInteger('period_month');
            $table->decimal('team_minima', 12, 2);
            $table->decimal('team_target', 12, 2);
            $table->decimal('team_wow', 12, 2);
            $table->timestamps();

            $table->unique(['period_year', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_period_goals');
    }
};
