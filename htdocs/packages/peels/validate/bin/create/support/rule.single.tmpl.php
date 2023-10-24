    public function test%%KEY%%AgainstRule%%BASENAME%%(): void
    {        
        $value = %%VALUE1%%;

        // if we throw an Exception it is not valid
        $this->expectException(RuleFailed::class);

        (new Rules($value, [], new Validate([])))->%%BASENAME%%();

        $this->assertEquals('x',$value);
    }

