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
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return view('admin.agents.index', compact('agents'));
    }

    public function create()
    {
        return view('admin.agents.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:users,email',
            'password'        => ['required', Password::min(8)],
            'whatsapp_phone'  => 'nullable|string|max:30',
            'role'            => 'nullable|in:cs_agent,application,sales_agent',
        ]);

        $user = User::create([
            'name'           => $request->name,
            'email'          => $request->email,
            'password'       => Hash::make($request->password),
            'role'           => $request->role ?: 'cs_agent',
            'whatsapp_phone' => $request->whatsapp_phone,
            'active'         => true,
        ]);

        $this->linkSalesConsultant($user);

        return redirect()->route('admin.agents.index')->with('success', 'User created.');
    }

    public function edit(User $agent)
    {
        abort_if($agent->role === 'admin', 403);
        return view('admin.agents.edit', compact('agent'));
    }

    public function update(Request $request, User $agent)
    {
        abort_if($agent->role === 'admin', 403);

        $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email,' . $agent->id,
            'password'       => ['nullable', Password::min(8)],
            'whatsapp_phone' => 'nullable|string|max:30',
            'active'         => 'boolean',
            'role'           => 'nullable|in:cs_agent,application,sales_agent',
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

        $this->linkSalesConsultant($agent->fresh());

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
     * If the user is a sales_agent, find or create the SalesConsultant record
     * matching their name and link it via user_id. This is what lets the sales
     * agent see their historical book of business (students from before the
     * in-CRM handoff existed) on /sales/leads/ongoing.
     *
     * Match by exact name (case-insensitive, trimmed). The webhook's
     * AssignmentService creates SalesConsultant rows by raw name, so most
     * legacy consultants exist with the typed-by-the-form spelling.
     */
    private function linkSalesConsultant(User $user): void
    {
        if ($user->role !== 'sales_agent') {
            return;
        }

        $name = trim($user->name);

        // Prefer exact match; fall back to case-insensitive.
        $consultant = SalesConsultant::where('name', $name)->first()
            ?? SalesConsultant::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        if ($consultant) {
            // Don't steal a consultant already linked to a different user.
            if ($consultant->user_id && $consultant->user_id !== $user->id) {
                return;
            }
            $consultant->update(['user_id' => $user->id]);
        } else {
            SalesConsultant::create(['name' => $name, 'user_id' => $user->id]);
        }
    }
}
