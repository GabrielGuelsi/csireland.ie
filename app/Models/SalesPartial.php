<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesPartial extends Model
{
    protected $fillable = [
        'sales_period_goal_id',
        'partial_date',
        'is_closing',
        'highlights',
        'created_by',
    ];

    protected $casts = [
        'partial_date' => 'date',
        'is_closing'   => 'boolean',
    ];

    public function periodGoal()
    {
        return $this->belongsTo(SalesPeriodGoal::class, 'sales_period_goal_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function highlightBullets(): array
    {
        if (!$this->highlights) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $this->highlights))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}
