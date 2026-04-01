<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrioritySlaSettings extends Model
{
    public $timestamps = false;

    protected $table = 'priority_sla_settings';

    protected $fillable = ['priority', 'working_days', 'updated_by', 'updated_at'];

    protected $casts = ['updated_at' => 'datetime'];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function getLimit(string $priority): ?int
    {
        $setting = static::where('priority', $priority)->first();
        return $setting?->working_days;
    }
}
