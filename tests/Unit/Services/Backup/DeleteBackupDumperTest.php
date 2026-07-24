<?php

namespace Tests\Unit\Services\Backup;

use App\Services\Backup\DeleteBackupDumper;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class DeleteBackupDumperTest extends TestCase
{
    #[Test]
    public function it_extracts_where_columns_for_equality_and_in_clauses(): void
    {
        $dumper = new DeleteBackupDumper();
        $method = new ReflectionMethod(DeleteBackupDumper::class, 'extractWhereColumn');
        $method->setAccessible(true);

        $this->assertSame('id', $method->invoke($dumper, 'id=39'));
        $this->assertSame('temp_application_id', $method->invoke($dumper, 'temp_application_id=39'));
        $this->assertSame('group_loans_id', $method->invoke($dumper, 'group_loans_id IN (1,2,3)'));
        $this->assertSame('loan_phase_id', $method->invoke($dumper, 'loan_phase_id=153'));
        $this->assertNull($method->invoke($dumper, '1=1'));
    }
}
