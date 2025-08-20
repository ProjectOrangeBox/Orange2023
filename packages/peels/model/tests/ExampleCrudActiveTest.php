<?php

declare(strict_types=1);

use peels\validate\Validate;

final class ExampleCrudActiveTest extends unitTestHelper
{
    protected $instance;
    protected $pdo;

    protected function setUp(): void
    {
        // connect to test db
        $this->pdo = new PDO('mysql:host=' . $_ENV['phpunit']['host'] . ';dbname=' . $_ENV['phpunit']['database'], $_ENV['phpunit']['username'], $_ENV['phpunit']['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        // setup table(s) and data
        $this->pdo->query(file_get_contents(__DIR__ . '/support/setup.sql'));

        require_once __DIR__ . '/support/ExampleCrudActive.php';

        // instance of Model Abstract Class
        $this->instance = ExampleCrudActive::getInstance([], $this->pdo, Validate::getInstance([]));
    }

    protected function tearDown(): void
    {
        $this->pdo->query(file_get_contents(__DIR__ . '/support/teardown.sql'));
    }

    public function testDeactiveUser(): void
    {
        $this->assertEquals(3, $this->instance->create('Orange', 'John', 32));
        $this->assertTrue($this->instance->delete(3));
        $this->assertFalse($this->instance->read(3));

        $this->assertTrue($this->instance->activate(3));

        $match = array(
            'id' => 3,
            'first_name' => 'John',
            'last_name' => 'Orange',
            'age' => 32,
            'is_active' => 1,
        );

        $this->assertEquals($match, $this->instance->read(3));
        $this->assertTrue($this->instance->deactivate(3));

        $this->assertFalse($this->instance->read(3));
    }

    public function testDeactiveReadAllUser(): void
    {
        $this->assertEquals(3, $this->instance->create('Orange', 'John', 32));

        $this->assertTrue($this->instance->delete(3));

        $match = array(
            0 =>
            array(
                'id' => 1,
                'first_name' => 'Johnny',
                'last_name' => 'Appleseed',
                'age' => 28,
                'is_active' => 1,
            ),
            1 =>
            array(
                'id' => 2,
                'first_name' => 'Jenny',
                'last_name' => 'Appleseed',
                'age' => 31,
                'is_active' => 1,
            ),
        );


        $this->assertEquals($match, $this->instance->readAll());
        $this->assertTrue($this->instance->activate(3));

        $match = array(
            0 =>
            array(
                'id' => 1,
                'first_name' => 'Johnny',
                'last_name' => 'Appleseed',
                'age' => 28,
                'is_active' => 1,
            ),
            1 =>
            array(
                'id' => 2,
                'first_name' => 'Jenny',
                'last_name' => 'Appleseed',
                'age' => 31,
                'is_active' => 1,
            ),
            2 =>
            array(
                'id' => 3,
                'first_name' => 'John',
                'last_name' => 'Orange',
                'age' => 32,
                'is_active' => 1,
            ),
        );

        $this->assertEquals($match, $this->instance->readAll());
    }
}
