<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'cs_agent'])->default('cs_agent')->after('email');
            $table->string('whatsapp_phone', 30)->nullable()->after('role');
            $table->boolean('active')->default(true)->after('whatsapp_phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'whatsapp_phone', 'active']);
        });
    }
};
