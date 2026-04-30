<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'completed_course', 'completed_university', 'completed_intake', 'completed_price', 'completed_at',
        'application_cancellation_reason', 'application_cancellation_stage', 'application_cancelled_at',
        'special_condition_options', 'special_condition_other', 'special_condition_status',
        'special_condition_reviewed_by', 'special_condition_reviewed_at', 'special_condition_review_notes',
        'reduced_entry_amount', 'reduced_entry_other', 'reduced_entry_status',
        'reduced_entry_reviewed_by', 'reduced_entry_reviewed_at', 'reduced_entry_review_notes',
        'original_product_type', 'original_course', 'original_university', 'original_intake', 'original_sales_price',
        'reapplication_count', 'last_reapplied_at',
        // Sales pipeline (prototype)
        'sales_stage', 'assigned_sales_agent_id',
        'primeiro_nome', 'sobrenome', 'nome_social',
        'temperature', 'lead_quality',
        'objection_reason', 'meeting_date',
        'handed_off_at', 'handed_off_by',
        'is_reapplication', 'current_journey_cycle',
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
        'completed_at'             => 'datetime',
        'application_cancelled_at' => 'datetime',
        'completed_price'          => 'decimal:2',
        'sales_price'        => 'decimal:2',
        'sales_price_scholarship' => 'decimal:2',
        'special_condition_options'     => 'array',
        'special_condition_reviewed_at' => 'datetime',
        'reduced_entry_amount'          => 'decimal:2',
        'reduced_entry_reviewed_at'     => 'datetime',
        'original_sales_price'          => 'decimal:2',
        'reapplication_count'           => 'int',
        'last_reapplied_at'             => 'datetime',
        // Sales pipeline (prototype)
        'meeting_date'                  => 'datetime',
        'handed_off_at'                 => 'datetime',
        'is_reapplication'              => 'boolean',
        'lead_quality'                  => 'int',
        'current_journey_cycle'         => 'int',
    ];

    /**
     * Pipeline isolation guard. Sales-leads (rows where sales_stage IS NOT NULL)
     * are invisible to every Eloquent query against Student by default. Sales code
     * opts in via the salesLeadsOnly() local scope.
     *
     * Why a global scope: ~25+ existing queries across CS / Admin / Apps / Jobs /
     * Webhook / Extension would otherwise leak sales-leads. Centralising the guard
     * here means the prototype is fail-safe by default — no per-query discipline,
     * no opt-outs needed in any existing file.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('exclude_sales_leads', function (Builder $q) {
            $q->whereNull('sales_stage');
        });
    }

    /**
     * Sales code opts in: only rows that ARE sales-leads.
     */
    public function scopeSalesLeadsOnly(Builder $q): Builder
    {
        return $q->withoutGlobalScope('exclude_sales_leads')
                 ->whereNotNull('sales_stage');
    }

    public function hasReapplied(): bool
    {
        return (int) $this->reapplication_count > 0;
    }

    public function isHandedOff(): bool
    {
        return $this->handed_off_at !== null;
    }

    public function scopeReapplied($q)
    {
        return $q->where('reapplication_count', '>', 0);
    }

    public function salesConsultant()
    {
        return $this->belongsTo(SalesConsultant::class);
    }

    public function assignedAgent()
    {
        return $this->belongsTo(User::class, 'assigned_cs_agent_id');
    }

    public function assignedSalesAgent()
    {
        return $this->belongsTo(User::class, 'assigned_sales_agent_id');
    }

    public function handedOffBy()
    {
        return $this->belongsTo(User::class, 'handed_off_by');
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

    public function insurancePolicies()
    {
        return $this->hasMany(InsurancePolicy::class);
    }

    public function chats()
    {
        return $this->hasMany(StudentChat::class)->orderBy('created_at');
    }

    public static function statusLabel(?string $status): string
    {
        if ($status === null) return '—';
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

    public static function allSalesStages(): array
    {
        return [
            'cadastro',
            'primeiro_contato',
            'qualificado',
            'apresentacao',
            'acompanhamento',
            'negociacao',
            'fechamento',
        ];
    }

    public static function salesStageLabel(string $stage): string
    {
        return match($stage) {
            'cadastro'         => 'Cadastro',
            'primeiro_contato' => 'Primeiro Contato',
            'qualificado'      => 'Qualificado',
            'apresentacao'     => 'Apresentação',
            'acompanhamento'   => 'Acompanhamento',
            'negociacao'       => 'Negociação',
            'fechamento'       => 'Fechamento',
            default            => ucfirst(str_replace('_', ' ', $stage)),
        };
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
            'enrolled',
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
            'enrolled'        => 'Enrolled',
            'cancelled'       => 'Cancelled',
            null, ''          => '—',
            default           => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public static function applicationCancellationReasons(): array
    {
        return [
            'did_not_pay'           => "Didn't pay",
            'went_elsewhere'        => 'Went elsewhere',
            'visa_refused'          => 'Visa refused',
            'withdrew'              => 'Withdrew',
            'documents_incomplete'  => 'Documents incomplete',
            'college_rejected'      => 'College rejected',
            'other'                 => 'Other',
        ];
    }

    public static function applicationCancellationReasonLabel(?string $code): string
    {
        if ($code === null || $code === '') return '—';
        return self::applicationCancellationReasons()[$code] ?? ucfirst(str_replace('_', ' ', $code));
    }

    public static function specialConditionOptions(): array
    {
        return [
            'gov_insurance_free' => 'Seguro Governamental Gratuito',
            'gov_insurance_50'   => 'Seguro Governamental 50% desconto',
            'duolingo'           => 'Duolingo',
            'other'              => 'Outro',
        ];
    }

    public static function specialConditionOptionLabel(string $code): string
    {
        return self::specialConditionOptions()[$code] ?? ucfirst(str_replace('_', ' ', $code));
    }

    public function specialConditionReviewer()
    {
        return $this->belongsTo(User::class, 'special_condition_reviewed_by');
    }

    public function reducedEntryReviewer()
    {
        return $this->belongsTo(User::class, 'reduced_entry_reviewed_by');
    }

    public function hasAnySpecialApprovals(): bool
    {
        return !empty($this->special_condition_options)
            || $this->special_condition_status !== null
            || $this->reduced_entry_amount !== null
            || $this->reduced_entry_other !== null
            || $this->reduced_entry_status !== null;
    }
}
