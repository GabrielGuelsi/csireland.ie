<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageSequence extends Model
{
    protected $fillable = [
        'name',
        'days_after_first_contact',
        'template_id',
        'active',
        'created_by',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function template()
    {
        return $this->belongsTo(MessageTemplate::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
