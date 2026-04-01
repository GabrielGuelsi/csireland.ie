<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Note;
use App\Models\MessageLog;
use App\Models\ScheduledStudentMessage;
use App\Models\Student;
use App\Models\StudentStageLog;
use App\Services\AssignmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportReportCsv extends Command
{
    protected $signature   = 'students:import-report {file}';
    protected $description = 'Wipe students and re-import from internal report CSV (skips paid/concluded/cancelled)';

    // ETAPA keywords → concluded or cancelled → skip
    private array $skipEtapa = ['conclu', 'cancel', 'duplicado', 'finalizado'];

    // STATUS keywords → paid (= concluded) or cancelled → skip
    private array $skipStatus = ['pago', 'quitado', 'entrada quitada', 'matricula cancelada', 'cancelado', 'cancelada'];

    private array $productTypeMap = [
        'graduação'    => 'higher_education',
        'graduacao'    => 'higher_education',
        'novo curso'   => 'higher_education',
        'renovação'    => 'reapplication',
        'renovacao'    => 'reapplication',
        'renovaçao'    => 'reapplication',
        'reapplicação' => 'reapplication',
        'reapplicacao' => 'reapplication',
        'reapplication'=> 'reapplication',
        'first visa'   => 'first_visa',
        'seguro'       => 'insurance',
        'insurance'    => 'insurance',
        'pathway'      => 'higher_education',
    ];

    public function handle(AssignmentService $assignment): int
    {
        $file = $this->argument('file');

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        if (! $this->confirm('This will DELETE all existing students and related records. Continue?')) {
            return 0;
        }

        // ── Wipe related tables in FK order ──────────────────────────────────
        $this->info('Wiping existing data...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        ScheduledStudentMessage::truncate();
        StudentStageLog::truncate();
        Note::truncate();
        MessageLog::truncate();
        Notification::truncate();
        Student::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ── Parse CSV ────────────────────────────────────────────────────────
        $handle = fopen($file, 'r');

        // Row 1 is a date/title row — skip it
        fgetcsv($handle, 0, ';');

        // Row 2 is the real header
        $header = fgetcsv($handle, 0, ';');
        $cols   = array_flip(array_map('trim', $header));

        $imported = 0;
        $skipped  = 0;

        $this->info('Importing active students...');

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $get = fn(string $col) => isset($cols[$col])
                ? trim($row[$cols[$col]] ?? '')
                : '';

            $name = $get('STUDENT');
            if (empty($name) || strtolower($name) === 'student') {
                $skipped++;
                continue;
            }

            $status = strtolower($get('STATUS'));
            $etapa  = strtolower($get('ETAPA DO PROCESSO'));

            // Skip: paid (= concluded) + any cancelled status
            foreach ($this->skipStatus as $keyword) {
                if (str_contains($status, $keyword)) {
                    $skipped++;
                    continue 2;
                }
            }

            // Skip: concluded, cancelled, duplicated etapa
            foreach ($this->skipEtapa as $keyword) {
                if (str_contains($etapa, $keyword)) {
                    $skipped++;
                    continue 2;
                }
            }

            $email = $get('E-MAIL') ?: 'unknown_' . uniqid() . '@import.local';

            $consultantName = $get('CONSULTOR') ?: 'Unknown';
            $assignmentData = $assignment->resolve($consultantName);

            $productRaw  = strtolower($get('TIPO DE CURSO'));
            $productType = null;
            foreach ($this->productTypeMap as $key => $val) {
                if (str_contains($productRaw, $key)) {
                    $productType = $val;
                    break;
                }
            }
            $productType ??= 'other';

            $price = $this->parsePrice($get('VALOR DO CURSO'));

            Student::create([
                'name'                    => $name,
                'email'                   => $email,
                'whatsapp_phone'          => null,
                'product_type'            => $productType,
                'product_type_other'      => null,
                'course'                  => $get('CURSO') ?: null,
                'university'              => $get('ESCOLA/UNIVERSIDADE') ?: null,
                'intake'                  => null,
                'sales_price'             => $price,
                'sales_price_scholarship' => null,
                'pending_documents'       => null,
                'reapplication_action'    => null,
                'sales_consultant_id'     => $assignmentData['sales_consultant_id'],
                'assigned_cs_agent_id'    => $assignmentData['assigned_cs_agent_id'],
                'status'                  => 'waiting_initial_documents',
                'date_of_birth'           => null,
                'visa_expiry_date'        => null,
                'form_submitted_at'       => now(),
            ]);

            $imported++;
        }

        fclose($handle);

        $this->info("Done. Imported: {$imported} | Skipped (paid/concluded/cancelled/empty): {$skipped}");

        return 0;
    }

    private function parsePrice(string $value): ?float
    {
        $value = trim($value);
        if ($value === '' || $value === '#DIV/0!' || $value === '#REF!') return null;
        $value = preg_replace('/[€$£\s]/', '', $value);
        // European format: "1.735,00" → 1735.00
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        $numeric = preg_replace('/[^0-9.]/', '', $value);
        return $numeric !== '' ? (float) $numeric : null;
    }
}
