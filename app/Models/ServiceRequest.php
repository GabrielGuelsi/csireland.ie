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

    public const TYPES = ['documentation', 'refund', 'cancellation', 'removal', 'insurance'];

    public const STATUSES = [
        'documentation' => ['pending', 'scheduled', 'completed'],
        'refund'        => ['pending', 'in_review', 'approved', 'completed', 'rejected'],
        'cancellation'  => ['pending', 'in_review', 'completed'],
        'removal'       => ['pending', 'in_review', 'completed', 'rejected'],
        'insurance'     => ['pending', 'completed'],
    ];

    // Statuses considered "open" (still need work). Lists with an entry here
    // default to showing only open requests; types absent show all statuses.
    public const OPEN_STATUSES = [
        'documentation' => ['pending', 'scheduled'],
        'refund'        => ['pending', 'in_review', 'approved'],
        'cancellation'  => ['pending', 'in_review'],
    ];

    public const TYPE_LABELS = [
        'documentation' => 'Document Request',
        'refund'        => 'Refund',
        'cancellation'  => 'Cancellation',
        'removal'       => 'Removal Request',
        'insurance'     => 'Insurance Request',
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

    // ── Chat message helpers ────────────────────────────────────────────────

    public static function buildSubmissionMessage(string $type, array $data, string $agentName): string
    {
        $label = self::TYPE_LABELS[$type] ?? $type;

        return match ($type) {
            'documentation' => "[{$label}] submitted by {$agentName}. University: {$data['university']}. Emergency fee: " . (($data['emergency_fee_paid'] ?? false) ? 'Yes' : 'No') . ".",
            'refund'        => "[{$label}] submitted by {$agentName}. Reason: {$data['reason']}. Amount: €" . number_format($data['refund_amount'] ?? 0, 2) . ".",
            'cancellation'  => "[{$label}] submitted by {$agentName}. Reason: {$data['reason']}.",
            'removal'       => "[{$label}] submitted by {$agentName}. Reason: " . ($data['reason_code'] ?? 'unknown') . ".",
            'insurance'     => "[{$label}] submitted by {$agentName}.",
            default         => "[{$label}] submitted by {$agentName}.",
        };
    }

    public static function buildCompletionMessage(string $type): string
    {
        $label = self::TYPE_LABELS[$type] ?? $type;
        return "[{$label}] marked as completed.";
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

    public function attachments()
    {
        return $this->hasMany(ServiceRequestAttachment::class);
    }
}
