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
        // connect to test db
        $this->pdo = new PDO('mysql:host=' . $_ENV['phpunit']['host'] . ';dbname=' . $_ENV['phpunit']['database'], $_ENV['phpunit']['username'], $_ENV['phpunit']['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        // setup table(s) and data
        $this->pdo->query(file_get_contents(__DIR__ . '/support/test.sql'));

        // instance of Model Abstract Class
        $this->instance = $this->getMockForAbstractClass(ModelAbstract::class, [$this->pdo, []]);

        // setup default tablename and primary id
        $this->setPrivatePublic('tablename', 'main');
        $this->setPrivatePublic('primaryColumn', 'id');
    }

    protected function tearDown(): void
    {
        $this->pdo->query('DROP TABLE IF EXISTS `main`');
        $this->pdo->query('DROP TABLE IF EXISTS `join`');
    }

    /* Tests */

    /* public */

    public function testHasError1(): void
    {
        $this->callMethod('_row', ['select * from `foobar` where id = ?', [2], -1]);

        $this->assertTrue($this->callMethod('hasError'));
        $this->assertEquals(['code' => '42S02', 'msg' => 'SQLSTATE[42S02]: Base table or view not found: 1146 Table \'' . $_ENV['phpunit']['database'] . '.foobar\' doesn\'t exist'], $this->callMethod('errors'));
    }

    public function testHasError2(): void
    {
        $this->callMethod('_row', ['select * from `main` where foobar = ?', [2], -1]);

        $this->assertTrue($this->callMethod('hasError'));
        $this->assertEquals(['code' => '42S22', 'msg' => 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'foobar\' in \'where clause\''], $this->callMethod('errors'));
    }

    /* protected */

    public function test_reset(): void
    {
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_reset'));
    }

    public function test_getById(): void
    {
        $this->assertEquals([
            'id' => 1,
            'first_name' => 'Johnny',
            'last_name' => 'Appleseed',
            'age' => 28,
        ], $this->callMethod('_getById', [1]));

        $this->assertEquals('SELECT * FROM `main`  WHERE `id` = ?', $this->getPrivatePublic('lastSQL'));
        $this->assertEquals([0 => 1], $this->getPrivatePublic('lastArgs'));
    }

    public function test_getById2(): void
    {
        $this->assertFalse($this->callMethod('_getById', [999]));

        $this->assertEquals('SELECT * FROM `main`  WHERE `id` = ?', $this->getPrivatePublic('lastSQL'));
        $this->assertEquals([0 => 999], $this->getPrivatePublic('lastArgs'));
    }

    public function test_getColumnById(): void
    {
        $this->assertEquals([
            'first_name' => 'Johnny',
        ], $this->callMethod('_getColumnById', ['first_name', 1]));

        $this->assertEquals('SELECT `first_name` FROM `main`  WHERE `id` = ?', $this->getPrivatePublic('lastSQL'));
        $this->assertEquals([0 => 1], $this->getPrivatePublic('lastArgs'));
    }

    public function test_getColumnById2(): void
    {
        $this->assertEquals([
            'fname' => 'Johnny',
        ], $this->callMethod('_getColumnById', ['first_name as fname', 1]));

        $this->assertEquals('SELECT `first_name` AS `fname` FROM `main`  WHERE `id` = ?', $this->getPrivatePublic('lastSQL'));
        $this->assertEquals([0 => 1], $this->getPrivatePublic('lastArgs'));
    }

    public function test_getValueById(): void
    {
        $this->assertEquals('Johnny', $this->callMethod('_getValueById', ['first_name', 1]));

        $this->assertEquals('SELECT `first_name` FROM `main`  WHERE `id` = ?', $this->getPrivatePublic('lastSQL'));
        $this->assertEquals([0 => 1], $this->getPrivatePublic('lastArgs'));
    }

    public function test_row(): void
    {
        $this->assertEquals([
            'id' => 2,
            'first_name' => 'Jenny',
            'last_name' => 'Appleseed',
            'age' => 31,
        ], $this->callMethod('_row', ['SELECT * FROM `main` WHERE `id` = ?', [2], -1]));

        $this->assertEquals('SELECT * FROM `main` WHERE `id` = ?', $this->getPrivatePublic('lastSQL'));
        $this->assertEquals([0 => 2], $this->getPrivatePublic('lastArgs'));
    }

    public function test_rows(): void
    {
        $this->assertEquals([
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
        ], $this->callMethod('_rows', ['SELECT * FROM `main`', [], -1]));

        $this->assertEquals('SELECT * FROM `main`', $this->getPrivatePublic('lastSQL'));
        $this->assertEquals([], $this->getPrivatePublic('lastArgs'));
    }

    public function test_select(): void
    {
        $this->assertEquals([
            0 => [
                'first_name' => 'Johnny',
                'last_name' => 'Appleseed',
            ],
            1 => [
                'first_name' => 'Jenny',
                'last_name' => 'Appleseed',
            ],
        ], $this->callMethod('_select', ['first_name,last_name', -1]));

        $this->assertEquals('SELECT `first_name`,`last_name` FROM `main`', $this->getPrivatePublic('lastSQL'));
        $this->assertEquals([], $this->getPrivatePublic('lastArgs'));
    }

    public function test_select2(): void
    {
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_where', ['id', 2]));
        $this->assertEquals([
            0 => [
                'first_name' => 'Jenny',
                'last_name' => 'Appleseed',
            ],
        ], $this->callMethod('_select', ['first_name,last_name', -1]));

        $this->assertEquals('SELECT `first_name`,`last_name` FROM `main`  WHERE `id` = ?', $this->getPrivatePublic('lastSQL'));
        $this->assertEquals([0 => 2], $this->getPrivatePublic('lastArgs'));
    }

    public function test_select3(): void
    {
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_where', ['id', 999]));
        $this->assertEquals([], $this->callMethod('_select', ['first_name,last_name', -1]));

        $this->assertEquals('SELECT `first_name`,`last_name` FROM `main`  WHERE `id` = ?', $this->getPrivatePublic('lastSQL'));
        $this->assertEquals([0 => 999], $this->getPrivatePublic('lastArgs'));
    }

    public function test_selectAlias(): void
    {
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_where', ['id', 1]));
        $this->assertEquals([0 => [
            'foobar' => 'Johnny',
            'last_name' => 'Appleseed',
        ]], $this->callMethod('_select', ['first_name As foobar,last_name', -1]));

        $this->assertEquals('SELECT `first_name` AS `foobar`,`last_name` FROM `main`  WHERE `id` = ?', $this->getPrivatePublic('lastSQL'));
        $this->assertEquals([0 => 1], $this->getPrivatePublic('lastArgs'));
    }

    public function test_SelectWithJoin(): void
    {
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_where', ['main.id', 2]));
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_join', ['join', 'inner', 'join.parent_id', 'main.id']));
        $this->assertEquals(
            [
                0 => [
                    'id' => 2,
                    'first_name' => 'Jenny',
                    'last_name' => 'Appleseed',
                    'age' => 31,
                    'parent_id' => 2,
                    'child_name' => 'Sally',
                ],
                1 => [
                    'id' => 3,
                    'first_name' => 'Jenny',
                    'last_name' => 'Appleseed',
                    'age' => 31,
                    'parent_id' => 2,
                    'child_name' => 'Chuck',
                ]
            ],
            $this->callMethod('_select')
        );

        $this->assertEquals('SELECT * FROM `main` INNER JOIN `join` ON `join`.`parent_id`=`main`.`id` WHERE `main`.`id` = ?', $this->getPrivatePublic('lastSQL'));
        $this->assertEquals([0 => 2], $this->getPrivatePublic('lastArgs'));
    }

    public function test_join(): void
    {
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_join', ['join', 'inner', 'join.parent_id', 'main.id']));

        $this->assertEquals('INNER JOIN `join` ON `join`.`parent_id`=`main`.`id`', $this->callMethod('_getJoin'));
    }

    public function test_joinLeft(): void
    {
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_joinLeft', ['join', 'join.parent_id', 'main.id']));

        $this->assertEquals('LEFT JOIN `join` ON `join`.`parent_id`=`main`.`id`', $this->callMethod('_getJoin'));
    }

    public function test_insert(): void
    {
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_insert', [['first_name' => 'Joe', 'last_name' => 'Coffee', 'age' => 21]]));
        $this->assertEquals('INSERT INTO `main` (`first_name`,`last_name`,`age`) VALUES (?,?,?)', $this->getPrivatePublic('lastSQL'));
        $this->assertEquals(3, $this->callMethod('_lastInsertId'));
        $this->assertEquals(1, $this->callMethod('_lastAffectedRows'));

        $this->assertEquals([
            'id' => 3,
            'first_name' => 'Joe',
            'last_name' => 'Coffee',
            'age' => 21,
        ], $this->callMethod('_getById', [3]));
    }

    public function test_updateById(): void
    {
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_updateById', [['first_name' => 'Joe', 'last_name' => 'Coffee', 'age' => 21], 2]));
        $this->assertEquals('UPDATE `main` SET `first_name` = ?,`last_name` = ?,`age` = ?WHERE `id` = ?', $this->getPrivatePublic('lastSQL'));

        $this->assertEquals(1, $this->callMethod('_lastAffectedRows'));

        $this->assertEquals([
            'id' => 2,
            'first_name' => 'Joe',
            'last_name' => 'Coffee',
            'age' => 21,
        ], $this->callMethod('_getById', [2]));
    }

    public function test_update(): void
    {
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_where', ['id', 2]));
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_update', [['first_name' => 'Joe', 'last_name' => 'Coffee', 'age' => 21]]));
        $this->assertEquals('UPDATE `main` SET `first_name` = ?,`last_name` = ?,`age` = ?WHERE `id` = ?', $this->getPrivatePublic('lastSQL'));
        $this->assertEquals(1, $this->callMethod('_lastAffectedRows'));

        $this->assertEquals([
            'id' => 2,
            'first_name' => 'Joe',
            'last_name' => 'Coffee',
            'age' => 21,
        ], $this->callMethod('_getById', [2]));
    }

    public function test_delete(): void
    {
        $this->assertTrue($this->callMethod('_existsById', [2]));

        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_where', ['id', 2]));
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_delete'));
        $this->assertEquals('DELETE FROM `main`WHERE `id` = ?', $this->getPrivatePublic('lastSQL'));
        $this->assertEquals(1, $this->callMethod('_lastAffectedRows'));

        $this->assertFalse($this->callMethod('_existsById', [2]));
    }

    public function test_deleteById(): void
    {
        $this->assertTrue($this->callMethod('_existsById', [2]));

        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_deleteById', [2]));
        $this->assertEquals('DELETE FROM `main`WHERE `id` = ?', $this->getPrivatePublic('lastSQL'));
        $this->assertEquals(1, $this->callMethod('_lastAffectedRows'));

        $this->assertFalse($this->callMethod('_existsById', [2]));
    }

    public function test_escape(): void
    {
        $this->assertEquals('`first_name`,`last_name`,`table`.`name`', $this->callMethod('_columns', [['first_name', 'last_name', 'table.name']]));

        $this->assertEquals('`firstname` AS `fname`,`lastname`,`table`.`name`', $this->callMethod('_columns', ['firstname as fname, lastname,table.name']));

        $this->setPrivatePublic('tablename', 'user');

        $this->assertEquals('`user`', $this->callMethod('_tablename'));

        $this->assertEquals('`database`.`table`', $this->callMethod('_escapeTableColumn', ['database.table']));
    }

    public function test_existsById(): void
    {
        $this->assertTrue($this->callMethod('_existsById', [2]));
        $this->assertEquals('SELECT `id` FROM `main`  WHERE `id` = ?  LIMIT 1', $this->getPrivatePublic('lastSQL'));
        $this->assertEquals([0 => 2], $this->getPrivatePublic('lastArgs'));

        $this->assertFalse($this->callMethod('_existsById', [999]));
    }

    public function test_exists(): void
    {
        $this->assertTrue($this->callMethod('_exists', ["SELECT `id` FROM `main` where `id` = ?", [2]]));
        $this->assertEquals('SELECT `id` FROM `main` where `id` = ?', $this->getPrivatePublic('lastSQL'));
        $this->assertEquals([0 => 2], $this->getPrivatePublic('lastArgs'));

        $this->assertFalse($this->callMethod('_exists', ["SELECT `id` FROM `main` where `id` = ?", [999]]));
    }

    public function test_lastInsertId(): void
    {
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_insert', [['first_name' => 'Joe', 'last_name' => 'Coffee', 'age' => 21]]));
        $this->assertEquals('INSERT INTO `main` (`first_name`,`last_name`,`age`) VALUES (?,?,?)', $this->getPrivatePublic('lastSQL'));
        $this->assertEquals([0 => 'Joe', 1 => 'Coffee', 2 => '21'], $this->getPrivatePublic('lastArgs'));
        $this->assertEquals(3, $this->callMethod('_lastInsertId'));
    }

    public function test_lastAffectedRows(): void
    {
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_insert', [['first_name' => 'Joe', 'last_name' => 'Coffee', 'age' => 29]]));
        $this->assertEquals('INSERT INTO `main` (`first_name`,`last_name`,`age`) VALUES (?,?,?)', $this->getPrivatePublic('lastSQL'));
        $this->assertEquals([0 => 'Joe', 1 => 'Coffee', 2 => '29'], $this->getPrivatePublic('lastArgs'));
        $this->assertEquals(1, $this->callMethod('_lastAffectedRows'));
    }

    public function test_whereAndArray(): void
    {
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_whereAndArray', [['first_name' => 'Joe', 'last_name' => 'Coffee', 'age' => 21]]));
        $this->assertEquals('WHERE `first_name` = ? AND `last_name` = ? AND `age` = ?', $this->callMethod('_getWhere'));

        $this->assertEquals([0 => 'Joe', 1 => 'Coffee', 2 => 21], $this->callMethod('_getValues'));
    }

    public function test_where(): void
    {
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_where', ['first_name', 'Joe']));
        $this->assertEquals('WHERE `first_name` = ?', $this->callMethod('_getWhere'));

        $this->assertEquals([0 => 'Joe'], $this->callMethod('_getValues'));
    }

    public function test_whereAnd(): void
    {
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_where', ['first_name', 'Joe']));
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_whereAnd'));
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_where', ['last_name', 'Coffee']));

        $this->assertEquals('WHERE `first_name` = ? AND `last_name` = ?', $this->callMethod('_getWhere'));

        $this->assertEquals([0 => 'Joe', 1 => 'Coffee'], $this->callMethod('_getValues'));
    }

    public function test_whereOr(): void
    {
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_where', ['first_name', 'Joe']));
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_whereOr'));
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_where', ['last_name', 'Coffee']));

        $this->assertEquals('WHERE `first_name` = ? OR `last_name` = ?', $this->callMethod('_getWhere'));

        $this->assertEquals([0 => 'Joe', 1 => 'Coffee'], $this->callMethod('_getValues'));
    }

    public function testGroupStartEnd(): void
    {
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_whereGroupStart'));
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_where', ['first_name', 'Joe']));
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_whereAnd'));
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_where', ['last_name', 'Coffee']));
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_whereGroupEnd'));

        $this->assertEquals('WHERE ( `first_name` = ? AND `last_name` = ? )', $this->callMethod('_getWhere'));

        $this->assertEquals([0 => 'Joe', 1 => 'Coffee'], $this->callMethod('_getValues'));
    }

    public function testGroupStartEnd2(): void
    {
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_whereGroupStart'));
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_where', ['first_name', 'Joe']));
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_whereAnd'));
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_where', ['last_name', 'Coffee']));
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_whereGroupEnd'));
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_whereOr'));
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_where', ['admin', 1]));

        $this->assertEquals('WHERE ( `first_name` = ? AND `last_name` = ? )  OR `admin` = ?', $this->callMethod('_getWhere'));

        $this->assertEquals([0 => 'Joe', 1 => 'Coffee', 2 => 1], $this->callMethod('_getValues'));
    }

    public function test_limit(): void
    {
        $this->assertEquals('', $this->callMethod('_getLimit'));

        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_limit', [1]));
        $this->assertEquals('LIMIT 1', $this->callMethod('_getLimit'));

        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_limit', [1, 10]));
        $this->assertEquals('LIMIT 1,10', $this->callMethod('_getLimit'));
    }

    public function test_orderBy(): void
    {
        $this->assertEquals('', $this->callMethod('_getOrderBy'));

        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_orderBy', ['name']));
        $this->assertEquals('ORDER BY `name`', $this->callMethod('_getOrderBy'));

        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_reset'));

        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_orderBy', ['name', 'desc']));
        $this->assertEquals('ORDER BY `name` desc', $this->callMethod('_getOrderBy'));

        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_reset'));

        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_orderBy', ['name', 'desc']));
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_orderBy', ['age', 'asc']));
        $this->assertEquals('ORDER BY `name` desc, `age` asc', $this->callMethod('_getOrderBy'));

        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_reset'));

        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_orderBy', ['name', 'desc']));
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_orderBy', ['food']));
        $this->assertInstanceOf(ModelAbstract::class, $this->callMethod('_orderBy', ['age', 'asc']));
        $this->assertEquals('ORDER BY `name` desc, `food`, `age` asc', $this->callMethod('_getOrderBy'));
    }

    public function test_setFetchMode(): void
    {
        $this->setPrivatePublic('defaultFetchType', PDO::FETCH_ASSOC);

        $this->assertEquals([
            'id' => 1,
            'first_name' => 'Johnny',
            'last_name' => 'Appleseed',
            'age' => 28,
        ], $this->callMethod('_getById', [1]));

        $this->setPrivatePublic('defaultFetchType', PDO::FETCH_NUM);

        $this->assertEquals([
            0 => 1,
            1 => 'Johnny',
            2 => 'Appleseed',
            3 => 28,
        ], $this->callMethod('_getById', [1]));

        $this->setPrivatePublic('defaultFetchType', PDO::FETCH_BOTH);

        $this->assertEquals([
            'id' => 1,
            'first_name' => 'Johnny',
            'last_name' => 'Appleseed',
            'age' => 28,
            0 => 1,
            1 => 'Johnny',
            2 => 'Appleseed',
            3 => 28,
        ], $this->callMethod('_getById', [1]));

        $this->setPrivatePublic('defaultFetchType', PDO::FETCH_OBJ);

        $stdClass = new stdClass;
        $stdClass->id = 1;
        $stdClass->first_name = 'Johnny';
        $stdClass->last_name = 'Appleseed';
        $stdClass->age = 28;

        $this->assertEquals($stdClass, $this->callMethod('_getById', [1]));
    }

    public function test_fetchClass(): void
    {
        require_once __DIR__ . '/support/modelRowClass.php';

        $this->setPrivatePublic('fetchClass', 'modelRowClass');

        $class = $this->callMethod('_getById', [1]);

        $this->assertInstanceOf(modelRowClass::class, $class);

        $this->assertEquals(1, $class->id);
        $this->assertEquals('Johnny', $class->first_name);
        $this->assertEquals('Appleseed', $class->last_name);
        $this->assertEquals(28, $class->age);
        $this->assertEquals('Johnny Appleseed', $class->full_name);
    }

    /* support for private / protected properties and methods */

    private function getPrivatePublic($attribute)
    {
        $getter = function () use ($attribute) {
            return $this->$attribute;
        };

        $closure = \Closure::bind($getter, $this->instance, get_class($this->instance));

        return $closure();
    }

    private function setPrivatePublic($attribute, $value)
    {
        $setter = function ($value) use ($attribute) {
            $this->$attribute = $value;
        };

        $closure = \Closure::bind($setter, $this->instance, get_class($this->instance));

        $closure($value);
    }

    private function callMethod(string $method, array $args = null)
    {
        $reflectionMethod = new ReflectionMethod($this->instance, $method);

        return (is_array($args)) ? $reflectionMethod->invokeArgs($this->instance, $args) : $reflectionMethod->invoke($this->instance);
    }
}
