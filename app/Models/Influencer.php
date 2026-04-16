<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Influencer extends Model
{
    protected $fillable = ['name', 'ref_code', 'started_at'];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
        ];
    }
}
