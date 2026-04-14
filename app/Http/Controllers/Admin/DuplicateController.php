<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DuplicateController extends Controller
{
    public function index()
    {
        $groups = $this->findDuplicateGroups();
        return view('admin.duplicates.index', compact('groups'));
    }

    public function merge(Request $request)
    {
        $request->validate([
            'keep_id'     => 'required|integer|exists:students,id',
            'merge_ids'   => 'required|array|min:1',
            'merge_ids.*' => 'integer|exists:students,id|different:keep_id',
        ]);

        $keep   = Student::findOrFail($request->keep_id);
        $merged = 0;

        DB::transaction(function () use ($request, $keep, &$merged) {
            foreach ($request->merge_ids as $dupId) {
                if ((int) $dupId === (int) $keep->id) {
                    continue;
                }
                $dup = Student::findOrFail($dupId);

                // ── Smart field merge ──
                $changes = [];

                // Rule A: empty-fill (always runs) — fill holes on keep from dup
                $emptyFillFields = ['whatsapp_phone', 'email', 'date_of_birth', 'visa_expiry_date', 'product_type_other'];
                foreach ($emptyFillFields as $field) {
                    if (blank($keep->$field) && filled($dup->$field)) {
                        $changes[$field] = [null, $dup->$field, 'filled from duplicate'];
                        $keep->$field = $dup->$field;
                    }
                }

                // Rule B: recency-override (only if dup is more recent) — carry over form-driven fields
                $dupIsNewer = $dup->form_submitted_at
                    && $keep->form_submitted_at
                    && $dup->form_submitted_at->gt($keep->form_submitted_at);

                if ($dupIsNewer) {
                    $recencyFields = ['course', 'university', 'intake', 'product_type', 'sales_price', 'sales_price_scholarship', 'pending_documents'];
                    foreach ($recencyFields as $field) {
                        if (filled($dup->$field) && $keep->$field !== $dup->$field) {
                            $changes[$field] = [$keep->$field, $dup->$field, 'from more recent form'];
                            $keep->$field = $dup->$field;
                        }
                    }
                }

                if (!empty($changes)) {
                    $keep->save();
                }

                // ── Move all child table rows from the duplicate to the kept record ──
                DB::table('notes')->where('student_id', $dup->id)->update(['student_id' => $keep->id]);
                DB::table('student_stage_logs')->where('student_id', $dup->id)->update(['student_id' => $keep->id]);
                DB::table('message_logs')->where('student_id', $dup->id)->update(['student_id' => $keep->id]);
                DB::table('notifications')->where('student_id', $dup->id)->update(['student_id' => $keep->id]);
                DB::table('scheduled_student_messages')->where('student_id', $dup->id)->update(['student_id' => $keep->id]);

                // ── Audit note listing what changed ──
                $noteLines = [
                    "Merged duplicate record #{$dup->id} ({$dup->name}). Original form submitted "
                        . ($dup->form_submitted_at?->format('d/m/Y') ?? 'unknown')
                        . ". Notes, stage logs and messages from the duplicate are now part of this record.",
                ];

                if (!empty($changes)) {
                    $noteLines[] = '';
                    $noteLines[] = 'Fields updated from duplicate:';
                    foreach ($changes as $field => [$old, $new, $reason]) {
                        $oldStr = $old === null || $old === '' ? '(empty)' : "\"{$old}\"";
                        $newStr = "\"{$new}\"";
                        $noteLines[] = "• {$field}: {$oldStr} → {$newStr} ({$reason})";
                    }
                }

                Note::create([
                    'student_id' => $keep->id,
                    'author_id'  => null,
                    'body'       => implode("\n", $noteLines),
                ]);

                // Soft delete the duplicate
                $dup->delete();
                $merged++;
            }
        });

        return redirect()
            ->route('admin.duplicates.index')
            ->with('success', "Merged {$merged} duplicate(s) into #{$keep->id}.");
    }

    private function findDuplicateGroups(): array
    {
        // 1. Find phone duplicates
        $phoneGroups = Student::whereNotNull('whatsapp_phone')
            ->where('whatsapp_phone', '!=', '')
            ->select('whatsapp_phone')
            ->groupBy('whatsapp_phone')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('whatsapp_phone');

        // 2. Find name duplicates (case-insensitive trimmed)
        $nameGroups = Student::select(DB::raw('LOWER(TRIM(name)) as norm_name'))
            ->groupBy('norm_name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('norm_name');

        $seen   = [];
        $groups = [];

        // 3. Build hydrated phone groups (priority)
        foreach ($phoneGroups as $phone) {
            $students = Student::withCount(['notes', 'stageLogs', 'messageLogs', 'scheduledMessages'])
                ->with(['assignedAgent', 'salesConsultant'])
                ->where('whatsapp_phone', $phone)
                ->orderByDesc('form_submitted_at')
                ->get();

            if ($students->count() < 2) continue;

            $groups[] = [
                'reason'   => "Same phone: {$phone}",
                'students' => $students,
            ];
            foreach ($students as $s) {
                $seen[$s->id] = true;
            }
        }

        // 4. Build hydrated name groups (skip students already in a phone group)
        foreach ($nameGroups as $normName) {
            $students = Student::withCount(['notes', 'stageLogs', 'messageLogs', 'scheduledMessages'])
                ->with(['assignedAgent', 'salesConsultant'])
                ->whereRaw('LOWER(TRIM(name)) = ?', [$normName])
                ->orderByDesc('form_submitted_at')
                ->get()
                ->filter(fn($s) => !isset($seen[$s->id]))
                ->values();

            if ($students->count() < 2) continue;

            $groups[] = [
                'reason'   => "Same name: {$normName}",
                'students' => $students,
            ];
            foreach ($students as $s) {
                $seen[$s->id] = true;
            }
        }

        return $groups;
    }
}
