<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesConsultant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AgentController extends Controller
{
    public function index()
    {
        $agents = User::whereIn('role', ['cs_agent', 'application', 'sales_agent'])
            ->withCount('assignedStudents')
            ->with('salesConsultant:id,name,user_id')
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return view('admin.agents.index', compact('agents'));
    }

    public function create()
    {
        $consultants = $this->consultantOptions();
        return view('admin.agents.create', compact('consultants'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                => 'required|string|max:255',
            'email'               => 'required|email|unique:users,email',
            'password'            => ['required', Password::min(8)],
            'whatsapp_phone'      => 'nullable|string|max:30',
            'role'                => 'nullable|in:cs_agent,application,sales_agent',
            'sales_consultant_id' => 'nullable|integer|exists:sales_consultants,id',
        ]);

        $user = User::create([
            'name'           => $request->name,
            'email'          => $request->email,
            'password'       => Hash::make($request->password),
            'role'           => $request->role ?: 'cs_agent',
            'whatsapp_phone' => $request->whatsapp_phone,
            'active'         => true,
        ]);

        $this->linkSalesConsultant($user, $request->input('sales_consultant_id'));

        return redirect()->route('admin.agents.index')->with('success', 'User created.');
    }

    public function edit(User $agent)
    {
        abort_if($agent->role === 'admin', 403);
        $agent->load('salesConsultant:id,name,user_id');
        $consultants = $this->consultantOptions($agent);
        return view('admin.agents.edit', compact('agent', 'consultants'));
    }

    public function update(Request $request, User $agent)
    {
        abort_if($agent->role === 'admin', 403);

        $request->validate([
            'name'                => 'required|string|max:255',
            'email'               => 'required|email|unique:users,email,' . $agent->id,
            'password'            => ['nullable', Password::min(8)],
            'whatsapp_phone'      => 'nullable|string|max:30',
            'active'              => 'boolean',
            'role'                => 'nullable|in:cs_agent,application,sales_agent',
            'sales_consultant_id' => 'nullable|integer|exists:sales_consultants,id',
        ]);

        $data = [
            'name'           => $request->name,
            'email'          => $request->email,
            'whatsapp_phone' => $request->whatsapp_phone,
            'active'         => $request->boolean('active'),
            'role'           => $request->role ?: $agent->role,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $agent->update($data);

        $this->linkSalesConsultant($agent->fresh(), $request->input('sales_consultant_id'));

        return redirect()->route('admin.agents.index')->with('success', 'User updated.');
    }

    public function destroy(User $agent)
    {
        abort_if($agent->role === 'admin', 403);

        // Unassign students rather than hard delete
        $agent->assignedStudents()->update(['assigned_cs_agent_id' => null]);
        $agent->tokens()->delete();
        $agent->delete();

        return redirect()->route('admin.agents.index')->with('success', 'Agent deleted. Their students have been unassigned.');
    }

    /**
     * Build the SalesConsultant dropdown options.
     *
     * For create: only consultants with no user link.
     * For edit:   include the currently-linked consultant (so it stays selected)
     *             plus all unlinked ones.
     *
     * Each option shows the student count so admin knows which consultant has
     * Romario's actual book of business.
     */
    private function consultantOptions(?User $editing = null)
    {
        $query = SalesConsultant::query()
            ->withCount('students')
            ->orderByDesc('students_count')
            ->orderBy('name');

        if ($editing && $editing->salesConsultant) {
            $query->where(function ($q) use ($editing) {
                $q->whereNull('user_id')
                  ->orWhere('id', $editing->salesConsultant->id);
            });
        } else {
            $query->whereNull('user_id');
        }

        return $query->get(['id', 'name', 'user_id', 'students_count']);
    }

    /**
     * Link a sales_agent user to a SalesConsultant. Three priority paths:
     *
     *   1. Explicit pick (admin selected from the dropdown) — the safe path.
     *      We always honour this when provided, including unlinking when "none".
     *   2. Auto-link by name match — fallback for first creation when admin
     *      didn't pick. Matches case-insensitively, trimmed.
     *   3. Create a new consultant with the user's name — only if no match
     *      exists and no explicit pick was made.
     *
     * Refuses to overwrite a consultant already linked to a different user
     * (no stealing the book of business).
     */
    private function linkSalesConsultant(User $user, mixed $explicitConsultantId = null): void
    {
        if ($user->role !== 'sales_agent') {
            // If demoting from sales_agent, optionally clear the link. For now
            // leave it — admin can clear via the dropdown if needed.
            return;
        }

        // Path 1: explicit pick (or explicit "none" if blank string was sent).
        if ($explicitConsultantId !== null && $explicitConsultantId !== '') {
            $picked = SalesConsultant::find($explicitConsultantId);
            if (!$picked) return;

            if ($picked->user_id && $picked->user_id !== $user->id) {
                // Already linked to a different user — refuse silently.
                return;
            }

            // First, unlink any other consultant currently linked to this user
            // (admin is choosing a different one).
            SalesConsultant::where('user_id', $user->id)
                ->where('id', '!=', $picked->id)
                ->update(['user_id' => null]);

            $picked->update(['user_id' => $user->id]);
            return;
        }

        // If user already has a link from a previous save, leave it alone.
        if ($user->salesConsultant) {
            return;
        }

        // Path 2: name match fallback.
        $name = trim($user->name);
        $consultant = SalesConsultant::where('name', $name)->first()
            ?? SalesConsultant::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        if ($consultant) {
            if ($consultant->user_id && $consultant->user_id !== $user->id) {
                return;
            }
            $consultant->update(['user_id' => $user->id]);
            return;
        }

        // Path 3: create a fresh consultant for this user.
        SalesConsultant::create(['name' => $name, 'user_id' => $user->id]);
    }
}
