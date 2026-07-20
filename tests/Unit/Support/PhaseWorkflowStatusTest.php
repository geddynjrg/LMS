<?php

namespace Tests\Unit\Support;

use App\Models\LoanPhase;
use App\Models\SecretariatApproval;
use App\Services\Loan\SecretariatApprovalService;
use App\Support\PhaseWorkflowStatus;
use Mockery;
use Tests\TestCase;

class PhaseWorkflowStatusTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_approved_phase_without_secretariat_approvals_awaits_head_of_secretariat(): void
    {
        $phase = new LoanPhase(['status' => 'approved']);
        $phase->setRelation('secretariatApprovals', collect());

        $service = Mockery::mock(SecretariatApprovalService::class);
        $service->shouldReceive('hasApproval')
            ->with($phase, SecretariatApproval::TYPE_TRAINING_AUTHORIZATION)
            ->andReturn(false);
        $service->shouldReceive('nextPendingApprovalType')
            ->with($phase)
            ->andReturn(SecretariatApproval::TYPE_POST_HQ_REVIEW);

        $this->instance(SecretariatApprovalService::class, $service);

        $display = PhaseWorkflowStatus::forPhase($phase);

        $this->assertSame('Awaiting Head of Secretariat Approval', $display['label']);
    }

    public function test_approved_phase_with_post_hq_review_shows_training_authorization_pending(): void
    {
        $phase = new LoanPhase(['status' => 'approved']);
        $phase->setRelation('secretariatApprovals', collect());

        $service = Mockery::mock(SecretariatApprovalService::class);
        $service->shouldReceive('hasApproval')
            ->with($phase, SecretariatApproval::TYPE_TRAINING_AUTHORIZATION)
            ->andReturn(false);
        $service->shouldReceive('nextPendingApprovalType')
            ->with($phase)
            ->andReturn(SecretariatApproval::TYPE_TRAINING_AUTHORIZATION);

        $this->instance(SecretariatApprovalService::class, $service);

        $display = PhaseWorkflowStatus::forPhase($phase);

        $this->assertSame('Awaiting Training Authorization', $display['label']);
        $this->assertSame('awaiting_secretariat', $display['value']);
    }

    public function test_approved_phase_with_training_authorization_shows_schedule_training(): void
    {
        $phase = new LoanPhase(['status' => 'approved']);
        $phase->setRelation('secretariatApprovals', collect());

        $service = Mockery::mock(SecretariatApprovalService::class);
        $service->shouldReceive('hasApproval')
            ->with($phase, SecretariatApproval::TYPE_TRAINING_AUTHORIZATION)
            ->andReturn(true);

        $this->instance(SecretariatApprovalService::class, $service);

        $display = PhaseWorkflowStatus::forPhase($phase);

        $this->assertSame('Schedule Training', $display['label']);
        $this->assertSame('schedule_training', $display['value']);
    }
}
