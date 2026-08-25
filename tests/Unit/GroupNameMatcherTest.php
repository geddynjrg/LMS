<?php

namespace Tests\Unit;

use App\Support\GroupNameMatcher;
use PHPUnit\Framework\TestCase;

class GroupNameMatcherTest extends TestCase
{
    public function test_abbreviated_names_match(): void
    {
        $this->assertTrue(GroupNameMatcher::isSameGroup('Faith Self Help Group', 'Faith S.H.G'));
        $this->assertTrue(GroupNameMatcher::isSameGroup('Mama Teresa Women Group', 'Mama Teresa W. Group'));
        $this->assertTrue(GroupNameMatcher::isSameGroup('Roho Safi W G', 'Roho Safi Women Group'));
        $this->assertTrue(GroupNameMatcher::isSameGroup('Beraca Women Group', 'Bereca'));
        $this->assertTrue(GroupNameMatcher::isSameGroup(
            'Nakuru-Nyamokia Persons With Disability Self Group',
            'Nakuru Nyamokia Disabled'
        ));
    }

    public function test_distinct_groups_do_not_match(): void
    {
        $this->assertFalse(GroupNameMatcher::isSameGroup('Amazing Lalwet W.G', 'Blessing Hand Self Help Group'));
        $this->assertFalse(GroupNameMatcher::isSameGroup('Gift Upendo Women Group', 'Elite Youth Group'));
    }

    public function test_category_aliases(): void
    {
        $this->assertSame('pwd', GroupNameMatcher::normalizeCategory('P.W.D'));
        $this->assertSame('pwd', GroupNameMatcher::normalizeCategory('PWD'));
        $this->assertSame('women', GroupNameMatcher::normalizeCategory('Women'));
    }
}
