<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Student;
use App\Services\AssignmentService;
use App\Services\PhoneNormaliser;
use Illuminate\Http\Request;

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

        // Helper: extract first array value, trim, treat "" as null
        $get = function (string $key) use ($normalised): ?string {
            $val = $normalised[$key][0] ?? null;
            if ($val === null) return null;
            $val = trim((string) $val);
            return $val === '' ? null : $val;
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
        $phoneRaw         = $get('Student WhatsApp number');
        $dobRaw           = $get('Date of Birth');
        $visaExpiryRaw    = $get('Visa expiry');

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

        // 8. Resolve sales consultant → CS agent
        $assignment = (new AssignmentService())->resolve($salesAdvisor ?? 'Unknown');

        // 9. Normalise phone
        $whatsappPhone = PhoneNormaliser::normalise($phoneRaw);

        // 10. Create Student
        $student = Student::create([
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
            'form_submitted_at'       => now(),
        ]);

        // 11. Notify assigned agent
        if ($assignment['assigned_cs_agent_id']) {
            Notification::create([
                'user_id'    => $assignment['assigned_cs_agent_id'],
                'type'       => 'new_assignment',
                'student_id' => $student->id,
            ]);
        }

        return response()->json(['ok' => true], 200);
    }
}
