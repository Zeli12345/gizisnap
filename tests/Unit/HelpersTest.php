<?php

namespace Tests\Unit;

use Tests\TestCase;

class HelpersTest extends TestCase
{
    public function test_gizi_num_memakai_format_indonesia(): void
    {
        $this->assertSame('1.250', gizi_num(1250));
        $this->assertSame('0', gizi_num(null));
        $this->assertSame('12,5', gizi_num(12.5, 1));
        $this->assertSame('12', gizi_num(12.0, 1));
        $this->assertSame('1.234,5', gizi_num(1234.5, 1));
    }
}
