<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SalesPeriodGoal extends Model
{
    protected $fillable = [
        'period_year', 'period_month',
        'team_minima', 'team_target', 'team_wow',
    ];

    protected $casts = [
        'period_year'  => 'integer',
        'period_month' => 'integer',
        'team_minima'  => 'decimal:2',
        'team_target'  => 'decimal:2',
        'team_wow'     => 'decimal:2',
    ];

    public function consultantGoals()
    {
        return $this->hasMany(SalesConsultantPeriodGoal::class);
    }

    public function partials()
    {
        return $this->hasMany(SalesPartial::class);
    }

    public function periodStart(): Carbon
    {
        return Carbon::create($this->period_year, $this->period_month, 1)->startOfMonth();
    }

    public function periodEnd(): Carbon
    {
        return $this->periodStart()->endOfMonth();
    }

    public function periodLabel(): string
    {
        return $this->periodStart()->format('F Y');
    }
}
