<?php

declare(strict_types=1);

use peels\validate\Validate;

final class anotherTest extends \unitTestHelper
{
    private $validate;

    protected function setUp(): void
    {
        $this->validate = Validate::getInstance([
            'throwExceptionOnFailure' => true,
        ]);
    }

    public function testOne(): void
    {
        $this->assertEquals(123, $this->validate->input('123', 'toInteger|castInteger|isGreaterThan[100]|isLessThan[999]|isInteger')->value());
    }

    public function testTwo(): void
    {
        $password = 'DefaultPassword#1';

        $hash = $this->validate->input($password, 'toPasswordHash')->value();

        $this->assertEquals($password, $this->validate->input($password, 'passwordVerify[' . $hash . ']')->value());
    }
}
