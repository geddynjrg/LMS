<?php

namespace Tests\Unit\Support;

use App\Models\LoanPhase;
use App\Models\User;
use App\Support\PhaseCodeListAccess;
use Mockery;
use Tests\TestCase;

class PhaseCodeListAccessTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_accountant_can_view_submitted_to_hq_phase_code_list(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('hasAnyRole')->with(['super-admin', 'hq_officer', 'admin'])->andReturn(false);
        $user->shouldReceive('hasAnyRole')->with(['super-admin', 'head_of_secretariat'])->andReturn(false);
        $user->shouldReceive('hasAnyRole')->with(['super-admin', 'accountant'])->andReturn(true);
        $user->shouldReceive('hasRole')->with('super-admin')->andReturn(false);

        $phase = new LoanPhase(['status' => 'submitted_to_hq']);

        $this->assertTrue(PhaseCodeListAccess::phaseCanBeViewedBy($user, $phase));
    }

    public function test_accountant_cannot_view_non_hq_submitted_phase_code_list(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('hasAnyRole')->with(['super-admin', 'hq_officer', 'admin'])->andReturn(false);
        $user->shouldReceive('hasAnyRole')->with(['super-admin', 'head_of_secretariat'])->andReturn(false);
        $user->shouldReceive('hasAnyRole')->with(['super-admin', 'accountant'])->andReturn(true);
        $user->shouldReceive('hasRole')->with('super-admin')->andReturn(false);

        $phase = new LoanPhase(['status' => 'approved']);

        $this->assertFalse(PhaseCodeListAccess::phaseCanBeViewedBy($user, $phase));
    }
}
