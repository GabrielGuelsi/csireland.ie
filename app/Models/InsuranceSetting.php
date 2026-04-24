<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceSetting extends Model
{
    protected $fillable = ['key', 'value_cents', 'updated_by'];

    protected $casts = [
        'value_cents' => 'int',
    ];

    /** Request-scope cache to avoid repeated queries for the same key. */
    protected static array $cache = [];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function get(string $key, int $fallback = 0): int
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }
        $row = self::where('key', $key)->first();
        return self::$cache[$key] = $row?->value_cents ?? $fallback;
    }

    public static function set(string $key, int $valueCents, ?int $userId = null): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value_cents' => $valueCents, 'updated_by' => $userId]
        );
        self::$cache[$key] = $valueCents;
    }
}
