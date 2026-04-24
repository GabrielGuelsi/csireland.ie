<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingReapplication extends Model
{
    const STATUS_PENDING  = 'pending';
    const STATUS_MATCHED  = 'matched';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'name',
        'email',
        'whatsapp_phone',
        'product_raw',
        'form_payload',
        'status',
        'matched_student_id',
        'matched_by',
        'matched_at',
        'admin_notes',
    ];

    protected $casts = [
        'form_payload' => 'array',
        'matched_at'   => 'datetime',
    ];

    public function matchedStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'matched_student_id');
    }

    public function matcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_by');
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_PENDING);
    }
}
