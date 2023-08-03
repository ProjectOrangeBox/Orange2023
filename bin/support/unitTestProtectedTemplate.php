    public function test{{method}}(): void
    {
        $reflectionMethod = new ReflectionMethod($this->instance, '{{method}}');
        
        $output = $reflectionMethod->invokeArgs($this->instance,[]);

        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }
