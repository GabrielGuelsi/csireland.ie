<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();            // default_price_cents | default_cost_cents
            $table->unsignedInteger('value_cents');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('insurance_settings')->insert([
            ['key' => 'default_price_cents', 'value_cents' => 22000, 'updated_by' => null, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'default_cost_cents',  'value_cents' =>  7000, 'updated_by' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_settings');
    }
};
