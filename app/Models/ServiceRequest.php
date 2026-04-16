<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    protected $fillable = [
        'type',
        'status',
        'student_id',
        'requested_by',
        'data',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    // ── Types & statuses ─────────────────────────────────────────────────────

    public const TYPES = ['documentation', 'refund', 'cancellation'];

    public const STATUSES = [
        'documentation' => ['pending', 'scheduled', 'completed'],
        'refund'        => ['pending', 'in_review', 'approved', 'completed', 'rejected'],
        'cancellation'  => ['pending', 'in_review', 'completed'],
    ];

    public const TYPE_LABELS = [
        'documentation' => 'Document Request',
        'refund'        => 'Refund',
        'cancellation'  => 'Cancellation',
    ];

    public const STATUS_LABELS = [
        'pending'   => 'Pending',
        'scheduled' => 'Scheduled',
        'in_review' => 'In Review',
        'approved'  => 'Approved',
        'completed' => 'Completed',
        'rejected'  => 'Rejected',
    ];

    public function validStatuses(): array
    {
        return self::STATUSES[$this->type] ?? [];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
