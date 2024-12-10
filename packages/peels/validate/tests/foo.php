<?php

public function testIsValidJsonTrue(): void
{
    $ruleRunner = new Json($this->jsonText, '', [], $this->validate);

    $this->assertNull($ruleRunner->isValidJson());
}

public function testIsValidJsonFalse(): void
{
    $input = 'foobar';

    $ruleRunner = new Json($input, '', [], $this->validate);

    $this->expectException(RuleFailed::class);

    $ruleRunner->isValidJson();
}

public function testValidateJson(): void
{
    $ruleRunner = new Json($this->jsonText, 'isString(people.*.name)', [], $this->validate);

    $this->assertNull($ruleRunner->validateJson());

    $ruleRunner = new Json($this->jsonText, 'isInteger(people.*.age)', [], $this->validate);

    $this->assertNull($ruleRunner->validateJson());
}

public function testIsString(): void
{
    $ruleRunner = new Json($this->jsonText, 'isString(address.state)', [], $this->validate);

    $this->assertNull($ruleRunner->validateJson());

    $ruleRunner = new Json($this->jsonText, 'isString(address.city)', [], $this->validate);

    $this->assertNull($ruleRunner->validateJson());
}

public function testIsInt(): void
{
    $ruleRunner = new Json($this->jsonText, 'isString(address.zip)', [], $this->validate);

    $this->assertNull($ruleRunner->validateJson());
}

public function testRuleFailed1(): void
{
    $ruleRunner = new Json($this->jsonText, 'isBool(people.*.age)', [], $this->validate);

    $this->expectException(ValidationFailed::class);

    $this->assertNull($ruleRunner->validateJson());
}

public function testRuleFailed2(): void
{
    $ruleRunner = new Json($this->jsonText, 'isBool(people.*.pet)', [], $this->validate);

    $this->expectException(ValidationFailed::class);

    $this->assertNull($ruleRunner->validateJson());
}

public function testRuleFailed3(): void
{
    $ruleRunner = new Json($this->jsonText, 'isBool(people.*.age.1)', [], $this->validate);

    $this->expectException(ValidationFailed::class);

    $this->assertNull($ruleRunner->validateJson());
}

public function testIsBool(): void
{
    $ruleRunner = new Json($this->jsonText, 'isBool(people.*.male)', [], $this->validate);

    $this->assertNull($ruleRunner->validateJson());
}

public function testIsArray(): void
{
    $ruleRunner = new Json($this->jsonText, 'isArray(people.*)', [], $this->validate);

    $this->assertNull($ruleRunner->validateJson());
}

public function testValidateJsonText(): void
{
    $ruleRunner = new Json($this->jsonText, 'isString(people.1.name)', [], $this->validate);

    $this->assertNull($ruleRunner->validateJson());
}






    /**
     * Single value filter
     * $filter->input($foobar,'readable');
     *
     * This WILL throw an error on fail
     * but these should be "filters" which do not return errors
     * and not validation rules which do return (or throw exceptions) errors
     */
public function _value(mixed $value, string|array $rules): bool
{
    // throws exception on fail
    if (is_array($rules)) {
        foreach ($rules as $rule) {
            $this->runRule($value, $rule);
        }
    } else {
        $this->runRule($value, $rules);
    }

    return true;
}
