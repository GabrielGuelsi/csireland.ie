<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsurancePolicy extends Model
{
    protected $fillable = [
        'student_id',
        'type',
        'source',
        'status',
        'price_cents',
        'cost_cents',
        'approved_by',
        'approved_at',
        'approval_notes',
        'form_payload',
        'matched_by',
    ];

    protected $casts = [
        'price_cents'   => 'int',
        'cost_cents'    => 'int',
        'approved_at'   => 'datetime',
        'form_payload'  => 'array',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopePaid(Builder $q): Builder
    {
        return $q->where('type', 'paid');
    }

    public function scopeBonificado(Builder $q): Builder
    {
        return $q->whereIn('type', ['gov_free', 'gov_50', 'other_bonificado']);
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', 'pending');
    }

    public function scopeInStudentProcess(Builder $q): Builder
    {
        return $q->where('status', 'in_student_process');
    }

    public function scopeIssued(Builder $q): Builder
    {
        return $q->where('status', 'issued');
    }

    public function scopeUnmatched(Builder $q): Builder
    {
        return $q->whereNull('student_id');
    }

    public function scopeInMonth(Builder $q, int $year, int $month): Builder
    {
        return $q->whereYear('created_at', $year)->whereMonth('created_at', $month);
    }

    // ── Accessors ──────────────────────────────────────────────────────────

    public function getPriceReaisAttribute(): ?float
    {
        return $this->price_cents !== null ? $this->price_cents / 100 : null;
    }

    public function getCostReaisAttribute(): ?float
    {
        return $this->cost_cents !== null ? $this->cost_cents / 100 : null;
    }

    // ── Status / type metadata ─────────────────────────────────────────────

    public static function statusLabels(string $locale = 'en'): array
    {
        return $locale === 'pt_BR' ? [
            'awaiting_payment'   => 'Aguardando Pagamento',
            'in_student_process' => 'Aluno em processo',
            'pending'            => 'Pendente',
            'issued'             => 'Emitido',
            'received'           => 'Recebido',
            'sent_to_cs'         => 'Enviado para o CS',
        ] : [
            'awaiting_payment'   => 'Awaiting Payment',
            'in_student_process' => 'Student in process',
            'pending'            => 'Pending',
            'issued'             => 'Issued',
            'received'           => 'Received',
            'sent_to_cs'         => 'Sent to CS',
        ];
    }

    public static function typeLabels(string $locale = 'en'): array
    {
        return $locale === 'pt_BR' ? [
            'paid'              => 'Pago',
            'gov_free'          => 'Governamental (gratuito)',
            'gov_50'            => 'Governamental (50%)',
            'other_bonificado'  => 'Outro (bonificado)',
        ] : [
            'paid'              => 'Paid',
            'gov_free'          => 'Government (free)',
            'gov_50'            => 'Government (50%)',
            'other_bonificado'  => 'Other (bonificado)',
        ];
    }

    public function statusLabel(string $locale = 'en'): string
    {
        return self::statusLabels($locale)[$this->status] ?? $this->status;
    }

    public function typeLabel(string $locale = 'en'): string
    {
        return self::typeLabels($locale)[$this->type] ?? $this->type;
    }

    public function isBonificado(): bool
    {
        return in_array($this->type, ['gov_free', 'gov_50', 'other_bonificado'], true);
    }

    // ── Status workflow ────────────────────────────────────────────────────

    /**
     * Allowed forward transitions from a given status.
     * Paid policies start at awaiting_payment; bonificado at pending.
     */
    public static function allowedTransitions(string $from): array
    {
        return match ($from) {
            'awaiting_payment'   => ['pending'],
            'in_student_process' => ['pending'],
            'pending'            => ['issued', 'in_student_process'],
            'issued'             => ['received'],
            'received'           => ['sent_to_cs'],
            'sent_to_cs'         => [],
            default              => [],
        };
    }

    public function canTransitionTo(string $to): bool
    {
        return in_array($to, self::allowedTransitions($this->status), true);
    }
}
