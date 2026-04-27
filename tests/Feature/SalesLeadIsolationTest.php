<?php

namespace Tests\Feature;

use App\Models\SalesConsultant;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pipeline isolation guard. Sales-leads (rows with sales_stage IS NOT NULL)
 * must be invisible to every existing CS / Admin / Apps / API query. The Student
 * model has a global scope that enforces this; this test pins it down so a future
 * commit can't silently regress.
 *
 * If any of these assertions fail: the global scope on Student is broken or has
 * been lifted somewhere it shouldn't be. Audit the change before proceeding.
 */
class SalesLeadIsolationTest extends TestCase
{
    use RefreshDatabase;

    private SalesConsultant $consultant;
    private User $admin;
    private User $csAgent;
    private User $salesAgent;
    private Student $csStudent;
    private Student $salesLead;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consultant = SalesConsultant::create(['name' => 'Iso Consultant']);
        $this->admin      = User::factory()->create(['role' => 'admin',       'active' => true]);
        $this->csAgent    = User::factory()->create(['role' => 'cs_agent',    'active' => true]);
        $this->salesAgent = User::factory()->create(['role' => 'sales_agent', 'active' => true]);

        $this->csStudent = Student::create([
            'name'                => 'CS Student',
            'email'               => 'cs@iso.invalid',
            'whatsapp_phone'      => '+353800000001',
            'product_type'        => 'higher_education',
            'status'              => 'waiting_initial_documents',
            'sales_consultant_id' => $this->consultant->id,
            'assigned_cs_agent_id'=> $this->csAgent->id,
            'form_submitted_at'   => now(),
            'source'              => 'form',
        ]);

        $this->salesLead = Student::create([
            'name'                    => 'Sales Lead',
            'primeiro_nome'           => 'Sales',
            'sobrenome'               => 'Lead',
            'whatsapp_phone'          => '+353800000002',
            'sales_stage'             => 'cadastro',
            'assigned_sales_agent_id' => $this->salesAgent->id,
            'source'                  => 'manual',
        ]);
    }

    public function test_default_count_excludes_sales_leads(): void
    {
        $this->assertSame(1, Student::count(), 'Default Student::count must exclude sales-leads.');
    }

    public function test_phone_lookup_does_not_find_sales_lead(): void
    {
        $found = Student::where('whatsapp_phone', $this->salesLead->whatsapp_phone)->first();
        $this->assertNull($found, 'Phone lookup must not surface a sales-lead.');
    }

    public function test_phone_lookup_still_finds_cs_student(): void
    {
        $found = Student::where('whatsapp_phone', $this->csStudent->whatsapp_phone)->first();
        $this->assertNotNull($found);
        $this->assertSame($this->csStudent->id, $found->id);
    }

    public function test_sales_leads_only_scope_returns_only_leads(): void
    {
        $leads = Student::salesLeadsOnly()->get();
        $this->assertCount(1, $leads);
        $this->assertSame($this->salesLead->id, $leads->first()->id);
    }

    public function test_without_global_scope_sees_everything(): void
    {
        $all = Student::withoutGlobalScope('exclude_sales_leads')->count();
        $this->assertSame(2, $all);
    }

    public function test_admin_students_index_does_not_show_sales_lead(): void
    {
        $resp = $this->actingAs($this->admin)->get(route('admin.students.index'));
        $resp->assertOk();
        $resp->assertSee('CS Student');
        $resp->assertDontSee('Sales Lead');
    }

    public function test_my_students_index_does_not_show_sales_lead(): void
    {
        $resp = $this->actingAs($this->csAgent)->get(route('my.students.index'));
        $resp->assertOk();
        $resp->assertDontSee('Sales Lead');
    }

    public function test_api_match_does_not_find_sales_lead(): void
    {
        $token = $this->csAgent->createToken('test')->plainTextToken;

        $resp = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/students/match?phone=' . urlencode($this->salesLead->whatsapp_phone));

        $resp->assertOk();
        $resp->assertExactJson(['student' => null]);
    }

    public function test_api_match_finds_cs_student(): void
    {
        $token = $this->csAgent->createToken('test')->plainTextToken;

        $resp = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/students/match?phone=' . urlencode($this->csStudent->whatsapp_phone));

        $resp->assertOk();
        $body = $resp->json();
        $this->assertNotNull($body['student'] ?? null);
        $this->assertSame($this->csStudent->id, $body['student']['id']);
    }

    public function test_sales_agent_sees_their_historical_students_via_consultant_link(): void
    {
        // Two sales agents, each linked to their own SalesConsultant record
        // (the Sales Advisor name on legacy form submissions).
        /** @var User $romario */
        $romario = User::factory()->create(['role' => 'sales_agent', 'name' => 'Romario', 'active' => true]);
        /** @var User $maria */
        $maria   = User::factory()->create(['role' => 'sales_agent', 'name' => 'Maria',   'active' => true]);

        $romarioConsultant = SalesConsultant::create(['name' => 'Romario', 'user_id' => $romario->id]);
        $mariaConsultant   = SalesConsultant::create(['name' => 'Maria',   'user_id' => $maria->id]);

        // Pre-existing CS students (came in via form webhook before the prototype).
        Student::create([
            'name'                => 'Romarios Old Student',
            'email'               => 'old-romario@iso.invalid',
            'whatsapp_phone'      => '+353800000010',
            'product_type'        => 'higher_education',
            'status'              => 'waiting_initial_documents',
            'sales_consultant_id' => $romarioConsultant->id,
            'assigned_cs_agent_id'=> $this->csAgent->id,
            'form_submitted_at'   => now()->subMonths(3),
            'source'              => 'form',
        ]);
        Student::create([
            'name'                => 'Marias Old Student',
            'email'               => 'old-maria@iso.invalid',
            'whatsapp_phone'      => '+353800000011',
            'product_type'        => 'higher_education',
            'status'              => 'waiting_initial_documents',
            'sales_consultant_id' => $mariaConsultant->id,
            'assigned_cs_agent_id'=> $this->csAgent->id,
            'form_submitted_at'   => now()->subMonths(2),
            'source'              => 'form',
        ]);

        // Romario hits /sales/leads/ongoing — should see his historical student, not Maria's.
        $resp = $this->actingAs($romario)->get(route('sales.leads.ongoing'));
        $resp->assertOk();
        $resp->assertSee('Romarios Old Student');
        $resp->assertDontSee('Marias Old Student');

        // Maria hits /sales/leads/ongoing — should see hers, not Romario's.
        $resp = $this->actingAs($maria)->get(route('sales.leads.ongoing'));
        $resp->assertOk();
        $resp->assertSee('Marias Old Student');
        $resp->assertDontSee('Romarios Old Student');
    }

    public function test_historical_consultant_link_does_not_break_global_scope(): void
    {
        // Linking a consultant to a sales-agent user must NOT make sales-leads
        // visible to the CS agent through any indirect path.
        $romario = User::factory()->create(['role' => 'sales_agent', 'name' => 'Romario', 'active' => true]);
        SalesConsultant::create(['name' => 'Romario', 'user_id' => $romario->id]);

        // Default Student count must still exclude the existing sales-lead.
        $this->assertSame(1, Student::count());

        // CS agent /my/students must still NOT show the sales-lead.
        $resp = $this->actingAs($this->csAgent)->get(route('my.students.index'));
        $resp->assertOk();
        $resp->assertDontSee('Sales Lead');
    }

    public function test_agent_controller_auto_links_consultant_on_create(): void
    {
        // Create a consultant ahead of time (simulating webhook history).
        SalesConsultant::create(['name' => 'Carlos']);

        $resp = $this->actingAs($this->admin)->post(route('admin.agents.store'), [
            'name'     => 'Carlos',
            'email'    => 'carlos@iso.invalid',
            'password' => 'password123',
            'role'     => 'sales_agent',
        ]);
        $resp->assertRedirect(route('admin.agents.index'));

        $carlos = User::where('email', 'carlos@iso.invalid')->first();
        $this->assertNotNull($carlos);
        $this->assertSame('sales_agent', $carlos->role);

        $consultant = SalesConsultant::where('name', 'Carlos')->first();
        $this->assertSame($carlos->id, $consultant->user_id,
            'AgentController must auto-link the matching SalesConsultant.');
    }

    public function test_agent_controller_explicit_consultant_pick_overrides_name_match(): void
    {
        // Two consultants with similar names — admin will explicitly pick one.
        $picked   = SalesConsultant::create(['name' => 'R. Silva (legacy spelling)']);
        $ignored  = SalesConsultant::create(['name' => 'Romario']);

        $resp = $this->actingAs($this->admin)->post(route('admin.agents.store'), [
            'name'                => 'Romario',
            'email'               => 'romario-explicit@iso.invalid',
            'password'            => 'password123',
            'role'                => 'sales_agent',
            'sales_consultant_id' => $picked->id,
        ]);
        $resp->assertRedirect(route('admin.agents.index'));

        $user = User::where('email', 'romario-explicit@iso.invalid')->first();

        // The picked consultant must be linked, NOT the name-matched one.
        $this->assertSame($user->id, $picked->fresh()->user_id);
        $this->assertNull($ignored->fresh()->user_id,
            'Auto-link by name must be skipped when admin made an explicit pick.');
    }

    public function test_agent_controller_can_relink_consultant_on_edit(): void
    {
        $oldConsultant = SalesConsultant::create(['name' => 'Old Pick']);
        $newConsultant = SalesConsultant::create(['name' => 'New Pick']);

        // Create the user already linked to the old consultant.
        $resp = $this->actingAs($this->admin)->post(route('admin.agents.store'), [
            'name'                => 'Switcher',
            'email'               => 'switcher@iso.invalid',
            'password'            => 'password123',
            'role'                => 'sales_agent',
            'sales_consultant_id' => $oldConsultant->id,
        ]);
        $resp->assertRedirect();
        $user = User::where('email', 'switcher@iso.invalid')->first();
        $this->assertSame($user->id, $oldConsultant->fresh()->user_id);

        // Admin edits and switches the consultant link.
        $resp = $this->actingAs($this->admin)->put(route('admin.agents.update', $user), [
            'name'                => 'Switcher',
            'email'               => 'switcher@iso.invalid',
            'role'                => 'sales_agent',
            'active'              => 1,
            'sales_consultant_id' => $newConsultant->id,
        ]);
        $resp->assertRedirect();

        // Old should be unlinked, new should be linked.
        $this->assertNull($oldConsultant->fresh()->user_id, 'Old link must be cleared on relink.');
        $this->assertSame($user->id, $newConsultant->fresh()->user_id, 'New link must be set.');
    }

    public function test_with_trashed_and_only_trashed_still_exclude_sales_leads(): void
    {
        // Soft-delete the lead so it would appear in trashed queries if the scope failed.
        $this->salesLead->delete();

        // withTrashed() lifts only the SoftDeletingScope; exclude_sales_leads must still apply.
        $this->assertFalse(
            Student::withTrashed()->where('id', $this->salesLead->id)->exists(),
            'withTrashed() must not surface a soft-deleted sales-lead.'
        );

        // onlyTrashed() likewise.
        $this->assertFalse(
            Student::onlyTrashed()->where('id', $this->salesLead->id)->exists(),
            'onlyTrashed() must not see a soft-deleted sales-lead.'
        );

        // Sanity: lifting BOTH scopes confirms the row still exists in the DB,
        // it's just been correctly hidden by the safety guard.
        $this->assertTrue(
            Student::withoutGlobalScope('exclude_sales_leads')
                ->withTrashed()
                ->where('id', $this->salesLead->id)
                ->exists(),
            'Sanity: the soft-deleted lead must still exist when both scopes are lifted.'
        );
    }

    public function test_handoff_makes_lead_visible_to_cs(): void
    {
        // Pre-handoff: CS agent sees only the CS student (1 row).
        $this->assertSame(1, Student::count());

        // Promote the sales-lead so it can be handed off.
        $this->salesLead->update([
            'sales_stage'    => 'fechamento',
            'email'          => 'lead@iso.invalid',
            'product_type'   => 'higher_education',
            'temperature'    => 'quente',
            'sales_price'    => 5000,
        ]);

        // Run the handoff.
        app(\App\Services\HandoffService::class)->execute(
            Student::salesLeadsOnly()->find($this->salesLead->id),
            $this->salesAgent
        );

        // Post-handoff: the row is now a CS student. Default count = 2.
        $this->assertSame(2, Student::count(), 'Handoff must make the lead visible to CS-side queries.');

        $reloaded = Student::find($this->salesLead->id);
        $this->assertNotNull($reloaded);
        $this->assertNull($reloaded->sales_stage);
        $this->assertSame('waiting_initial_documents', $reloaded->status);
        $this->assertNotNull($reloaded->handed_off_at);
    }
}
