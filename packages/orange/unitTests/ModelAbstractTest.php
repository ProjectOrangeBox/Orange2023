<?php

declare(strict_types=1);

use dmyers\orange\ModelAbstract;
use PHPUnit\Framework\TestCase;

final class ModelAbstractTest extends TestCase
{
    private $instance;
    private $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('mysql:host=127.0.0.1;dbname=phpunit', 'phpunit', 'phpunit', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        $this->pdo->query(file_get_contents(__DIR__.'/support/test.sql'));

        $this->instance = $this->getMockForAbstractClass(ModelAbstract::class, [$this->pdo,[]]);
    }

    protected function tearDown(): void
    {
        $this->pdo->query('DROP TABLE IF EXISTS `main`');
    }

    /* Tests */

	/* public */

    public function test__construct(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testHasError(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function testErrors(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }

    public function test__debugInfo(): void
    {
        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }


	/* protected */

    public function test_reset(): void
    {
        $reflectionMethod = new ReflectionMethod($this->instance, '_reset');
        
        $output = $reflectionMethod->invokeArgs($this->instance,[]);

        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }
    public function test_getById(): void
    {
        $reflectionMethod = new ReflectionMethod($this->instance, '_getById');
        
        $output = $reflectionMethod->invokeArgs($this->instance,[]);

        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }
    public function test_getColumnById(): void
    {
        $reflectionMethod = new ReflectionMethod($this->instance, '_getColumnById');
        
        $output = $reflectionMethod->invokeArgs($this->instance,[]);

        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }
    public function test_getValueById(): void
    {
        $reflectionMethod = new ReflectionMethod($this->instance, '_getValueById');
        
        $output = $reflectionMethod->invokeArgs($this->instance,[]);

        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }
    public function test_row(): void
    {
        $reflectionMethod = new ReflectionMethod($this->instance, '_row');
        
        $output = $reflectionMethod->invokeArgs($this->instance,[]);

        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }
    public function test_rows(): void
    {
        $reflectionMethod = new ReflectionMethod($this->instance, '_rows');
        
        $output = $reflectionMethod->invokeArgs($this->instance,[]);

        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }
    public function test_insert(): void
    {
        $reflectionMethod = new ReflectionMethod($this->instance, '_insert');
        
        $output = $reflectionMethod->invokeArgs($this->instance,[]);

        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }
    public function test_lastInsertId(): void
    {
        $reflectionMethod = new ReflectionMethod($this->instance, '_lastInsertId');
        
        $output = $reflectionMethod->invokeArgs($this->instance,[]);

        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }
    public function test_updateById(): void
    {
        $reflectionMethod = new ReflectionMethod($this->instance, '_updateById');
        
        $output = $reflectionMethod->invokeArgs($this->instance,[]);

        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }
    public function test_update(): void
    {
        $reflectionMethod = new ReflectionMethod($this->instance, '_update');
        
        $output = $reflectionMethod->invokeArgs($this->instance,[]);

        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }
    public function test_lastAffectedRows(): void
    {
        $reflectionMethod = new ReflectionMethod($this->instance, '_lastAffectedRows');
        
        $output = $reflectionMethod->invokeArgs($this->instance,[]);

        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }
    public function test_delete(): void
    {
        $reflectionMethod = new ReflectionMethod($this->instance, '_delete');
        
        $output = $reflectionMethod->invokeArgs($this->instance,[]);

        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }
    public function test_deleteById(): void
    {
        $reflectionMethod = new ReflectionMethod($this->instance, '_deleteById');
        
        $output = $reflectionMethod->invokeArgs($this->instance,[]);

        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }
    public function test_run(): void
    {
        $reflectionMethod = new ReflectionMethod($this->instance, '_run');
        
        $output = $reflectionMethod->invokeArgs($this->instance,[]);

        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }
    public function test_captureError(): void
    {
        $reflectionMethod = new ReflectionMethod($this->instance, '_captureError');
        
        $output = $reflectionMethod->invokeArgs($this->instance,[]);

        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }
    public function test_setFetchMode(): void
    {
        $reflectionMethod = new ReflectionMethod($this->instance, '_setFetchMode');
        
        $output = $reflectionMethod->invokeArgs($this->instance,[]);

        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }
    public function test_columns(): void
    {
        $reflectionMethod = new ReflectionMethod($this->instance, '_columns');
        
        $this->assertEquals('`first_name`,`last_name`,`table`.`name`',$reflectionMethod->invokeArgs($this->instance,[['first_name','last_name','table.name']]));
    }
    public function test_table(): void
    {
        $reflectionMethod = new ReflectionMethod($this->instance, '_table');
        
        $output = $reflectionMethod->invokeArgs($this->instance,[]);

        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }
    public function test_primary(): void
    {
        $reflectionMethod = new ReflectionMethod($this->instance, '_primary');
        
        $output = $reflectionMethod->invokeArgs($this->instance,[]);

        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }
    public function test_escapeTableColumn(): void
    {
        $reflectionMethod = new ReflectionMethod($this->instance, '_escapeTableColumn');
        
        $output = $reflectionMethod->invokeArgs($this->instance,[]);

        fwrite(STDOUT, __METHOD__ . "\n");
        
        $this->assertTrue(true);
    }
}
