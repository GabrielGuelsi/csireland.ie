<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\SalesConsultant;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecialApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.webhook.secret' => 'test-webhook-secret']);
    }

    private function consultant(): SalesConsultant
    {
        return SalesConsultant::firstOrCreate(['name' => 'Test Consultant']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'active' => true]);
    }

    private function student(array $overrides = []): Student
    {
        return Student::create(array_merge([
            'name'                => 'Alice Form',
            'email'               => 'alice@test.invalid',
            'product_type'        => 'higher_education',
            'sales_consultant_id' => $this->consultant()->id,
            'status'              => 'waiting_initial_documents',
            'form_submitted_at'   => now(),
        ], $overrides));
    }

    private function postForm(array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/webhook/form', $payload, [
            'Authorization' => 'Bearer test-webhook-secret',
        ]);
    }

    public function test_webhook_ingests_condicao_diferenciada_and_entrada_reduzida(): void
    {
        SalesConsultant::firstOrCreate(['name' => 'Test Advisor']);

        $this->postForm([
            'Student full name'        => ['Bruna Special'],
            'Student email'            => ['bruna@test.invalid'],
            'Student WhatsApp number'  => ['+353861234567'],
            'Sales Advisor'            => ['Test Advisor'],
            'Product type'             => ['Higher Education'],
            'Condição diferenciada'    => ['Seguro Governamental Gratuito', 'Duolingo'],
            'Entrada Reduzida'         => ['150'],
        ])->assertOk();

        $s = Student::where('name', 'Bruna Special')->first();
        $this->assertNotNull($s);
        $this->assertEquals(['gov_insurance_free', 'duolingo'], $s->special_condition_options);
        $this->assertNull($s->special_condition_other);
        $this->assertSame('pending', $s->special_condition_status);
        $this->assertEquals(150.0, (float) $s->reduced_entry_amount);
        $this->assertNull($s->reduced_entry_other);
        $this->assertSame('pending', $s->reduced_entry_status);
    }

    public function test_webhook_without_special_fields_leaves_statuses_null(): void
    {
        SalesConsultant::firstOrCreate(['name' => 'Test Advisor']);

        $this->postForm([
            'Student full name' => ['Clean Form'],
            'Student email'     => ['clean@test.invalid'],
            'Sales Advisor'     => ['Test Advisor'],
            'Product type'      => ['Higher Education'],
        ])->assertOk();

        $s = Student::where('name', 'Clean Form')->first();
        $this->assertNotNull($s);
        $this->assertNull($s->special_condition_status);
        $this->assertNull($s->reduced_entry_status);
        $this->assertNull($s->special_condition_options);
    }

    public function test_webhook_with_outro_values_captures_free_text(): void
    {
        SalesConsultant::firstOrCreate(['name' => 'Test Advisor']);

        $this->postForm([
            'Student full name'     => ['Outro Student'],
            'Student email'         => ['outro@test.invalid'],
            'Sales Advisor'         => ['Test Advisor'],
            'Product type'          => ['Higher Education'],
            'Condição diferenciada' => ['Outro: custom deal'],
            'Entrada Reduzida'      => ['Outro: parceled in 3x'],
        ])->assertOk();

        $s = Student::where('name', 'Outro Student')->first();
        $this->assertEquals(['other'], $s->special_condition_options);
        $this->assertSame('custom deal', $s->special_condition_other);
        $this->assertNull($s->reduced_entry_amount);
        $this->assertSame('parceled in 3x', $s->reduced_entry_other);
        $this->assertSame('pending', $s->special_condition_status);
        $this->assertSame('pending', $s->reduced_entry_status);
    }

    public function test_webhook_with_zero_entry_still_requires_approval(): void
    {
        SalesConsultant::firstOrCreate(['name' => 'Test Advisor']);

        $this->postForm([
            'Student full name' => ['Zero Entry'],
            'Student email'     => ['zero@test.invalid'],
            'Sales Advisor'     => ['Test Advisor'],
            'Product type'      => ['Higher Education'],
            'Entrada Reduzida'  => ['0'],
        ])->assertOk();

        $s = Student::where('name', 'Zero Entry')->first();
        $this->assertEquals(0.0, (float) $s->reduced_entry_amount);
        $this->assertSame('pending', $s->reduced_entry_status);
    }

    public function test_admin_can_approve_special_condition(): void
    {
        $admin = $this->admin();
        $s = $this->student([
            'special_condition_options' => ['gov_insurance_free'],
            'special_condition_status'  => 'pending',
        ]);

        $this->actingAs($admin)->patch(
            route('admin.applications.special-approvals.update', $s),
            ['field' => 'special_condition', 'decision' => 'approved', 'notes' => 'Signed off.']
        )->assertRedirect();

        $s->refresh();
        $this->assertSame('approved', $s->special_condition_status);
        $this->assertSame($admin->id, $s->special_condition_reviewed_by);
        $this->assertNotNull($s->special_condition_reviewed_at);
        $this->assertSame('Signed off.', $s->special_condition_review_notes);

        $this->assertDatabaseHas('activity_logs', [
            'student_id' => $s->id,
            'user_id'    => $admin->id,
            'action'     => 'special_condition_approved',
            'old_value'  => 'pending',
            'new_value'  => 'approved',
        ]);
    }

    public function test_admin_can_reject_reduced_entry_and_values_are_retained(): void
    {
        $admin = $this->admin();
        $s = $this->student([
            'reduced_entry_amount' => 250,
            'reduced_entry_status' => 'pending',
        ]);

        $this->actingAs($admin)->patch(
            route('admin.applications.special-approvals.update', $s),
            ['field' => 'reduced_entry', 'decision' => 'rejected']
        )->assertRedirect();

        $s->refresh();
        $this->assertSame('rejected', $s->reduced_entry_status);
        $this->assertEquals(250.0, (float) $s->reduced_entry_amount, 'Values must be retained after rejection.');
    }

    public function test_cannot_re_decide_already_decided_field(): void
    {
        $admin = $this->admin();
        $s = $this->student([
            'special_condition_options' => ['duolingo'],
            'special_condition_status'  => 'approved',
        ]);

        $this->actingAs($admin)->patch(
            route('admin.applications.special-approvals.update', $s),
            ['field' => 'special_condition', 'decision' => 'rejected']
        )->assertSessionHasErrors();

        $s->refresh();
        $this->assertSame('approved', $s->special_condition_status);
    }

    public function test_application_role_cannot_approve(): void
    {
        if (\DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped("'application' role is only valid on the MySQL schema.");
        }

        $appUser = User::factory()->create(['role' => 'application', 'active' => true]);
        $s = $this->student([
            'special_condition_options' => ['duolingo'],
            'special_condition_status'  => 'pending',
        ]);

        $this->actingAs($appUser)->patch(
            route('admin.applications.special-approvals.update', $s),
            ['field' => 'special_condition', 'decision' => 'approved']
        )->assertForbidden();

        $s->refresh();
        $this->assertSame('pending', $s->special_condition_status);
    }

    public function test_queue_index_lists_only_pending_by_default(): void
    {
        $admin = $this->admin();
        $pending = $this->student([
            'name'                     => 'Pending Pat',
            'special_condition_status' => 'pending',
        ]);
        $approved = $this->student([
            'name'                     => 'Approved Amy',
            'email'                    => 'amy@test.invalid',
            'special_condition_status' => 'approved',
        ]);

        $r = $this->actingAs($admin)->get(route('admin.applications.special-approvals.index'));
        $r->assertOk();
        $r->assertSee('Pending Pat');
        $r->assertDontSee('Approved Amy');
    }
}
