<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('insurance_policies')
            ->whereIn('type', ['gov_free', 'gov_50', 'other_bonificado'])
            ->where('status', 'pending')
            ->update([
                'status'     => 'in_student_process',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('insurance_policies')
            ->whereIn('type', ['gov_free', 'gov_50', 'other_bonificado'])
            ->where('status', 'in_student_process')
            ->update([
                'status'     => 'pending',
                'updated_at' => now(),
            ]);
    }
};
