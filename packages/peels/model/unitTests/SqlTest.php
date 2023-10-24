<?php

declare(strict_types=1);

use dmyers\orange\Sql;

final class SqlTest extends unitTestHelper
{
    protected $instance;
    protected $pdo;

    protected function setUp(): void
    {
        // connect to test db
        $this->pdo = new PDO('mysql:host=' . $_ENV['phpunit']['host'] . ';dbname=' . $_ENV['phpunit']['database'], $_ENV['phpunit']['username'], $_ENV['phpunit']['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        // setup table(s) and data
        $this->pdo->query(file_get_contents(__DIR__ . '/support/test.sql'));

        $this->instance = new Sql([
            'tablename' => 'main',
            'primaryColumn' => 'id',
            'throwException' => false,
            'defaultFetchType' => PDO::FETCH_ASSOC,
            'fetchClass' => '',
            'errorFormat' => '[%1$s] %2$s',
        ], $this->pdo);
    }

    protected function tearDown(): void
    {
        $this->pdo->query('DROP TABLE IF EXISTS `main`');
        $this->pdo->query('DROP TABLE IF EXISTS `join`');
    }

    /* Public Method Tests */

    public function testHasError(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->select('foobar')->from()->run());

        $this->assertTrue($this->instance->hasError());

        $error = '[42S22] SQLSTATE[42S22]: Column not found: 1054 Unknown column \'foobar\' in \'field list\'';

        $this->assertEquals([$error], $this->instance->errors());
        $this->assertEquals($error, $this->instance->error());
    }

    public function testErrorFormat(): void
    {
        $this->setPrivatePublic('errorFormat', '{%1$s}::%2$s');

        $this->assertInstanceOf(Sql::class, $this->instance->select('foobar')->from()->run());

        $this->assertEquals('{42S22}::SQLSTATE[42S22]: Column not found: 1054 Unknown column \'foobar\' in \'field list\'', $this->instance->errorFormat());
    }

    public function testGetLast(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->select()->from()->wherePrimary(1)->run());

        $this->assertEquals([
            'sql' => 'SELECT * FROM `main`  WHERE `main`.`id` = ?',
            'args' => [
                0 => 1,
            ],
        ], $this->instance->getLast());
    }

    public function testReset(): void
    {
        $this->setPrivatePublic('lastSQL', 'select * from main');

        $this->assertEquals(['sql' => 'select * from main', 'args' => []], $this->instance->getLast());

        $this->instance->reset();

        $this->assertEquals(['sql' => '', 'args' => []], $this->instance->getLast());
    }

    public function testBuildSelect(): void
    {
        $this->assertTrue(true);
    }

    public function testBuildInsert(): void
    {
        $this->assertTrue(true);
    }

    public function testBuildUpdate(): void
    {
        $this->assertTrue(true);
    }

    public function testBuildDelete(): void
    {
        $this->assertTrue(true);
    }

    public function testColumn(): void
    {
        $this->assertEquals('Johnny', $this->instance->select('first_name')->from()->wherePrimary(1)->run()->column());
    }

    public function testRow(): void
    {
        $this->assertEquals(['first_name' => 'Johnny'], $this->instance->select('first_name')->from()->wherePrimary(1)->run()->row());
    }

    public function testRows(): void
    {
        $this->assertEquals([['first_name' => 'Johnny'], ['first_name' => 'Jenny']], $this->instance->select('first_name')->from()->run()->rows());
    }

    public function testLastInsertId(): void
    {
        $this->assertEquals(3, $this->instance->insert()->into()->values(['first_name' => 'Joe', 'last_name' => 'Coffee', 'age' => 27])->run()->lastInsertId());
    }

    public function testRowCount(): void
    {
        $this->assertEquals(1, $this->instance->update()->set(['first_name' => 'Joe', 'last_name' => 'Coffee', 'age' => 27])->wherePrimary(1)->run()->rowCount());

        $this->assertEquals([
            'sql' => 'UPDATE `main` SET `first_name` = ? , `last_name` = ? , `age` = ? WHERE `main`.`id` = ?',
            'args' => [
                0 => 'Joe',
                1 => 'Coffee',
                2 => 27,
                3 => 1,
            ],
        ], $this->instance->getLast());
    }

    public function testConnection(): void
    {
        $this->assertInstanceOf(PDOStatement::class, $this->instance->connection());
    }

    public function testSet(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->set(['foo' => 'bar']));

        $this->assertEquals([0 => [
            'column' => 'foo', 'value' => 'bar'
        ]], $this->getPrivatePublic('columns'));
    }

    public function testValues(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->values(['foo' => 'bar']));

        $this->assertEquals([0 => [
            'column' => 'foo', 'value' => 'bar'
        ]], $this->getPrivatePublic('columns'));
    }

    public function testGetColumnsSelect(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->select(['foo', 'bar']));

        $this->assertEquals(' `foo`,`bar`', $this->instance->GetSelectColumns());

        $this->assertEquals('select', $this->getPrivatePublic('method'));

        $this->assertInstanceOf(Sql::class, $this->instance->select('*'));

        $this->assertEquals(' *', $this->instance->GetSelectColumns());

        $this->assertInstanceOf(Sql::class, $this->instance->select('fname,lname'));

        $this->assertEquals(' `fname`,`lname`', $this->instance->GetSelectColumns());

        $this->assertInstanceOf(Sql::class, $this->instance->select('fname as f,lname'));

        $this->assertEquals(' `fname` AS `f`,`lname`', $this->instance->GetSelectColumns());

        $this->assertInstanceOf(Sql::class, $this->instance->select('fname as f,table.column'));

        $this->assertEquals(' `fname` AS `f`,`table`.`column`', $this->instance->GetSelectColumns());

        $this->assertInstanceOf(Sql::class, $this->instance->select('fname as f,table.column as tc'));

        $this->assertEquals(' `fname` AS `f`,`table`.`column` AS `tc`', $this->instance->GetSelectColumns());
    }

    /**
     * INSERT INTO table_name (column1, column2, column3, ...) VALUES (value1, value2, value3, ...);
     */
    public function testInsert(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->insert());

        $this->assertEquals('insert', $this->getPrivatePublic('method'));
    }

    public function testInsertValues(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->values(['foo' => 1, 'bar' => 2]));

        $this->assertEquals(' (`foo`,`bar`)', $this->instance->getInsertColumns());
        $this->assertEquals(' (?,?)', $this->instance->GetInsertValues());
        $this->assertEquals([1, 2], $this->instance->boundValues());
    }

    /**
     * UPDATE table_name SET column1 = value1, column2 = value2, ... WHERE condition;
     */
    public function testUpdate(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->update());

        $this->assertEquals('update', $this->getPrivatePublic('method'));
    }

    public function testGetUpdateSet(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->set(['foo' => 1, 'bar' => 'cat']));

        $this->assertEquals(' SET `foo` = ? , `bar` = ?', $this->instance->GetUpdateSet());
        $this->assertEquals([1, 'cat'], $this->instance->boundValues());
    }

    public function testBindValue(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->bindValue('dog'));

        $this->assertEquals(['dog'], $this->instance->boundValues());
    }

    /**
     * DELETE FROM table_name WHERE condition;
     */
    public function testDelete(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->delete());

        $this->assertEquals('delete', $this->getPrivatePublic('method'));
    }

    public function testEscapeTableColumn(): void
    {
        $this->assertEquals('`columnsname`', $this->instance->escapeTableColumn('columnsname'));
        $this->assertEquals('`columnsname` AS `cn`', $this->instance->escapeTableColumn('columnsname as cn'));
        $this->assertEquals('`tablename`.`columnsname`', $this->instance->escapeTableColumn('tablename.columnsname'));
        $this->assertEquals('`tablename`.`columnsname` AS `tc`', $this->instance->escapeTableColumn('tablename.columnsname as tc'));
    }

    public function testWhere(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->where('columna', '>', 123));

        $this->assertEquals(' WHERE `columna` > ?', $this->instance->getWhere());
        $this->assertEquals([123], $this->instance->boundValues());
    }

    public function testWhereEqual(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->whereEqual('columna', 123));

        $this->assertEquals(' WHERE `columna` = ?', $this->instance->getWhere());
        $this->assertEquals([123], $this->instance->boundValues());
    }

    public function testWherePrimary(): void
    {
        $this->setPrivatePublic('tablename', 'people');
        $this->setPrivatePublic('primaryColumn', 'pid');

        $this->assertInstanceOf(Sql::class, $this->instance->wherePrimary(333));

        $this->assertEquals(' WHERE `people`.`pid` = ?', $this->instance->getWhere());
        $this->assertEquals([333], $this->instance->boundValues());
    }

    public function testAnd(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->whereEqual('fname', 'Johnny'));
        $this->assertInstanceOf(Sql::class, $this->instance->and());
        $this->assertInstanceOf(Sql::class, $this->instance->whereEqual('lname', 'Appleseed'));

        $this->assertEquals(' WHERE `fname` = ? AND `lname` = ?', $this->instance->getWhere());
        $this->assertEquals(['Johnny', 'Appleseed'], $this->instance->boundValues());
    }

    public function testOr(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->whereEqual('fname', 'Johnny'));
        $this->assertInstanceOf(Sql::class, $this->instance->or());
        $this->assertInstanceOf(Sql::class, $this->instance->whereEqual('lname', 'Appleseed'));

        $this->assertEquals(' WHERE `fname` = ? OR `lname` = ?', $this->instance->getWhere());
        $this->assertEquals(['Johnny', 'Appleseed'], $this->instance->boundValues());
    }

    public function testNot(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->not());
        $this->assertInstanceOf(Sql::class, $this->instance->whereEqual('country', 'Germany'));

        $this->assertEquals(' WHERE NOT `country` = ?', $this->instance->getWhere());
        $this->assertEquals(['Germany'], $this->instance->boundValues());
    }

    public function testGroup(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->groupStart());
        $this->assertInstanceOf(Sql::class, $this->instance->whereEqual('fname', 'Johnny'));
        $this->assertInstanceOf(Sql::class, $this->instance->or());
        $this->assertInstanceOf(Sql::class, $this->instance->whereEqual('lname', 'Appleseed'));
        $this->assertInstanceOf(Sql::class, $this->instance->groupEnd());

        $this->assertEquals(' WHERE ( `fname` = ? OR `lname` = ? )', $this->instance->getWhere());
        $this->assertEquals(['Johnny', 'Appleseed'], $this->instance->boundValues());
    }

    public function testGroup2(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->groupStart());
        $this->assertInstanceOf(Sql::class, $this->instance->whereEqual('fname', 'Johnny'));
        $this->assertInstanceOf(Sql::class, $this->instance->or());
        $this->assertInstanceOf(Sql::class, $this->instance->whereEqual('lname', 'Appleseed'));
        $this->assertInstanceOf(Sql::class, $this->instance->groupEnd());
        $this->assertInstanceOf(Sql::class, $this->instance->or());
        $this->assertInstanceOf(Sql::class, $this->instance->whereEqual('foo', 'bar'));


        $this->assertEquals(' WHERE ( `fname` = ? OR `lname` = ? )  OR `foo` = ?', $this->instance->getWhere());
        $this->assertEquals(['Johnny', 'Appleseed', 'bar'], $this->instance->boundValues());
    }

    public function testWhereRaw1(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->whereRaw('`columnname` IN (?,?,?,?)', [12, 23, 45, 789]));
        $this->assertEquals(' WHERE `columnname` IN (?,?,?,?)', $this->instance->getWhere());
        $this->assertEquals([12, 23, 45, 789], $this->instance->boundValues());
    }

    public function testWhereRaw2(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->whereRaw('`Price` NOT BETWEEN ? AND ?', [10, 20]));
        $this->assertEquals(' WHERE `Price` NOT BETWEEN ? AND ?', $this->instance->getWhere());
        $this->assertEquals([10, 20], $this->instance->boundValues());
    }

    public function testLimit(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->limit(1));
        $this->assertEquals(' LIMIT 1', $this->instance->getLimit());

        $this->assertInstanceOf(Sql::class, $this->instance->limit(1, 10));
        $this->assertEquals(' LIMIT 1,10', $this->instance->getLimit());
    }

    public function testOrderBy(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->OrderBy('first_name'));
        $this->assertEquals(' ORDER BY `first_name`', $this->instance->getOrderBy());

        $this->assertInstanceOf(Sql::class, $this->instance->reset());

        $this->assertInstanceOf(Sql::class, $this->instance->OrderBy('first_name', 'asc'));
        $this->assertEquals(' ORDER BY `first_name` ASC', $this->instance->getOrderBy());

        $this->assertInstanceOf(Sql::class, $this->instance->reset());

        $this->assertInstanceOf(Sql::class, $this->instance->OrderBy('first_name', 'asc'));
        $this->assertInstanceOf(Sql::class, $this->instance->OrderBy('last_name', 'desc'));

        $this->assertEquals(' ORDER BY `first_name` ASC, `last_name` DESC', $this->instance->getOrderBy());

        $this->assertInstanceOf(Sql::class, $this->instance->reset());

        $this->assertInstanceOf(Sql::class, $this->instance->OrderBy('tablename.first_name'));
        $this->assertInstanceOf(Sql::class, $this->instance->OrderBy('last_name', 'desc'));

        $this->assertEquals(' ORDER BY `tablename`.`first_name`, `last_name` DESC', $this->instance->getOrderBy());

        $this->assertInstanceOf(Sql::class, $this->instance->OrderBy('tablename.first_name'));
        $this->assertInstanceOf(Sql::class, $this->instance->OrderBy('last_name', 'az'));

        $this->assertEquals(' ORDER BY `tablename`.`first_name`, `last_name` DESC', $this->instance->getOrderBy());

        $this->assertInstanceOf(Sql::class, $this->instance->OrderBy('tablename.first_name'));
        $this->assertInstanceOf(Sql::class, $this->instance->OrderBy('last_name', 'za'));

        $this->assertEquals(' ORDER BY `tablename`.`first_name`, `last_name` ASC', $this->instance->getOrderBy());
    }

    // join(string $joinTable, string $on, string $left, string $right): self
    public function testJoin(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->join('child_table', 'inner', 'child_table.parent_id', 'parent_table.id'));

        $this->assertEquals(' INNER JOIN `child_table` ON `child_table`.`parent_id`=`parent_table`.`id`', $this->instance->getJoins());
    }

    public function testInnerJoin(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->innerJoin('child_table', 'child_table.parent_id', 'parent_table.id'));

        $this->assertEquals(' INNER JOIN `child_table` ON `child_table`.`parent_id`=`parent_table`.`id`', $this->instance->getJoins());
    }

    public function testLeftJoin(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->leftJoin('child_table', 'child_table.parent_id', 'parent_table.id'));

        $this->assertEquals(' LEFT JOIN `child_table` ON `child_table`.`parent_id`=`parent_table`.`id`', $this->instance->getJoins());
    }

    public function testRightJoin(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->rightJoin('child_table', 'child_table.parent_id', 'parent_table.id'));

        $this->assertEquals(' RIGHT JOIN `child_table` ON `child_table`.`parent_id`=`parent_table`.`id`', $this->instance->getJoins());

        $this->instance->reset();

        $this->assertInstanceOf(Sql::class, $this->instance->rightJoin('child_table as ct', 'ct.parent_id', 'parent_table.id'));

        $this->assertEquals(' RIGHT JOIN `child_table` AS `ct` ON `ct`.`parent_id`=`parent_table`.`id`', $this->instance->getJoins());
    }

    public function testJoins(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->innerJoin('child_table as ct', 'ct.parent_id', 'parent_table.id'));
        $this->assertInstanceOf(Sql::class, $this->instance->leftJoin('child_table', 'child_table.parent_id', 'parent_table.id'));
        $this->assertInstanceOf(Sql::class, $this->instance->rightJoin('child_table', 'child_table.parent_id', 'parent_table.id'));

        $this->assertEquals(' INNER JOIN `child_table` AS `ct` ON `ct`.`parent_id`=`parent_table`.`id` LEFT JOIN `child_table` ON `child_table`.`parent_id`=`parent_table`.`id` RIGHT JOIN `child_table` ON `child_table`.`parent_id`=`parent_table`.`id`', $this->instance->getJoins());
    }

    public function testInto(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->into('myTable'));
        $this->assertEquals(' INTO `myTable`', $this->instance->getInto());
    }

    public function testFrom(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->from('myTable'));
        $this->assertEquals(' FROM `myTable`', $this->instance->getFrom());

        $this->assertInstanceOf(Sql::class, $this->instance->from('myTable as mt'));
        $this->assertEquals(' FROM `myTable` AS `mt`', $this->instance->getFrom());

        $this->assertInstanceOf(Sql::class, $this->instance->from('myTable mt'));
        $this->assertEquals(' FROM `myTable` AS `mt`', $this->instance->getFrom());
    }

    public function testTable(): void
    {
        $this->assertInstanceOf(Sql::class, $this->instance->table('myTable'));
        $this->assertEquals(' `myTable`', $this->instance->getTable());

        $this->assertInstanceOf(Sql::class, $this->instance->table('myTable as mt'));
        $this->assertEquals(' `myTable` AS `mt`', $this->instance->getTable());

        $this->assertInstanceOf(Sql::class, $this->instance->table('myTable mt'));
        $this->assertEquals(' `myTable` AS `mt`', $this->instance->getTable());
    }

    /**
     * query(string $sql, array $args = [], int $fetchMode = -1): PDOStatement
     */
    public function testQuery(): void
    {
        $this->assertInstanceOf(PDOStatement::class, $this->instance->query('select * from `main` where `id` = ?', [1]));
    }

    public function testSetFetchModeAsFetchAssoc(): void
    {
        $pdoStatement = $this->instance->query('select * from `main` where `id` = ?', [1], PDO::FETCH_ASSOC);

        $this->assertInstanceOf(PDOStatement::class, $pdoStatement);

        $this->assertEquals([
            'id' => 1,
            'first_name' => 'Johnny',
            'last_name' => 'Appleseed',
            'age' => 28,
        ], $pdoStatement->fetch());
    }

    public function testSetFetchModeAsFetchClass(): void
    {
        require __DIR__ . '/support/modelRowClass.php';

        $this->setPrivatePublic('fetchClass', modelRowClass::class);

        $pdoStatement = $this->instance->query('select * from `main` where `id` = ?', [1], PDO::FETCH_CLASS);

        $this->assertInstanceOf(PDOStatement::class, $pdoStatement);

        $class = new modelRowClass();

        $class->id = 1;
        $class->first_name = 'Johnny';
        $class->last_name = 'Appleseed';
        $class->age = 28;

        $this->assertEquals($class, $pdoStatement->fetch());
    }

    public function testSetFetchModeAsFetchObject(): void
    {
        $pdoStatement = $this->instance->query('select * from `main` where `id` = ?', [1], PDO::FETCH_OBJ);

        $this->assertInstanceOf(PDOStatement::class, $pdoStatement);

        $class = new stdClass();

        $class->id = 1;
        $class->first_name = 'Johnny';
        $class->last_name = 'Appleseed';
        $class->age = 28;

        $this->assertEquals($class, $pdoStatement->fetch());
    }

    public function testSelectAll(): void
    {
        $sqlQuery = $this->instance->select()->from()->build();

        $this->assertEquals("SELECT * FROM `main`", $sqlQuery);
    }

    public function testBiggerQuerys(): void
    {
        $sqlQuery = $this->instance->select('cow,dog,cat')->from('foobar')->wherePrimary(1)->build();

        $this->assertEquals("SELECT `cow`,`dog`,`cat` FROM `foobar`  WHERE `foobar`.`id` = ?", $sqlQuery);


        $sqlQuery = $this->instance->select('cow as c,dog d,cat')->from('foobar')->wherePrimary(1)->build();

        $this->assertEquals("SELECT `cow` AS `c`,`dog` AS `d`,`cat` FROM `foobar`  WHERE `foobar`.`id` = ?", $sqlQuery);

        $sqlQuery = $this->instance->select('f.cow as c,f.dog d,f.cat')->from('foobar f')->wherePrimary(1)->build();

        $this->assertEquals("SELECT `f`.`cow` AS `c`,`f`.`dog` AS `d`,`f`.`cat` FROM `foobar` AS `f`  WHERE `f`.`id` = ?", $sqlQuery);

        $sqlQuery = $this->instance->select('f.cow as c,f.dog d,f.cat')->from('foobar f')->wherePrimary(1)->and()->WhereEqual('moo', 'cow')->build();

        $this->assertEquals("SELECT `f`.`cow` AS `c`,`f`.`dog` AS `d`,`f`.`cat` FROM `foobar` AS `f`  WHERE `f`.`id` = ? AND `moo` = ?", $sqlQuery);

        $sqlQuery = $this->instance->select('f.cow as c,f.dog d,f.cat')->from('foobar f')->innerJoin('table2', 'f.id', 'table2.parent.id')->wherePrimary(1)->and()->WhereEqual('moo', 'cow')->build();

        $this->assertEquals("SELECT `f`.`cow` AS `c`,`f`.`dog` AS `d`,`f`.`cat` FROM `foobar` AS `f`  INNER JOIN `table2` ON `f`.`id`=`table2`.`parent`.`id` WHERE `f`.`id` = ? AND `moo` = ?", $sqlQuery);


        $sqlQuery = $this->instance->select('f.cow as c,f.dog d,f.cat')->from('foobar f')->innerJoin('table2', 'f.id', 'table2.parent.id')->and('table2.status', 1)->wherePrimary(1)->and()->WhereEqual('moo', 'cow')->build();

        $this->assertEquals("SELECT `f`.`cow` AS `c`,`f`.`dog` AS `d`,`f`.`cat` FROM `foobar` AS `f`  INNER JOIN `table2` ON `f`.`id`=`table2`.`parent`.`id` AND `table2`.`status` = ? WHERE `f`.`id` = ? AND `moo` = ?", $sqlQuery);


        $sqlQuery = $this->instance->select('f.cow as c,f.dog d,f.cat')->from('foobar f')->innerJoin('table2', 'f.id', 'table2.parent.id')->wherePrimary(1)->and()->WhereEqual('moo', 'cow')->limit(100)->build();

        $this->assertEquals("SELECT `f`.`cow` AS `c`,`f`.`dog` AS `d`,`f`.`cat` FROM `foobar` AS `f`  INNER JOIN `table2` ON `f`.`id`=`table2`.`parent`.`id` WHERE `f`.`id` = ? AND `moo` = ? LIMIT 100", $sqlQuery);


        $sqlQuery = $this->instance->select('f.cow as c,f.dog d,f.cat')->from('foobar f')->innerJoin('table2', 'f.id', 'table2.parent.id')->wherePrimary(1)->and()->WhereEqual('moo', 'cow')->limit(10, 100)->build();

        $this->assertEquals("SELECT `f`.`cow` AS `c`,`f`.`dog` AS `d`,`f`.`cat` FROM `foobar` AS `f`  INNER JOIN `table2` ON `f`.`id`=`table2`.`parent`.`id` WHERE `f`.`id` = ? AND `moo` = ? LIMIT 10,100", $sqlQuery);


        $sqlQuery = $this->instance->select('f.cow as c,f.dog d,f.cat')->from('foobar f')->innerJoin('table2', 'f.id', 'table2.parent.id')->wherePrimary(1)->and()->WhereEqual('moo', 'cow')->limit(10, 100)->orderBy('c', 'desc')->build();

        $this->assertEquals("SELECT `f`.`cow` AS `c`,`f`.`dog` AS `d`,`f`.`cat` FROM `foobar` AS `f`  INNER JOIN `table2` ON `f`.`id`=`table2`.`parent`.`id` WHERE `f`.`id` = ? AND `moo` = ? ORDER BY `c` DESC LIMIT 10,100", $sqlQuery);


        $sqlQuery = $this->instance->select('f.cow as c,f.dog d,f.cat')->from('foobar f')->innerJoin('table2', 'f.id', 'table2.parent.id')->groupStart()->wherePrimary(1)->and()->WhereEqual('moo', 'cow')->groupEnd()->or()->Where('d', '=', 123)->limit(10, 100)->orderBy('c', 'desc')->build();

        $this->assertEquals("SELECT `f`.`cow` AS `c`,`f`.`dog` AS `d`,`f`.`cat` FROM `foobar` AS `f`  INNER JOIN `table2` ON `f`.`id`=`table2`.`parent`.`id` WHERE ( `f`.`id` = ? AND `moo` = ? )  OR `d` = ? ORDER BY `c` DESC LIMIT 10,100", $sqlQuery);

        $sqlQuery = $this->instance->select('f.cow as c,f.dog d,f.cat')->from('foobar f')->innerJoin('table2', 'f.id', 'table2.parent.id')->groupStart()->wherePrimary(1)->and()->WhereEqual('moo', 'cow')->groupEnd()->or()->Where('d', '=', 123)->or()->Where('cat', 'yellow')->limit(10, 100)->orderBy('c', 'desc')->build();

        $this->assertEquals("SELECT `f`.`cow` AS `c`,`f`.`dog` AS `d`,`f`.`cat` FROM `foobar` AS `f`  INNER JOIN `table2` ON `f`.`id`=`table2`.`parent`.`id` WHERE ( `f`.`id` = ? AND `moo` = ? )  OR `d` = ? OR `cat` = ? ORDER BY `c` DESC LIMIT 10,100", $sqlQuery);
    }
}
