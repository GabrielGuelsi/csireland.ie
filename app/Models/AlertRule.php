<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertRule extends Model
{
    protected $fillable = [
        'name',
        'condition_type',
        'condition_value',
        'priority_filter',
        'status_filter',
        'notification_message',
        'auto_escalate_to_high',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'status_filter'        => 'array',
            'auto_escalate_to_high'=> 'boolean',
            'active'               => 'boolean',
            'condition_value'      => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public static function conditionTypeOptions(): array
    {
        return [
            'no_contact_days'      => 'No contact for X days',
            'sla_overdue'          => 'SLA overdue (any status)',
            'exam_approaching_days'=> 'Exam approaching in X days',
        ];
    }

    public function conditionLabel(): string
    {
        return match ($this->condition_type) {
            'no_contact_days'       => "No contact for {$this->condition_value} days",
            'sla_overdue'           => 'SLA overdue',
            'exam_approaching_days' => "Exam in {$this->condition_value} days",
            default                 => $this->condition_type,
        };
    }

    /** Replace {name} and {status} placeholders in the message template */
    public function buildMessage(Student $student): string
    {
        return str_replace(
            ['{name}', '{status}'],
            [$student->name, Student::statusLabel($student->status)],
            $this->notification_message
        );
    }

    /** Notification type to use when this rule fires */
    public function notificationType(): string
    {
        return match ($this->condition_type) {
            'no_contact_days'       => 'no_contact_overdue',
            'sla_overdue'           => 'sla_breach',
            'exam_approaching_days' => 'exam_approaching',
            default                 => 'sla_breach',
        };
    }
}
