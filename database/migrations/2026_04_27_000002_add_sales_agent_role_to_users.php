<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite stores the CHECK constraint inside sqlite_master.sql as text.
            // We rewrite the role's IN-list in place so the new values are allowed.
            // The 2026_04_15 migration only handled MySQL, so the SQLite schema may
            // still be ('admin', 'cs_agent'). We normalise to all four roles.
            DB::statement("PRAGMA writable_schema = ON");
            $row = DB::selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'");
            if ($row && !str_contains($row->sql, "'sales_agent'")) {
                $newSql = preg_replace(
                    '/check\s*\(\s*"role"\s+in\s*\([^\)]*\)\s*\)/i',
                    "check (\"role\" in ('admin', 'cs_agent', 'application', 'sales_agent'))",
                    $row->sql,
                    1
                );
                if ($newSql && $newSql !== $row->sql) {
                    DB::statement("UPDATE sqlite_master SET sql = ? WHERE type='table' AND name='users'", [$newSql]);
                }
            }
            DB::statement("PRAGMA writable_schema = OFF");
            DB::statement("PRAGMA integrity_check");
        } else {
            DB::statement("ALTER TABLE users MODIFY COLUMN role
                ENUM('admin','cs_agent','application','sales_agent') NOT NULL DEFAULT 'cs_agent'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role
                ENUM('admin','cs_agent','application') NOT NULL DEFAULT 'cs_agent'");
        }
    }
};
