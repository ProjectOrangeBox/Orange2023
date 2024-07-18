<?php

declare(strict_types=1);

use peels\model\Model;
use peels\validate\Validate;

final class ModelParentTest extends unitTestHelper
{
    protected $instance;
    protected $pdo;

    protected function setUp(): void
    {
        // connect to test db
        $this->pdo = new PDO('mysql:host=' . $_ENV['phpunit']['host'] . ';dbname=' . $_ENV['phpunit']['database'], $_ENV['phpunit']['username'], $_ENV['phpunit']['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        // setup table(s) and data
        $this->pdo->query(file_get_contents(__DIR__ . '/support/test.sql'));

        $config = [
            'tablename' => 'main',
            'primaryColumn' => 'id',
            'throwException' => false,
            'defaultFetchType' => PDO::FETCH_ASSOC,
            'fetchClass' => '',
            'errorFormat' => '[%1$s] %2$s',
        ];

        // instance of Model Abstract Class
        $this->instance = $this->getMockForAbstractClass(Model::class, [$config, $this->pdo, new Validate([])]);
    }

    protected function tearDown(): void
    {
        $this->pdo->query('DROP TABLE IF EXISTS `main`');
        $this->pdo->query('DROP TABLE IF EXISTS `join`');
    }

    /* Protected Method Tests */

    public function testGetById(): void
    {
        $this->assertEquals([
            'id' => 1,
            'first_name' => 'Johnny',
            'last_name' => 'Appleseed',
            'age' => 28
        ], $this->callMethod('GetById', [1]));

        $this->assertFalse($this->callMethod('GetById', [999]));
    }

    public function testGetValueById(): void
    {
        $this->assertEquals('Johnny', $this->callMethod('GetValueById', ['first_name', 1]));
        $this->assertEquals('', $this->callMethod('GetValueById', ['first_name', 999]));
    }

    public function testGetAll1(): void
    {
        $this->assertEquals(
            [
                0 => [
                    'id' => 1,
                    'first_name' => 'Johnny',
                    'last_name' => 'Appleseed',
                    'age' => 28,
                ],
                1 => [
                    'id' => 2,
                    'first_name' => 'Jenny',
                    'last_name' => 'Appleseed',
                    'age' => 31,
                ],
            ],
            $this->callMethod('getAll')
        );
    }

    public function testGetAll2(): void
    {
        $this->assertEquals(
            [
                0 => [
                    'id' => 1,
                    'first_name' => 'Johnny',
                ],
                1 => [
                    'id' => 2,
                    'first_name' => 'Jenny',
                ],
            ],
            $this->callMethod('getAll', ['id,first_name'])
        );
    }
}
