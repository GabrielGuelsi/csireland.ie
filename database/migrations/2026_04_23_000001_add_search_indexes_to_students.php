<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $t) {
            $t->index('name');
            $t->index('email');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $t) {
            $t->dropIndex(['name']);
            $t->dropIndex(['email']);
        });
    }
};
