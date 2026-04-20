<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    const PARTIAL_COUNTABLE_PRODUCTS = ['higher_education', 'first_visa'];

    protected $fillable = [
        'name', 'email', 'whatsapp_phone',
        'product_type', 'product_type_other', 'course', 'university', 'intake',
        'sales_price', 'sales_price_scholarship',
        'pending_documents', 'observations', 'reapplication_action',
        'sales_consultant_id', 'assigned_cs_agent_id',
        'status', 'priority', 'system',
        'exam_date', 'exam_result', 'payment_status', 'visa_status',
        'visa_type', 'visa_expiry_date', 'date_of_birth',
        'form_submitted_at', 'first_contacted_at', 'last_contacted_at', 'gift_received_at',
        'next_followup_date', 'next_followup_note',
        'source',
        'cancellation_reason', 'cancellation_justified',
        'application_status', 'application_notes',
        'college_application_date', 'college_response_date', 'offer_letter_received_at',
    ];

    protected $casts = [
        'form_submitted_at'   => 'datetime',
        'first_contacted_at'  => 'datetime',
        'last_contacted_at'   => 'datetime',
        'gift_received_at'    => 'datetime',
        'exam_date'           => 'date',
        'visa_expiry_date'    => 'date',
        'date_of_birth'       => 'date',
        'next_followup_date'  => 'date',
        'college_application_date' => 'date',
        'college_response_date'    => 'date',
        'offer_letter_received_at' => 'datetime',
        'sales_price'        => 'decimal:2',
        'sales_price_scholarship' => 'decimal:2',
    ];

    public function salesConsultant()
    {
        return $this->belongsTo(SalesConsultant::class);
    }

    public function assignedAgent()
    {
        return $this->belongsTo(User::class, 'assigned_cs_agent_id');
    }

    public function stageLogs()
    {
        return $this->hasMany(StudentStageLog::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class)->orderByDesc('created_at');
    }

    public function messageLogs()
    {
        return $this->hasMany(MessageLog::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function scheduledMessages()
    {
        return $this->hasMany(ScheduledStudentMessage::class);
    }

    public function chats()
    {
        return $this->hasMany(StudentChat::class)->orderBy('created_at');
    }

    public static function statusLabel(string $status): string
    {
        return match($status) {
            'waiting_initial_documents' => 'Waiting for Documents (Initial)',
            'first_contact_made'        => 'First Contact Made',
            'waiting_offer_letter'      => 'Waiting for Offer Letter',
            'waiting_english_exam'      => 'Waiting for English Exam (College)',
            'waiting_duolingo'          => 'Waiting for Duolingo',
            'waiting_reapplication'     => 'Waiting for Reapplication',
            'waiting_college_documents' => 'Waiting for Documents (College)',
            'waiting_college_response'  => 'Waiting for College Response',
            'waiting_final_letter'      => 'Waiting for Final Letter',
            'waiting_payment'           => 'Waiting for Payment',
            'waiting_student_response'  => 'Waiting for Student Response',
            'cancelled'                 => 'Cancelled',
            'concluded'                 => 'Concluded',
            default                     => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public static function visaTypeLabel(string $type): string
    {
        return match($type) {
            'eu_passport' => 'EU Passport',
            'stamp_2'     => 'Stamp 2',
            'stamp_1_4'   => 'Stamp 1/4',
            default       => $type,
        };
    }

    public static function allStatuses(): array
    {
        return [
            'waiting_initial_documents',
            'first_contact_made',
            'waiting_offer_letter',
            'waiting_english_exam',
            'waiting_duolingo',
            'waiting_reapplication',
            'waiting_college_documents',
            'waiting_college_response',
            'waiting_final_letter',
            'waiting_payment',
            'waiting_student_response',
            'cancelled',
            'concluded',
        ];
    }

    public static function allApplicationStatuses(): array
    {
        return [
            'new_dispatch',
            'in_review',
            'waiting_cs',
            'applied',
            'waiting_college',
            'offer_received',
            'cancelled',
        ];
    }

    public static function applicationStatusLabel(?string $status): string
    {
        return match($status) {
            'new_dispatch'    => 'New Dispatch',
            'in_review'       => 'In Review',
            'waiting_cs'      => 'Waiting CS',
            'applied'         => 'Applied to College',
            'waiting_college' => 'Waiting College Response',
            'offer_received'  => 'Offer Received',
            'cancelled'       => 'Cancelled',
            null, ''          => '—',
            default           => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
