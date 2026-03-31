<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Services\AssignmentService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportStudentsCsv extends Command
{
    protected $signature   = 'students:import-csv {file} {--limit=200}';
    protected $description = 'Import students from a Google Form CSV export (last N rows)';

    private array $productTypeMap = [
        'higher education'  => 'higher_education',
        'first visa'        => 'first_visa',
        'reapplication'     => 'reapplication',
        'insurance'         => 'insurance',
        'emergencial tax'   => 'emergencial_tax',
        'learn protection'  => 'learn_protection',
    ];

    private array $intakeMap = [
        'january/february' => 'jan',
        'january'          => 'jan',
        'february'         => 'feb',
        'maio'             => 'may',
        'may'              => 'may',
        'june'             => 'jun',
        'junho'            => 'jun',
        'september'        => 'sep',
        'setembro'         => 'sep',
    ];

    public function handle(AssignmentService $assignment): int
    {
        $file  = $this->argument('file');
        $limit = (int) $this->option('limit');

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $handle = fopen($file, 'r');
        $header = fgetcsv($handle);

        // Index columns by name
        $cols = array_flip(array_map('trim', $header));

        // Read all rows then take last N
        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        $rows   = array_slice($rows, -$limit);
        $total  = count($rows);
        $this->info("Importing {$total} students...");

        $bar     = $this->output->createProgressBar($total);
        $imported = 0;
        $skipped  = 0;

        foreach ($rows as $row) {
            $get = fn(string $col) => isset($cols[$col]) ? trim($row[$cols[$col]] ?? '') : '';

            $name = $get('Student full name');
            if (empty($name)) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $productRaw = strtolower($get('Product type'));
            $productType = $this->productTypeMap[$productRaw] ?? 'other';
            $productOther = ! isset($this->productTypeMap[$productRaw]) && $productRaw !== ''
                ? $get('Product type')
                : null;

            $intakeRaw = strtolower($get('Intake'));
            $intake = $this->intakeMap[$intakeRaw] ?? null;

            $advisorName = $get('Sales Advisor');
            $assignment_data = $advisorName
                ? $assignment->resolve($advisorName)
                : ['sales_consultant_id' => null, 'assigned_cs_agent_id' => null];

            $salesPrice = $this->parsePrice($get('Sales price without scholarship'));
            $salesPriceScholarship = $this->parsePrice($get('Sales price with scholarship (If Applicable)'));

            $dob        = $this->parseDate($get('  Date of Birth  ') ?: $get('Date of Birth'));
            $visaExpiry = $this->parseDate($get('Visa expiry'));
            $submittedAt = $this->parseDateTime($get('Carimbo de data/hora'));

            $reapplication = $get('If REAPPLICATION:') ?: null;
            if ($reapplication) {
                $reapplication = str_contains(strtolower($reapplication), 'keep') ? 'keep_previous' : 'cancel_previous';
            }

            Student::create([
                'name'                    => $name,
                'email'                   => $get('Student email') ?: 'unknown_' . uniqid() . '@import.local',
                'whatsapp_phone'          => null,
                'product_type'            => $productType,
                'product_type_other'      => $productOther,
                'course'                  => $get('Course') ?: null,
                'university'              => $get('University') ?: null,
                'intake'                  => $intake,
                'sales_price'             => $salesPrice,
                'sales_price_scholarship' => $salesPriceScholarship,
                'pending_documents'       => $get('Pending documents and add informations') ?: null,
                'reapplication_action'    => $reapplication,
                'sales_consultant_id'     => $assignment_data['sales_consultant_id'],
                'assigned_cs_agent_id'    => $assignment_data['assigned_cs_agent_id'],
                'status'                  => 'waiting_initial_documents',
                'date_of_birth'           => $dob,
                'visa_expiry_date'        => $visaExpiry,
                'form_submitted_at'       => $submittedAt,
            ]);

            $imported++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. Imported: {$imported}, Skipped (no name): {$skipped}");

        return 0;
    }

    private function parsePrice(string $value): ?float
    {
        $value = trim($value);
        if ($value === '') return null;
        // Handle Brazilian format: "5.000,00" → 5000.00
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        $numeric = preg_replace('/[^0-9.]/', '', $value);
        return $numeric !== '' ? (float) $numeric : null;
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') return null;
        try {
            return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    private function parseDateTime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') return null;
        try {
            return Carbon::createFromFormat('d/m/Y H:i:s', $value)->format('Y-m-d H:i:s');
        } catch (\Exception) {
            return null;
        }
    }
}
