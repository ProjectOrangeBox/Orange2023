<?php

declare(strict_types=1);

use orange\framework\Input;
use peels\validate\Validate;
use peels\validate\ValidJson;
use peels\validate\exceptions\ValidationFailed;

final class jsonTest extends \unitTestHelper
{
    private $validJson;

    private $jsonText = '{
        "address": {
            "state": "pa",
            "city": "west chester",
            "zip": 19443
        },
        "people": [
            {
                "name": "John",
                "age": 21,
                "male": true
            },
            {
                "name": "Jenny",
                "age": 22,
                "male": false
            },
            {
                "name": "Jake",
                "age": 23,
                "male": true
            }
        ]
    }';

    protected function setUp(): void
    {
        $this->validJson = ValidJson::getInstance(Validate::getInstance([]), Input::getInstance([]));
    }

    public function testValidateJson(): void
    {
        $this->assertTrue($this->validJson->value($this->jsonText, 'isString(people.*.name)'));

        $this->expectException(ValidationFailed::class);

        $this->assertNull($this->validJson->value($this->jsonText, 'isBool(people.*.name)'));
    }

    public function testValidateJsonCounts(): void
    {
        $this->assertTrue($this->validJson->value($this->jsonText, [
            'isCount(people.*),3',
            'isCountLessThan(people.*),6',
            'isCountGreaterThan(people.*),1'
        ]));
    }
}
