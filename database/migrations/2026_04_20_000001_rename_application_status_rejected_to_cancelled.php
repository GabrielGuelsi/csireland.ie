<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('students')
            ->where('application_status', 'rejected')
            ->update(['application_status' => 'cancelled']);
    }

    public function down(): void
    {
        DB::table('students')
            ->where('application_status', 'cancelled')
            ->update(['application_status' => 'rejected']);
    }
};
