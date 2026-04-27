<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentStageLog;
use App\Models\User;
use App\Services\HandoffService;
use App\Services\PhoneNormaliser;
use Illuminate\Http\Request;

class KanbanController extends Controller
{
    // ── Kanban board ─────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        $query = Student::salesLeadsOnly()->with('assignedSalesAgent:id,name');

        if (! $isAdmin) {
            $query->where('assigned_sales_agent_id', $user->id);
        }

        if ($request->filled('agent')) {
            $query->where('assigned_sales_agent_id', $request->agent);
        }
        if ($request->filled('temperature')) {
            $query->where('temperature', $request->temperature);
        }

        $leads = $query->orderBy('next_followup_date')->get();

        $stages = [];
        foreach (Student::allSalesStages() as $stage) {
            $stages[$stage] = $leads->where('sales_stage', $stage)->values();
        }

        $salesAgents = $isAdmin
            ? User::where('role', 'sales_agent')->orderBy('name')->get()
            : collect();

        return view('sales.kanban', compact('stages', 'salesAgents', 'isAdmin'));
    }

    // ── Ongoing (handed-off) leads ───────────────────────────────────────────
    // Sales retains read-only visibility on students they sold, whether handed
    // off via the in-CRM button (handed_off_by = $user->id) or historically via
    // the form webhook with their name as Sales Advisor (sales_consultant linked
    // to $user via SalesConsultant.user_id). Both populate this list for the
    // originating sales agent; admins see everything.

    public function ongoing(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        $query = Student::query()
            ->with(['assignedAgent:id,name', 'handedOffBy:id,name', 'salesConsultant:id,name,user_id']);

        if ($isAdmin) {
            // Admins see every handed-off OR consultant-linked student.
            $query->where(function ($q) {
                $q->whereNotNull('handed_off_at')
                  ->orWhereHas('salesConsultant', fn ($c) => $c->whereNotNull('user_id'));
            });
        } else {
            // A sales agent sees their own handoffs + their historical book of
            // business (students linked through their SalesConsultant record).
            $query->where(function ($q) use ($user) {
                $q->where('handed_off_by', $user->id)
                  ->orWhereHas('salesConsultant', fn ($c) => $c->where('user_id', $user->id));
            });
        }

        // Search across name / email / whatsapp_phone (LIKE).
        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%")
                  ->orWhere('whatsapp_phone', 'like', "%{$term}%");
            });
        }

        $leads = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        return view('sales.ongoing', compact('leads', 'isAdmin'));
    }

    // ── Read-only student detail (handed-off + historical) ──────────────────
    // Sales agents drill in to a student in their book to see CS status,
    // application status, and stage history. Pure observation — zero edit
    // affordances. CS drives the pipeline; sales just watches.

    public function showStudent(Request $request, Student $student)
    {
        $this->authorizeStudentVisibility($request, $student);

        $student->load([
            'assignedAgent:id,name',
            'salesConsultant:id,name,user_id',
            'handedOffBy:id,name',
            'stageLogs.changedBy:id,name',
            'notes.author:id,name',
        ]);

        return view('sales.student-show', compact('student'));
    }

    // ── Lead detail ──────────────────────────────────────────────────────────

    public function show(Request $request, Student $student)
    {
        // Route binding goes through the global scope by default, which would
        // 404 sales-leads. We need the bypassed lookup here.
        $student = $this->findSalesLeadOrFail($student->id);
        $this->authorizeSalesAccess($request, $student);

        $student->load([
            'assignedSalesAgent:id,name',
            'salesConsultant',
            'notes.author',
            'stageLogs',
        ]);

        return view('sales.lead-show', compact('student'));
    }

    // ── Edit lead ────────────────────────────────────────────────────────────

    public function edit(Request $request, Student $student)
    {
        $student = $this->findSalesLeadOrFail($student->id);
        $this->authorizeSalesAccess($request, $student);

        return view('sales.lead-edit', compact('student'));
    }

    public function update(Request $request, Student $student)
    {
        $student = $this->findSalesLeadOrFail($student->id);
        $this->authorizeSalesAccess($request, $student);

        $data = $request->validate([
            'primeiro_nome'           => 'nullable|string|max:255',
            'sobrenome'               => 'nullable|string|max:255',
            'nome_social'             => 'nullable|string|max:255',
            'email'                   => 'nullable|email|max:255',
            'whatsapp_phone'          => 'nullable|string|max:30',
            'temperature'             => 'nullable|string|in:quente,morno,frio',
            'lead_quality'            => 'nullable|integer|min:1|max:5',
            'product_type'            => 'nullable|string',
            'date_of_birth'           => 'nullable|date',
            'visa_type'               => 'nullable|string|in:eu_passport,stamp_2,stamp_1_4',
            'visa_expiry_date'        => 'nullable|date',
            'sales_price'             => 'nullable|numeric|min:0',
            'sales_price_scholarship' => 'nullable|numeric|min:0',
            'meeting_date'            => 'nullable|date',
            'objection_reason'        => 'nullable|string',
            'observations'            => 'nullable|string',
            'course'                  => 'nullable|string|max:255',
            'university'              => 'nullable|string|max:255',
            'intake'                  => 'nullable|string|max:50',
        ]);

        if (!empty($data['whatsapp_phone'])) {
            $data['whatsapp_phone'] = PhoneNormaliser::normalise($data['whatsapp_phone']);
        }

        // Keep `name` in sync with primeiro_nome + sobrenome for legacy code that
        // reads the single `name` field (it's used by the existing CS dashboard,
        // notifications, search, etc.).
        if (isset($data['primeiro_nome']) || isset($data['sobrenome'])) {
            $first = $data['primeiro_nome'] ?? $student->primeiro_nome ?? '';
            $last  = $data['sobrenome'] ?? $student->sobrenome ?? '';
            $data['name'] = trim($first . ' ' . $last) ?: $student->name;
        }

        $student->update($data);

        return redirect()->route('sales.leads.show', $student)->with('success', 'Lead updated.');
    }

    // ── Create lead ──────────────────────────────────────────────────────────

    public function create()
    {
        return view('sales.lead-create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'primeiro_nome'  => 'required|string|max:255',
            'sobrenome'      => 'required|string|max:255',
            'nome_social'    => 'nullable|string|max:255',
            'whatsapp_phone' => 'required|string|max:30',
            'email'          => 'nullable|email|max:255',
        ]);

        $phone = PhoneNormaliser::normalise($request->whatsapp_phone);

        // Phone collision check across BOTH pipelines — even though sales-leads
        // are scoped out by default, we lift the scope here to catch a sales
        // creating a lead for someone who's already a CS student.
        $existing = Student::withoutGlobalScope('exclude_sales_leads')
            ->where('whatsapp_phone', $phone)
            ->first();
        if ($existing) {
            return back()->withInput()->withErrors([
                'whatsapp_phone' => 'A student or lead with this phone number already exists.',
            ]);
        }

        $student = Student::create([
            'name'                    => trim($request->primeiro_nome . ' ' . $request->sobrenome),
            'primeiro_nome'           => $request->primeiro_nome,
            'sobrenome'               => $request->sobrenome,
            'nome_social'             => $request->nome_social,
            'email'                   => $request->email,
            'whatsapp_phone'          => $phone,
            'sales_stage'             => 'cadastro',
            'assigned_sales_agent_id' => $request->user()->id,
            'source'                  => 'manual',
            'current_journey_cycle'   => 1,
        ]);

        StudentStageLog::create([
            'student_id' => $student->id,
            'from_stage' => null,
            'to_stage'   => 'cadastro',
            'changed_by' => $request->user()->id,
            'changed_at' => now(),
        ]);

        return redirect()->route('sales.leads.show', $student)->with('success', 'Lead created.');
    }

    // ── Stage change (kanban drag-and-drop) ──────────────────────────────────

    public function updateStage(Request $request, Student $student)
    {
        $student = $this->findSalesLeadOrFail($student->id);
        $this->authorizeSalesAccess($request, $student);

        $request->validate([
            'sales_stage' => 'required|string|in:' . implode(',', Student::allSalesStages()),
        ]);

        $oldStage = $student->sales_stage;
        $newStage = $request->sales_stage;

        if ($oldStage === $newStage) {
            return $request->wantsJson() ? response()->json(['ok' => true]) : back();
        }

        $errors = $this->validateStageRequirements($student, $newStage);
        if ($errors) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $errors], 422);
            }
            return back()->withErrors(['sales_stage' => $errors]);
        }

        $student->update(['sales_stage' => $newStage]);

        StudentStageLog::create([
            'student_id' => $student->id,
            'from_stage' => $oldStage,
            'to_stage'   => $newStage,
            'changed_by' => $request->user()->id,
            'changed_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'sales_stage' => $newStage]);
        }
        return redirect()->route('sales.leads.show', $student)->with('success', "Moved to {$newStage}.");
    }

    // ── Quick temperature update ─────────────────────────────────────────────

    public function updateTemperature(Request $request, Student $student)
    {
        $student = $this->findSalesLeadOrFail($student->id);
        $this->authorizeSalesAccess($request, $student);

        $request->validate(['temperature' => 'required|in:quente,morno,frio']);
        $student->update(['temperature' => $request->temperature]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'temperature' => $request->temperature]);
        }
        return back()->with('success', 'Temperature updated.');
    }

    // ── Follow-up date update ────────────────────────────────────────────────

    public function updateFollowup(Request $request, Student $student)
    {
        $student = $this->findSalesLeadOrFail($student->id);
        $this->authorizeSalesAccess($request, $student);

        $request->validate([
            'next_followup_date' => 'nullable|date',
            'next_followup_note' => 'nullable|string|max:500',
        ]);

        $student->update($request->only('next_followup_date', 'next_followup_note'));

        return redirect()->route('sales.leads.show', $student)->with('success', 'Follow-up updated.');
    }

    // ── Add note ─────────────────────────────────────────────────────────────

    public function storeNote(Request $request, Student $student)
    {
        $student = $this->findSalesLeadOrFail($student->id);
        $this->authorizeSalesAccess($request, $student);

        $request->validate(['body' => 'required|string|max:2000']);

        $student->notes()->create([
            'body'      => $request->body,
            'author_id' => $request->user()->id,
        ]);

        return redirect()->route('sales.leads.show', $student)->with('success', 'Note added.');
    }

    // ── Handoff to CS ────────────────────────────────────────────────────────

    public function handoff(Request $request, Student $student)
    {
        $student = $this->findSalesLeadOrFail($student->id);
        $this->authorizeSalesAccess($request, $student);

        if ($student->sales_stage !== 'fechamento') {
            return back()->withErrors(['handoff' => 'Lead must be in Fechamento stage to hand off.']);
        }

        $errors = $this->validateHandoffRequirements($student);
        if ($errors) {
            return back()->withErrors(['handoff' => $errors]);
        }

        app(HandoffService::class)->execute($student, $request->user());

        return redirect()->route('sales.kanban')
            ->with('success', "Lead \"{$student->name}\" handed off to CS pipeline.");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Find a sales-lead by ID, bypassing the global scope. 404 if it isn't one.
     * Route binding through `Student $student` triggers the global scope which
     * filters out sales-leads — so we re-fetch by ID via salesLeadsOnly.
     */
    private function findSalesLeadOrFail(int $id): Student
    {
        $student = Student::salesLeadsOnly()->find($id);
        abort_if(!$student, 404);
        return $student;
    }

    private function authorizeSalesAccess(Request $request, Student $student): void
    {
        if (! $request->user()->isAdmin()
            && $student->assigned_sales_agent_id !== $request->user()->id) {
            abort(403);
        }
    }

    /**
     * Read-only visibility for a CS student in the sales agent's book.
     * Allowed if the student was handed off by this user OR is linked through
     * the user's SalesConsultant. Admins always allowed.
     */
    private function authorizeStudentVisibility(Request $request, Student $student): void
    {
        $user = $request->user();
        if ($user->isAdmin()) {
            return;
        }

        $ownsViaHandoff    = $student->handed_off_by === $user->id;
        $ownsViaConsultant = $student->salesConsultant
            && $student->salesConsultant->user_id === $user->id;

        if (!$ownsViaHandoff && !$ownsViaConsultant) {
            abort(403);
        }
    }

    private function validateStageRequirements(Student $student, string $newStage): ?string
    {
        $errors = [];

        if ($newStage === 'qualificado' && empty($student->temperature)) {
            $errors[] = 'Temperature is required to move to Qualificado.';
        }

        if ($newStage === 'negociacao' && empty($student->sales_price)) {
            $errors[] = 'Sales price is required to move to Negociação.';
        }

        if ($newStage === 'fechamento') {
            if (empty($student->email)) $errors[] = 'Email is required.';
            if (empty($student->product_type)) $errors[] = 'Product type is required.';
            if (empty($student->temperature)) $errors[] = 'Temperature is required.';
            if (empty($student->sales_price)) $errors[] = 'Sales price is required.';
        }

        return $errors ? implode(' ', $errors) : null;
    }

    private function validateHandoffRequirements(Student $student): ?string
    {
        $errors = [];

        if (empty($student->primeiro_nome)) $errors[] = 'First name is required.';
        if (empty($student->sobrenome)) $errors[] = 'Last name is required.';
        if (empty($student->email)) $errors[] = 'Email is required.';
        if (empty($student->whatsapp_phone)) $errors[] = 'WhatsApp phone is required.';
        if (empty($student->product_type)) $errors[] = 'Product type is required.';
        if (empty($student->sales_price)) $errors[] = 'Sales price is required.';

        return $errors ? implode(' ', $errors) : null;
    }
}
