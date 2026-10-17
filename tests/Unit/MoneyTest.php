<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_converts_major_strings_to_minor_units_without_floats(): void
    {
        $this->assertSame(1050, Money::toMinor('10.5', 'NGN'));
        $this->assertSame(1999, Money::toMinor('19.99', 'USD'));
        $this->assertSame(500000, Money::toMinor('5000', 'NGN'));
        $this->assertSame(1000, Money::toMinor('1000', 'JPY'));
        $this->assertSame(1001, Money::toMinor('10.019', 'USD')); // truncates extra precision
    }

    public function test_formats_amounts(): void
    {
        $this->assertSame('₦5,000.00', Money::format(500000, 'NGN'));
        $this->assertSame('$19.99', Money::format(1999, 'USD'));
    }
}
