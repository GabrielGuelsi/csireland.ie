<?php

namespace Tests\Feature;

use App\Models\SalesConsultant;
use App\Models\ServiceRequest;
use App\Models\Student;
use App\Models\StudentStageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancellationCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_a_cancellation_flips_student_status_and_logs_the_transition(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $consultant = SalesConsultant::create(['name' => 'Test Consultant']);

        $student = Student::create([
            'name'                => 'Alice Test',
            'email'               => 'alice@test.invalid',
            'product_type'        => 'higher_education',
            'sales_consultant_id' => $consultant->id,
            'status'              => 'waiting_student_response',
            'application_status'  => 'waiting_cs',
            'form_submitted_at'   => now(),
        ]);

        $sr = ServiceRequest::create([
            'type'         => 'cancellation',
            'status'       => 'in_review',
            'student_id'   => $student->id,
            'requested_by' => $admin->id,
            'data'         => ['reason' => 'Student withdrew', 'cancellation_justified' => true],
        ]);

        $response = $this->actingAs($admin)->patch(
            route('admin.applications.service-requests.update', $sr),
            ['status' => 'completed', 'notes' => null]
        );

        $response->assertRedirect();

        $student->refresh();
        $this->assertSame('cancelled', $student->status);
        $this->assertSame('cancelled', $student->application_status);
        $this->assertSame('Student withdrew', $student->cancellation_reason);
        $this->assertTrue((bool) $student->cancellation_justified);

        $this->assertDatabaseHas('student_stage_logs', [
            'student_id' => $student->id,
            'from_stage' => 'waiting_student_response',
            'to_stage'   => 'cancelled',
            'changed_by' => $admin->id,
        ]);

        $sr->refresh();
        $this->assertSame('completed', $sr->status);
    }

    public function test_completing_a_second_cancellation_does_not_double_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $consultant = SalesConsultant::create(['name' => 'Test Consultant 2']);

        $student = Student::create([
            'name'                => 'Bob Test',
            'email'               => 'bob@test.invalid',
            'product_type'        => 'first_visa',
            'sales_consultant_id' => $consultant->id,
            'status'              => 'cancelled',
            'form_submitted_at'   => now(),
        ]);

        $sr = ServiceRequest::create([
            'type'         => 'cancellation',
            'status'       => 'in_review',
            'student_id'   => $student->id,
            'requested_by' => $admin->id,
            'data'         => ['reason' => 'Duplicate'],
        ]);

        $this->actingAs($admin)->patch(
            route('admin.applications.service-requests.update', $sr),
            ['status' => 'completed']
        );

        $this->assertSame(
            0,
            StudentStageLog::where('student_id', $student->id)->count(),
            'No stage log should be written when student is already cancelled.'
        );
    }

    public function test_refund_rejection_is_unaffected_by_cancellation_rename(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $consultant = SalesConsultant::create(['name' => 'Test Consultant 3']);

        $student = Student::create([
            'name'                => 'Carol Test',
            'email'               => 'carol@test.invalid',
            'product_type'        => 'higher_education',
            'sales_consultant_id' => $consultant->id,
            'status'              => 'waiting_payment',
            'application_status'  => 'applied',
            'form_submitted_at'   => now(),
        ]);

        $sr = ServiceRequest::create([
            'type'         => 'refund',
            'status'       => 'in_review',
            'student_id'   => $student->id,
            'requested_by' => $admin->id,
            'data'         => ['reason' => 'Not eligible', 'refund_amount' => 0],
        ]);

        $this->actingAs($admin)->patch(
            route('admin.applications.service-requests.update', $sr),
            ['status' => 'rejected']
        );

        $student->refresh();
        $this->assertSame('waiting_payment', $student->status);
        $this->assertSame('applied', $student->application_status);
    }
}
