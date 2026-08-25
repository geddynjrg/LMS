<?php

namespace Tests\Unit;

use App\Support\GfsCode;
use PHPUnit\Framework\TestCase;

class GfsCodeTest extends TestCase
{
    public function test_normalize_accepts_dashed_format(): void
    {
        $code = '4-006-026-4110202-201-001';

        $this->assertSame($code, GfsCode::normalize($code));
    }

    public function test_normalize_accepts_digits_only(): void
    {
        $this->assertSame(
            '4-006-026-4110202-201-001',
            GfsCode::normalize('40060264110202201001')
        );
    }

    public function test_normalize_accepts_mixed_dashes_and_spaces(): void
    {
        $this->assertSame(
            '4-006-026-4110202-201-001',
            GfsCode::normalize('4-006-026-4110202-201-001')
        );
    }

    public function test_normalize_accepts_twenty_one_digit_suffix(): void
    {
        $this->assertSame(
            '4-006-026-4110202-201-0001',
            GfsCode::normalize('400602641102022010001')
        );
    }

    public function test_normalize_accepts_twenty_two_digit_suffix(): void
    {
        $this->assertSame(
            '4-006-026-4110202-201-00001',
            GfsCode::normalize('4006026411020220100001')
        );
        $this->assertSame(
            '4-006-026-4110202-201-00001',
            GfsCode::normalize('4-006-026-4110202-201-00001')
        );
    }

    public function test_normalize_strips_trailing_dashes(): void
    {
        $this->assertSame(
            '4-006-026-4110202-201-001',
            GfsCode::normalize('4-006-026-4110202-201-001---')
        );
        $this->assertSame(
            '4-006-026-4110202-201-001',
            GfsCode::normalize('40060264110202201001---')
        );
        $this->assertSame(
            '4-006-026-4110202-201-00001',
            GfsCode::normalize('4-006-026-4110202-201-00001-')
        );
    }

    public function test_normalize_rejects_invalid_length(): void
    {
        $this->assertNull(GfsCode::normalize('4-006-026-4110202-201'));
        $this->assertNull(GfsCode::normalize('12345'));
    }

    public function test_group_suffix(): void
    {
        $this->assertSame('001', GfsCode::groupSuffix('4-006-026-4110202-201-001'));
        $this->assertSame('0001', GfsCode::groupSuffix('4-006-026-4110202-201-0001'));
        $this->assertSame('00001', GfsCode::groupSuffix('4-006-026-4110202-201-00001'));
    }
}
