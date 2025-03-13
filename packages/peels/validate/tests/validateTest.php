<?php

declare(strict_types=1);

use peels\validate\Validate;
use peels\validate\exceptions\ValidationFailed;

final class validateTest extends \unitTestHelper
{
    private $validate;

    protected function setUp(): void
    {
        $this->validate = Validate::getInstance([
            'throwExceptionOnFailure' => true,
            'notationDelimiter' => ''
        ]);
    }

    public function testValidateDateInvalid(): void
    {
        $this->expectException(ValidationFailed::class);

        $this->validate->input('123', 'ConvertDate|Length[6]');
    }

    public function testValidateConvertDate(): void
    {
        $this->assertEquals('1942-12-18 23:42:00', $this->validate->input('December 18th 1942 11:42pm', 'ConvertDate')->value());
    }

    public function testValidateNotInteger(): void
    {
        $this->expectException(ValidationFailed::class);

        $this->validate->input('abc', 'isInteger');
    }

    public function testValidateSet(): void
    {
        $values = [
            'name' => 'Jane Doe',
            'age' => 27,
            'food' => 'pizza'
        ];

        $rules = [
            'name' => 'length[4]|isString',
            'age' => 'isGreaterThan[18]|isLessThan[100]|isInteger',
            'food' => 'isString|isOneOf[pizza,burger,hot dog,ice cream]',
        ];

        $this->assertEquals([
            'name' => 'Jane',
            'age' => 27,
            'food' => 'pizza',
        ], $this->validate->input($values, $rules)->values());
    }

    public function testValidateSetDotNotation(): void
    {
        $values = [
            'name' => [
                'first' => 'Jane',
                'last' => 'Doe'
            ],
            'age' => 27,
            'food' => 'pizza'
        ];

        $rules = [
            'name.first' => 'length[2]|isString',
            'name.last' => 'length[2]|isString',
            'age' => 'isGreaterThan[18]|isLessThan[100]|isInteger',
            'food' => 'isString|isOneOf[pizza,burger,hot dog,ice cream]',
        ];

        $this->validate->changeNotationDelimiter('.');

        $this->assertEquals([
            'name' => [
                'first' => 'Ja',
                'last' => 'Do'
            ],
            'age' => 27,
            'food' => 'pizza',
        ], $this->validate->input($values, $rules)->values());
    }

    public function testValidateSetError(): void
    {
        $values = [
            'name' => 456,
            'age' => 2,
            'food' => 'cat'
        ];

        $rules = [
            'name' => 'length[4]|isString',
            'age' => 'isGreaterThan[18]|isLessThan[100]|isInteger',
            'food' => 'isString|isOneOf[pizza,burger,hot dog,ice cream]',
        ];

        $this->validate->throwExceptionOnFailure(false)->input($values, $rules);

        $this->assertTrue($this->validate->hasErrors());
        $this->assertEquals(['age is not greater than 18.', 'food is not one of pizza, burger, hot dog, ice cream.'], $this->validate->errors());
    }
}
