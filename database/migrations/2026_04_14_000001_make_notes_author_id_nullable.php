<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE notes MODIFY COLUMN author_id BIGINT UNSIGNED NULL');
        } else {
            // SQLite: recreate the column via Laravel's change()
            Schema::table('notes', function ($table) {
                $table->unsignedBigInteger('author_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE notes MODIFY COLUMN author_id BIGINT UNSIGNED NOT NULL');
        } else {
            Schema::table('notes', function ($table) {
                $table->unsignedBigInteger('author_id')->nullable(false)->change();
            });
        }
    }
};
