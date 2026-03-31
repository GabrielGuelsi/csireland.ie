<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlaSetting extends Model
{
    public $timestamps = false;

    protected $fillable = ['stage', 'days_limit', 'updated_by', 'updated_at'];

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
