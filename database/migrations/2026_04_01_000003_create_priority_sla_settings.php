<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('priority_sla_settings', function (Blueprint $table) {
            $table->id();
            $table->string('priority', 20)->unique(); // high, medium, low
            $table->unsignedInteger('working_days');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('updated_at')->nullable();
        });

        DB::table('priority_sla_settings')->insert([
            ['priority' => 'high',   'working_days' => 2, 'updated_by' => null, 'updated_at' => null],
            ['priority' => 'medium', 'working_days' => 4, 'updated_by' => null, 'updated_at' => null],
            ['priority' => 'low',    'working_days' => 7, 'updated_by' => null, 'updated_at' => null],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('priority_sla_settings');
    }
};
