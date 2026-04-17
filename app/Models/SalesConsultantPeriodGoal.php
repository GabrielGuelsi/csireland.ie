<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesConsultantPeriodGoal extends Model
{
    protected $fillable = [
        'sales_period_goal_id',
        'sales_consultant_id',
        'individual_minima',
        'individual_target',
        'individual_wow',
    ];

    protected $casts = [
        'individual_minima' => 'decimal:2',
        'individual_target' => 'decimal:2',
        'individual_wow'    => 'decimal:2',
    ];

    public function periodGoal()
    {
        return $this->belongsTo(SalesPeriodGoal::class, 'sales_period_goal_id');
    }

    public function consultant()
    {
        return $this->belongsTo(SalesConsultant::class, 'sales_consultant_id');
    }
}
