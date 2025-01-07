<?php

declare(strict_types=1);

use peels\validate\Validate;

final class ExampleModelTest extends unitTestHelper
{
    protected $instance;
    protected $pdo;

    protected function setUp(): void
    {
        // connect to test db
        $this->pdo = new PDO('mysql:host=' . $_ENV['phpunit']['host'] . ';dbname=' . $_ENV['phpunit']['database'], $_ENV['phpunit']['username'], $_ENV['phpunit']['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        // setup table(s) and data
        $this->pdo->query(file_get_contents(__DIR__ . '/support/setup.sql'));

        require_once __DIR__ . '/support/ExampleModel.php';

        // instance of Model Abstract Class
        $this->instance = new ExampleModel([], $this->pdo, new Validate([]));
    }

    protected function tearDown(): void
    {
        $this->pdo->query(file_get_contents(__DIR__ . '/support/teardown.sql'));
    }

    /* Tests */

    /* public */

    public function testGetUser(): void
    {
        $this->assertEquals([
            'id' => 1,
            'first_name' => 'Johnny',
            'last_name' => 'Appleseed',
            'age' => 28,
        ], $this->instance->getUser(1));
    }

    public function testGetDetailUser(): void
    {
        $this->assertEquals([
            'fname' => 'Jenny',
            'lname' => 'Appleseed',
            'childern' => [
                0 => ['child_name' => 'Sally', 'id' => 2],
                1 => ['child_name' => 'Chuck', 'id' => 3],
            ]
        ], $this->instance->getUserDetailed(2));
    }

    public function testGetDetailUserNone(): void
    {
        $this->assertEquals([], $this->instance->getUserDetailed(999));
    }
}
