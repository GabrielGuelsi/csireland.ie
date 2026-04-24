<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InsurancePolicy;
use App\Models\Note;
use App\Models\Notification;
use App\Models\PendingReapplication;
use App\Models\Student;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\PhoneNormaliser;
use App\Services\ReapplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    public function handleForm(Request $request)
    {
        // 1. Validate bearer token (timing-safe comparison)
        $secret = config('services.webhook.secret');
        $header = $request->bearerToken();
        if (!$secret || !hash_equals($secret, $header ?? '')) {
            return response()->json(['error' => 'Unauthorised'], 401);
        }

        $payload = $request->all();

        // Normalise keys: trim whitespace (e.g. "  Date of Birth  " → "Date of Birth")
        $normalised = [];
        foreach ($payload as $key => $value) {
            $normalised[trim($key)] = $value;
        }

        \Log::info('Webhook payload keys: ' . implode(' | ', array_keys($normalised)));

        // Helper: extract first array value, trim, treat "" as null
        $get = function (string $key) use ($normalised): ?string {
            $val = $normalised[$key][0] ?? null;
            if ($val === null) return null;
            $val = trim((string) $val);
            return $val === '' ? null : $val;
        };

        // Helper: extract every value for a multi-select field, trimmed, non-empty
        $getAll = function (string $key) use ($normalised): array {
            $raw = $normalised[$key] ?? [];
            if (!is_array($raw)) {
                $raw = [$raw];
            }
            $out = [];
            foreach ($raw as $v) {
                $v = trim((string) $v);
                if ($v !== '') {
                    $out[] = $v;
                }
            }
            return $out;
        };

        // 2. Extract fields — using actual Google Form column names
        $studentEmail     = $get('Student email');
        $salesAdvisor     = $get('Sales Advisor');
        $studentName      = $get('Student full name');
        $productRaw       = $get('Product type');
        $course           = $get('Course');
        $university       = $get('University');
        $intakeRaw        = $get('Intake');
        $price            = $get('Sales price without scholarship');
        $priceScholarship = $get('Sales price with scholarship (If Applicable)');
        $pendingDocs      = $get('Pending documents and add informations');
        $reappRaw         = $get('If REAPPLICATION:');
        $phoneRaw         = $get('Student WhatsApp number')
                        ?? $get('WhatsApp number')
                        ?? $get('WhatsApp Number')
                        ?? $get('Student WhatsApp Number')
                        ?? $get('Phone number')
                        ?? $get('Phone Number')
                        ?? $get('Student phone number');
        $dobRaw           = $get('Date of Birth');
        $visaExpiryRaw    = $get('Visa expiry');
        $reducedEntryRaw  = $get('Entrada Reduzida');
        $specialCondRaw   = $getAll('Condição diferenciada');

        // 3. Map product_type
        $productType      = null;
        $productTypeOther = null;
        $productMap = [
            'Higher Education' => 'higher_education',
            'First Visa'       => 'first_visa',
            'Reapplication'    => 'reapplication',
            'Insurance'        => 'insurance',
            'Emergencial Tax'  => 'emergencial_tax',
            'Learn Protection' => 'learn_protection',
        ];
        if ($productRaw !== null) {
            if (isset($productMap[$productRaw])) {
                $productType = $productMap[$productRaw];
            } elseif (stripos($productRaw, 'Outro') === 0) {
                $productType      = 'other';
                $productTypeOther = trim(substr($productRaw, strlen('Outro:')));
            }
        }

        // 4. Map intake — handles Portuguese and English month names
        $intake = null;
        $intakeMap = [
            'january'          => 'jan',
            'january/february' => 'jan',
            'jan'              => 'jan',
            'february'         => 'feb',
            'fevereiro'        => 'feb',
            'feb'              => 'feb',
            'may'              => 'may',
            'maio'             => 'may',
            'june'             => 'jun',
            'jun'              => 'jun',
            'junho'            => 'jun',
            'september'        => 'sep',
            'setembro'         => 'sep',
            'sep'              => 'sep',
        ];
        if ($intakeRaw !== null) {
            if (stripos($intakeRaw, 'Outro') === 0) {
                $intake = trim(substr($intakeRaw, strlen('Outro:')));
            } else {
                $intake = $intakeMap[strtolower(trim($intakeRaw))] ?? $intakeRaw;
            }
        }

        // 5. Map reapplication_action
        $reappAction = null;
        if ($productType === 'reapplication' && $reappRaw !== null) {
            if (stripos($reappRaw, 'Keep') !== false) {
                $reappAction = 'keep_previous';
            } elseif (stripos($reappRaw, 'Cancel') !== false) {
                $reappAction = 'cancel_previous';
            }
        }

        // 6. Parse date of birth (handles dd/mm/yyyy or yyyy-mm-dd)
        $dateOfBirth = null;
        if ($dobRaw !== null) {
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dobRaw, $m)) {
                $dateOfBirth = "{$m[3]}-{$m[2]}-{$m[1]}";
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dobRaw)) {
                $dateOfBirth = $dobRaw;
            }
        }

        // 7. Parse visa expiry date (handles dd/mm/yyyy or yyyy-mm-dd)
        $visaExpiry = null;
        if ($visaExpiryRaw !== null) {
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $visaExpiryRaw, $m)) {
                $visaExpiry = "{$m[3]}-{$m[2]}-{$m[1]}";
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $visaExpiryRaw)) {
                $visaExpiry = $visaExpiryRaw;
            }
        }

        // 7b. Parse "Condição diferenciada" (multi-select, free-text on "Outro:")
        $specialOptions = [];
        $specialOther   = null;
        $condMap = [
            'Seguro Governamental Gratuito'     => 'gov_insurance_free',
            'Seguro Governamental 50% desconto' => 'gov_insurance_50',
            'Duolingo'                          => 'duolingo',
        ];
        foreach ($specialCondRaw as $v) {
            if (stripos($v, 'Outro') === 0) {
                $specialOptions[] = 'other';
                $specialOther = trim(substr($v, strlen('Outro:'))) ?: null;
            } elseif (isset($condMap[$v])) {
                $specialOptions[] = $condMap[$v];
            }
        }
        $specialOptions = array_values(array_unique($specialOptions));

        // 7c. Parse "Entrada Reduzida" (single choice: 0/150/250/350 or "Outro:")
        $reducedAmount = null;
        $reducedOther  = null;
        if ($reducedEntryRaw !== null) {
            if (stripos($reducedEntryRaw, 'Outro') === 0) {
                $reducedOther = trim(substr($reducedEntryRaw, strlen('Outro:'))) ?: null;
            } elseif (is_numeric($reducedEntryRaw)) {
                $reducedAmount = (float) $reducedEntryRaw;
            }
        }

        $hasSpecialCondition = !empty($specialOptions);
        $hasReducedEntry     = $reducedAmount !== null || $reducedOther !== null;

        // 8. Resolve sales consultant → CS agent
        $assignment = (new AssignmentService())->resolve($salesAdvisor ?? 'Unknown');

        // 9. Normalise phone
        $whatsappPhone = PhoneNormaliser::normalise($phoneRaw);

        // 10. Build the "new student" payload (used if no match is found OR for reapplications)
        $newStudentData = [
            'name'                    => $studentName ?? 'Unknown',
            'email'                   => $studentEmail ?? '',
            'whatsapp_phone'          => $whatsappPhone,
            'product_type'            => $productType ?? 'other',
            'product_type_other'      => $productTypeOther,
            'course'                  => $course,
            'university'              => $university,
            'intake'                  => $intake,
            'sales_price'             => $price ? (float) str_replace(['.', ','], ['', '.'], $price) : null,
            'sales_price_scholarship' => $priceScholarship ? (float) str_replace(['.', ','], ['', '.'], $priceScholarship) : null,
            'pending_documents'       => $pendingDocs,
            'reapplication_action'    => $reappAction,
            'date_of_birth'           => $dateOfBirth,
            'visa_expiry_date'        => $visaExpiry,
            'sales_consultant_id'     => $assignment['sales_consultant_id'],
            'assigned_cs_agent_id'    => $assignment['assigned_cs_agent_id'],
            'status'                  => 'waiting_initial_documents',
            'application_status'      => 'new_dispatch',
            'source'                  => 'form',
            'form_submitted_at'       => now(),
            'special_condition_options' => $hasSpecialCondition ? $specialOptions : null,
            'special_condition_other'   => $specialOther,
            'special_condition_status'  => $hasSpecialCondition ? 'pending' : null,
            'reduced_entry_amount'      => $reducedAmount,
            'reduced_entry_other'       => $reducedOther,
            'reduced_entry_status'      => $hasReducedEntry ? 'pending' : null,
        ];

        // 10a. Paid insurance forms no longer create a Student row — they become an InsurancePolicy
        //      attached to the existing student (matched by phone / email / name).
        if ($productType === 'insurance') {
            return $this->handleInsuranceForm($whatsappPhone, $studentEmail, $studentName, $price, $pendingDocs, $productRaw, $assignment, $payload);
        }

        // 10b. Reapplications take over the existing student's active fields instead of creating a
        //      duplicate row. If we can't find the student, the form goes to the pending-match queue.
        if ($productType === 'reapplication') {
            return $this->handleReapplicationForm(
                $whatsappPhone, $studentEmail, $studentName,
                $course, $university, $intake,
                $newStudentData['sales_price'],
                $productRaw, $payload
            );
        }

        // 11. Try to find an existing student (phone first, then name)
        $existing = null;
        if ($whatsappPhone) {
            $existing = Student::where('whatsapp_phone', $whatsappPhone)->first();
        }
        if (!$existing && $studentName) {
            $existing = Student::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($studentName))])->first();
        }

        // 12. No match → create as today (normal flow), with special handling for add-ons
        if (!$existing) {
            $addOnTypes = ['insurance', 'emergencial_tax', 'learn_protection'];
            $isAddOn = in_array($productType, $addOnTypes, true);

            // Add-on forms with no matching student: mark as already contacted
            // so they don't trigger first_contact_overdue SLA alerts
            if ($isAddOn) {
                $newStudentData['first_contacted_at'] = now();
            }

            $student = Student::create($newStudentData);

            if ($isAddOn) {
                $productLabel = $productRaw ?? ucfirst(str_replace('_', ' ', $productType));
                $this->appendSystemNote(
                    $student,
                    "Add-on product request ({$productLabel}) — no previous student found. Review manually and decide if a full CS journey applies."
                );
            }

            $this->notifyAgent(
                $assignment['assigned_cs_agent_id'],
                $student->id,
                $isAddOn ? 'additional_form_submission' : 'new_assignment'
            );
            $this->notifyApplicationsTeam($student->id);
            return response()->json(['ok' => true, 'action' => $isAddOn ? 'created_addon' : 'created'], 200);
        }

        // 13. Match found → route based on product type
        $productLabel  = $productRaw ?? ucfirst(str_replace('_', ' ', $productType ?? 'unknown'));
        $priceStr      = $price ?: '—';
        $pendingStr    = $pendingDocs ? " Details: {$pendingDocs}" : '';

        // 13a. Add-on products → note + notify, no new student
        $addOnTypes = ['insurance', 'emergencial_tax', 'learn_protection', 'other'];
        if (in_array($productType, $addOnTypes, true)) {
            $this->appendSystemNote(
                $existing,
                "New {$productLabel} request from this student. Price: {$priceStr}.{$pendingStr}"
            );
            $this->notifyAgent($existing->assigned_cs_agent_id, $existing->id, 'additional_form_submission');
            return response()->json(['ok' => true, 'action' => 'addon_note'], 200);
        }

        // 13c. Primary types duplicate (higher_education, first_visa) → note + notify, no new student
        $this->appendSystemNote(
            $existing,
            "Duplicate submission detected for {$productLabel}. Form submitted " . now()->format('d/m/Y H:i') . ". Review and reach out."
        );
        $this->notifyAgent($existing->assigned_cs_agent_id, $existing->id, 'additional_form_submission');
        return response()->json(['ok' => true, 'action' => 'duplicate_note'], 200);
    }

    /**
     * Paid insurance form → create an InsurancePolicy (attached to existing student
     * when we can match by phone, email, or name). No new Student row is created.
     */
    private function handleInsuranceForm(
        ?string $whatsappPhone,
        ?string $studentEmail,
        ?string $studentName,
        ?string $price,
        ?string $pendingDocs,
        ?string $productRaw,
        array $assignment,
        array $rawPayload,
    ) {
        $existing  = null;
        $matchedBy = null;

        if ($whatsappPhone) {
            $existing = Student::where('whatsapp_phone', $whatsappPhone)->first();
            if ($existing) $matchedBy = 'phone';
        }
        if (!$existing && $studentEmail) {
            $existing = Student::whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower(trim($studentEmail))])->first();
            if ($existing) $matchedBy = 'email';
        }
        if (!$existing && $studentName) {
            $existing = Student::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($studentName))])->first();
            if ($existing) $matchedBy = 'name';
        }

        $priceCents = $price
            ? (int) round(((float) str_replace(['.', ','], ['', '.'], $price)) * 100)
            : (int) config('insurance.default_price_cents');

        $policy = InsurancePolicy::create([
            'student_id'     => $existing?->id,
            'type'           => 'paid',
            'source'         => 'form',
            'status'         => 'awaiting_payment',
            'price_cents'    => $priceCents,
            'cost_cents'     => (int) config('insurance.default_cost_cents'),
            'matched_by'     => $matchedBy,
            'form_payload'   => $rawPayload,
            'approval_notes' => $pendingDocs,
        ]);

        if ($existing) {
            $this->appendSystemNote(
                $existing,
                "Paid insurance form received (" . ($productRaw ?? 'Insurance') . ") — policy #{$policy->id} created."
            );
            $this->notifyAgent($existing->assigned_cs_agent_id, $existing->id, 'additional_form_submission');
        }

        $this->notifyApplicationsTeamForPolicy($policy->id, (bool) $existing);

        return response()->json([
            'ok'        => true,
            'action'    => 'insurance_paid_' . ($existing ? 'matched' : 'unmatched'),
            'policy_id' => $policy->id,
        ], 200);
    }

    /**
     * Reapplication form → update existing Student in place via ReapplicationService.
     * No new Student row. If no match, queue a PendingReapplication for admin review.
     */
    private function handleReapplicationForm(
        ?string $whatsappPhone,
        ?string $studentEmail,
        ?string $studentName,
        ?string $course,
        ?string $university,
        ?string $intake,
        $salesPrice,
        ?string $productRaw,
        array $rawPayload,
    ) {
        $existing  = null;
        $matchedBy = null;

        if ($studentEmail) {
            $existing = Student::whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower(trim($studentEmail))])->first();
            if ($existing) $matchedBy = 'email';
        }
        if (!$existing && $whatsappPhone) {
            $existing = Student::where('whatsapp_phone', $whatsappPhone)->first();
            if ($existing) $matchedBy = 'phone';
        }
        if (!$existing && $studentName) {
            $existing = Student::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($studentName))])->first();
            if ($existing) $matchedBy = 'name';
        }

        if ($existing) {
            (new ReapplicationService())->transition(
                $existing,
                [
                    'course'      => $course,
                    'university'  => $university,
                    'intake'      => $intake,
                    'sales_price' => $salesPrice,
                ],
                $matchedBy,
                null  // webhook actor — no admin user
            );

            return response()->json([
                'ok'         => true,
                'action'     => 'reapplication_matched',
                'student_id' => $existing->id,
                'cycle'      => $existing->fresh()->reapplication_count,
                'matched_by' => $matchedBy,
            ], 200);
        }

        $pending = PendingReapplication::create([
            'name'           => $studentName,
            'email'          => $studentEmail,
            'whatsapp_phone' => $whatsappPhone,
            'product_raw'    => $productRaw,
            'form_payload'   => $rawPayload,
            'status'         => PendingReapplication::STATUS_PENDING,
        ]);

        foreach (User::where('role', 'application')->where('active', true)->pluck('id') as $uid) {
            Notification::create([
                'user_id' => $uid,
                'type'    => 'reapplication_pending_match',
                'data'    => ['pending_reapplication_id' => $pending->id],
            ]);
        }

        return response()->json([
            'ok'         => true,
            'action'     => 'reapplication_pending_match',
            'pending_id' => $pending->id,
        ], 200);
    }

    private function notifyApplicationsTeamForPolicy(int $policyId, bool $isMatched): void
    {
        $userIds = User::where('role', 'application')
            ->where('active', true)
            ->pluck('id');

        foreach ($userIds as $uid) {
            Notification::create([
                'user_id' => $uid,
                'type'    => 'application_dispatch',
                'data'    => ['insurance_policy_id' => $policyId, 'matched' => $isMatched],
            ]);
        }
    }

    private function appendSystemNote(Student $student, string $body): void
    {
        Note::create([
            'student_id' => $student->id,
            'author_id'  => null,
            'body'       => $body,
        ]);
    }

    private function notifyAgent(?int $agentId, int $studentId, string $type = 'new_assignment'): void
    {
        if (!$agentId) return;
        Notification::create([
            'user_id'    => $agentId,
            'type'       => $type,
            'student_id' => $studentId,
        ]);
    }

    private function notifyApplicationsTeam(int $studentId): void
    {
        $userIds = User::where('role', 'application')
            ->where('active', true)
            ->pluck('id');

        foreach ($userIds as $uid) {
            Notification::create([
                'user_id'    => $uid,
                'type'       => 'application_dispatch',
                'student_id' => $studentId,
            ]);
        }
    }
}
