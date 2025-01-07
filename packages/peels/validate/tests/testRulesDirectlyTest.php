<?php

declare(strict_types=1);

use peels\validate\Validate;
use peels\validate\rules\Rules;
use peels\validate\exceptions\RuleFailed;

final class testRulesDirectlyTest extends \unitTestHelper
{
    private $validate;

    protected function setUp(): void
    {
        $this->validate = Validate::getInstance([]);
    }

    public function testEmpty(): void
    {
        $value = '';

        $this->expectException(RuleFailed::class);

        (new Rules($value, '', [], $this->validate))->ConvertDate();
    }

    public function testMySQL(): void
    {
        $value = 'Jan 21st 1901';

        (new Rules($value, '', [], $this->validate))->ConvertDate('Y-m-d H:i:s');

        $this->assertEquals('1901-01-21 00:00:00', $value);
    }

    public function testMySQL2(): void
    {
        $value = 'Jan 21st 1901 4:45pm';

        (new Rules($value, '', [], $this->validate))->ConvertDate('Y-m-d H:i:s');

        $this->assertEquals('1901-01-21 16:45:00', $value);
    }

    public function testLong(): void
    {
        $value = 'Jan 21st 1901 4:45pm';

        (new Rules($value, 'l jS \of F Y h:iA', [], $this->validate))->ConvertDate();

        $this->assertEquals('Monday 21st of January 1901 04:45PM', $value);
    }

    public function testInvalidAsArray(): void
    {
        $value = [];

        $this->expectException(RuleFailed::class);

        (new Rules($value, '', [], $this->validate))->ConvertDate('Y-m-d H:i:s');
    }

    public function testInvalidAsClass(): void
    {
        $value = new stdClass();

        $this->expectException(RuleFailed::class);

        (new Rules($value, '', [], $this->validate))->ConvertDate('Y-m-d H:i:s');
    }
}
