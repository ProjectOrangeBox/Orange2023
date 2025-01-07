<?php

declare(strict_types=1);

use peels\validate\Validate;

final class ExampleCrudTest extends unitTestHelper
{
    protected $instance;
    protected $pdo;

    protected function setUp(): void
    {
        // connect to test db
        $this->pdo = new PDO('mysql:host=' . $_ENV['phpunit']['host'] . ';dbname=' . $_ENV['phpunit']['database'], $_ENV['phpunit']['username'], $_ENV['phpunit']['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        // setup table(s) and data
        $this->pdo->query(file_get_contents(__DIR__ . '/support/setup.sql'));

        require_once __DIR__ . '/support/ExampleCrud.php';

        // instance of Model Abstract Class
        $this->instance = new ExampleCrud([], $this->pdo, new Validate([]));
    }

    protected function tearDown(): void
    {
        $this->pdo->query(file_get_contents(__DIR__ . '/support/teardown.sql'));
    }

    public function testCreateUser(): void
    {
        $this->assertEquals(3, $this->instance->create('Orange', 'John', 32));
    }

    public function testUpdateUser(): void
    {
        $this->assertEquals(3, $this->instance->create('Orange', 'John', 32));
        $this->assertTrue($this->instance->update(['age' => 48], 3));
    }

    public function testReadUser(): void
    {
        $this->assertEquals(3, $this->instance->create('Orange', 'John', 32));

        $match = array(
            'id' => 3,
            'first_name' => 'John',
            'last_name' => 'Orange',
            'age' => 32,
        );

        $this->assertEquals($match, $this->instance->read(3));
    }

    public function testReadAllUsers(): void
    {
        $this->assertEquals(3, $this->instance->create('Orange', 'John', 32));

        $match = array(
            0 =>
            array(
                'id' => 1,
                'first_name' => 'Johnny',
                'last_name' => 'Appleseed',
                'age' => 28,
            ),
            1 =>
            array(
                'id' => 2,
                'first_name' => 'Jenny',
                'last_name' => 'Appleseed',
                'age' => 31,
            ),
            2 =>
            array(
                'id' => 3,
                'first_name' => 'John',
                'last_name' => 'Orange',
                'age' => 32,
            ),
        );

        $this->assertEquals($match, $this->instance->readAll());
    }

    public function testDeleteUser(): void
    {
        $this->assertEquals(3, $this->instance->create('Orange', 'John', 32));
        $this->assertTrue($this->instance->delete(3));
    }
}
