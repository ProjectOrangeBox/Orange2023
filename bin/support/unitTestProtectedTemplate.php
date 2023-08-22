    public function test{{method}}(): void
    {
        //$this->assertEquals(true, $this->callMethod('{{method}}'));

        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

