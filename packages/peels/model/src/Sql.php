<?php

declare(strict_types=1);

namespace peels\model;

use PDO;
use Throwable;
use PDOStatement;
use peels\model\StringBuilder;
use orange\framework\exceptions\InvalidValue;
use orange\framework\exceptions\SqlBuilderException;

/**
 * Basic SQL abstraction layer
 * Not meant to be a ORM or replace doing complex Query's in your models
 *
 *
 * $sql->select('name,age')->from('foo')->wherePrimary(1)->build();
 *
 * $sql->update()->table('foo')->set(['name'=>'jake'])->wherePrimary(1)->build();
 * $sql->update('foo')->set(['name'=>'jake'])->wherePrimary(1)->build();
 *
 * $sql->insert()->into('foo')->set(['name'=>'jake'])->build();
 * $sql->insert('foo')->set(['name'=>'jake'])->build();
 *
 * $sql->delete()->from('table')->wherePrimary(1)->build();
 * $sql->delete('table')->wherePrimary(1)->build();
 *
 */

class Sql
{
    // You can change these in you model
    public PDO $pdo;
    public PDOStatement $pdoStatement;

    public string $tablename = '';
    public string $primaryColumn = '';
    public string $errorFormat = '[%1$s] %2$s';
    public bool $throwException = false;
    public string $fetchClass = '';

    protected array $config = [];

    protected string $errorCode = '';
    protected string $errorMsg = '';

    protected string $lastSQL = '';
    protected array $lastArgs = [];

    protected int $fetchMode = PDO::FETCH_ASSOC;
    protected array $fetchArgs = [];

    protected string $sqlStatement = '';
    protected array $columns = [];
    protected array $bound = [];

    protected array $where = [];
    protected array $orderBy = [];
    protected array $limit = [];
    protected array $join  = [];

    protected string $implodeComma = ',';

    public function __construct(array $config, PDO $pdo)
    {
        $this->config = $config;

        // merge config
        foreach (['tablename', 'primaryColumn', 'fetchClass', 'throwException', 'errorFormat'] as $variable) {
            $this->$variable = $this->config[$variable] ?? $this->$variable;
        }

        $this->pdo = $pdo;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->reset();
    }

    public function hasError(): bool
    {
        return !empty($this->errorCode);
    }

    public function error(): string
    {
        return $this->errorFormat();
    }

    public function errors(): array
    {
        return [$this->errorFormat()];
    }

    public function errorFormat(string $format = null): string
    {
        $format = $format ?? $this->errorFormat;

        return sprintf($format, $this->errorCode, $this->errorMsg);
    }

    public function getLast(): array
    {
        return [
            'sql' => $this->lastSQL,
            'args' => $this->lastArgs,
        ];
    }

    public function reset(): self
    {
        $this->pdoStatement = new PDOStatement();

        $this->errorCode = '';
        $this->errorMsg = '';

        $this->where = [];
        $this->orderBy = [];
        $this->limit = [];
        $this->join = [];

        $this->lastSQL = '';
        $this->lastArgs = [];

        $this->columns = [];
        $this->bound = [];

        return $this;
    }

    /**
     * returns SQL string
     */
    public function build(): string
    {
        $builder = new StringBuilder();

        switch ($this->sqlStatement) {
            case 'select':
                $builder->append('SELECT', $this->getSelectColumns(), $this->getFrom(), $this->getJoins(), $this->getWhere(), $this->getOrderBy(), $this->getLimit());
                break;
            case 'insert':
                $builder->append('INSERT', $this->getInto(), $this->getInsertColumns(), 'VALUES', $this->getInsertValues());
                break;
            case 'update':
                $builder->append('UPDATE', $this->getTable(), 'SET', $this->getUpdateSet(), $this->getWhere(), $this->getLimit());
                break;
            case 'delete':
                $builder->append('DELETE', $this->getFrom(), $this->getWhere(), $this->getLimit());
                break;
            default:
                throw new InvalidValue('Unknown SQL statement "' . $this->sqlStatement . '".');
        }

        return trim($builder->get());
    }

    /**
     * builds the query and runs it
     */
    public function execute(): self
    {
        $this->query($this->build(), $this->boundValues());

        return $this;
    }

    public function column(int $column = 0): mixed
    {
        return $this->pdoStatement->fetchColumn($column);
    }

    public function row(): mixed
    {
        return $this->pdoStatement->fetch();
    }

    public function rows(): array
    {
        return $this->pdoStatement->fetchAll();
    }

    public function keyPair(): array
    {
        return $this->pdoStatement->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function lastInsertId(bool $raw = false): mixed
    {
        return ($raw) ? $this->pdo->lastInsertId() : (int)$this->pdo->lastInsertId();
    }

    public function rowCount(): mixed
    {
        return $this->pdoStatement->rowCount();
    }

    public function set(array|string $arg1, mixed $value = null, bool $isRaw = false): self
    {
        if (is_array($arg1)) {
            foreach ($arg1 as $column => $value) {
                $this->set($column, $value, $isRaw);
            }
        } else {
            $this->columns[] = $isRaw ? ['raw' => $arg1] : ['column' => $arg1, 'value' => $value];
        }

        return $this;
    }

    public function setRaw(array|string $arg1, mixed $value = null): self
    {
        return $this->set($arg1, $value, true);
    }

    public function value(string $name, mixed $value): self
    {
        return $this->set($name, $value, false);
    }

    public function valueRaw(string $name, mixed $value): self
    {
        return $this->set($name, $value, true);
    }

    public function values(array $array): self
    {
        return $this->set($array);
    }

    public function valuesRaw(array $array): self
    {
        return $this->set($array, null, true);
    }


    /**
     * Add a bound parameter identifier and value
     */
    public function bindValue(string $column, $value): self
    {
        $this->bound[$this->escapeColumnForBind($column)] = $value;

        return $this;
    }

    /**
     * Returns an array of bound parameter identifiers and there values
     */
    public function boundValues(): array
    {
        return $this->bound;
    }

    public function select(array|string $columns = '*'): self
    {
        $this->reset();

        $this->sqlStatement = 'select';

        // convert to array
        if (is_string($columns)) {
            $columns = explode(',', $columns);
        }

        foreach ($columns as $column) {
            if (trim($column) == '*') {
                $this->columns[] = ['raw' => '*'];
            } else {
                $this->columns[] = ['column' => $column];
            }
        }

        return $this;
    }

    public function update(string $tablename = ''): self
    {
        $this->reset();

        $this->sqlStatement = 'update';

        return $this->table($tablename);
    }

    public function insert(string $tablename = ''): self
    {
        $this->reset();

        $this->sqlStatement = 'insert';

        return $this->table($tablename);
    }

    public function delete(string $tablename = ''): self
    {
        $this->reset();

        $this->sqlStatement = 'delete';

        return $this->table($tablename);
    }

    public function where(string $column, $operator, $value = null): self
    {
        if ($value === null) {
            // if all 3 args not provided then assume it's a equals
            $this->whereEqual($column, $operator);
        } else {
            $this->where[] = ['column' => $column, 'operator' => $operator, 'value' => $value];
        }

        return $this;
    }

    public function whereEqual(string $column, $value): self
    {
        $this->where($column, '=', $value);

        return $this;
    }

    public function whereIsNull(string $column): self
    {
        $this->whereColumnRaw($column, 'IS NULL');

        return $this;
    }

    public function whereIsNotNull(string $column): self
    {
        $this->whereColumnRaw($column, 'IS NOT NULL');

        return $this;
    }

    public function wherePrimary($value): self
    {
        $tablename = substr($this->tablename, strrpos(' ' . $this->tablename, ' '));

        $this->whereEqual($tablename . '.' . $this->primaryColumn, $value);

        return $this;
    }

    public function and(string $column = null, mixed $value = null): self
    {
        // if column and value are set it's a join "and"
        if ($column !== null && $value !== null) {
            $this->join[] = ['column' => $column, 'value' => $value, 'bool' => 'AND', 'operator' => '='];
        } else {
            // if not it's a where "and"
            $this->whereRaw('AND');
        }

        return $this;
    }

    public function or(string $column = null, mixed $value = null): self
    {
        // if column and value are set it's a join "or"
        if ($column !== null && $value !== null) {
            $this->join[] = ['column' => $column, 'value' => $value, 'bool' => 'OR', 'operator' => '='];
        } else {
            // if not it's a where "OR"
            $this->whereRaw('OR');
        }

        return $this;
    }

    public function not(): self
    {
        $this->whereRaw('NOT');

        return $this;
    }

    public function groupStart(): self
    {
        $this->whereRaw('(');

        return $this;
    }

    public function groupEnd(): self
    {
        $this->whereRaw(')');

        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $builder = new StringBuilder($this->implodeComma);

        foreach ($values as $index => $value) {
            $identifier = $column . '_IN_' . $index;

            $builder->append(':' . $identifier);

            $this->bindValue($identifier, $value);
        }

        $this->whereColumnRaw($column, $builder->get('IN (', ')'));

        return $this;
    }

    /**
     * Examples
     * whereColumnRaw('foo','IS NULL');
     * whereColumnRaw('Price','NOT BETWEEN :priceStart AND :priceEnd',['priceStart'=>10,'priceEnd=>'20]);
     */
    public function whereColumnRaw(string $column, string $append, $value = null): self
    {
        return $this->whereRaw($this->escapeTableColumn($column) . ' ' . $append, $value);
    }

    public function whereRaw(string $raw, array $value = null): self
    {
        $where['raw'] = $raw;

        if (is_array($value)) {
            foreach ($value as $column => $value) {
                $this->bindValue($column, $value);
            }
        }

        $this->where[] = $where;

        return $this;
    }

    public function limit(int $limit, int $offset = -1): self
    {
        // we can only have 1 limit
        $this->limit = ['limit' => $limit, 'offset' => $offset];

        return $this;
    }

    public function limitByPage(int $pageNumber, int $limit): self
    {
        return $this->limit($limit, $this->getPagingOffset($pageNumber, $limit));
    }

    public function orderBy(string $column, string $dir = ''): self
    {
        // some basic shorthand
        $shorthand = [
            'd' => 'DESC',
            'a' => 'ASC',
            'az' => 'DESC',
            'za' => 'ASC'
        ];

        $dir = $shorthand[$dir] ?? strtoupper($dir);

        $this->orderBy[$column] = $dir;

        return $this;
    }

    public function join(string $joinTable, string $on, string $left, string $right): self
    {
        $this->join[$left . $on . $right] = ['on' => strtoupper($on), 'tablename' => $joinTable, 'left' => $left, 'right' => $right];

        return $this;
    }

    public function innerJoin(string $joinTable, string $left, string $right): self
    {
        $this->join($joinTable, 'INNER', $left, $right);

        return $this;
    }

    public function leftJoin(string $joinTable, string $left, string $right): self
    {
        $this->join($joinTable, 'LEFT', $left, $right);

        return $this;
    }

    public function rightJoin(string $joinTable, string $left, string $right): self
    {
        $this->join($joinTable, 'RIGHT', $left, $right);

        return $this;
    }

    public function into(string $tablename = ''): self
    {
        // set table if provided
        return $this->table($tablename);
    }

    public function from(string $tablename = ''): self
    {
        // set table if provided
        return $this->table($tablename);
    }

    /**
     * SELECT column1, column2, ... FROM table_name;
     */
    public function getSelectColumns(): string
    {
        $builder = new StringBuilder($this->implodeComma);

        foreach ($this->columns as $record) {
            if (isset($record['raw'])) {
                $builder->append($record['raw']);
            } else {
                $builder->append($this->escapeTableColumn($record['column']));
            }
        }

        return $builder->get();
    }

    /**
     * INSERT INTO table_name (column1, column2, column3, ...) VALUES (value1, value2, value3, ...);
     */
    public function getInsertColumns(): string
    {
        $builder = new StringBuilder($this->implodeComma);

        foreach ($this->columns as $record) {
            $builder->append($this->escapeTableColumn($record['column']));
        }

        return $builder->get('(', ')');
    }

    /**
     * INSERT INTO table_name (column1, column2, column3, ...) VALUES (value1, value2, value3, ...);
     */
    public function getInsertValues(): string
    {
        $builder = new StringBuilder($this->implodeComma);

        foreach ($this->columns as $record) {
            if (isset($record['raw'])) {
                $builder->append($record['raw']);
            } else {
                $builder->append($this->escapeColumnForBind($record['column'], true));
            }

            $this->bindValue($record['column'], $record['value']);
        }

        return $builder->get('(', ')');
    }

    /**
     * UPDATE table_name SET column1 = value1, column2 = value2 WHERE condition;
     */
    public function getUpdateSet(): string
    {
        $builder = new StringBuilder($this->implodeComma);

        foreach ($this->columns as $record) {
            if (isset($record['raw'])) {
                $builder->append($record['raw']);
            } else {
                $builder->append($this->escapeTableColumn($record['column']) . ' = ' . $this->escapeColumnForBind($record['column'], true));
            }

            $this->bindValue($record['column'], $record['value']);
        }

        return $builder->get();
    }

    public function getFrom(): string
    {
        return 'FROM ' . $this->getTable();
    }

    public function getInto(): string
    {
        return 'INTO ' . $this->getTable();
    }

    public function getTable(): string
    {
        return trim($this->escapeTableColumn($this->tablename));
    }

    public function getJoins(): string
    {
        $builder = new StringBuilder();

        foreach ($this->join as $join) {
            if (isset($join['on'])) {
                $builder->append($join['on'], 'JOIN', $this->escapeTableColumn($join['tablename']), 'ON', $this->escapeTableColumn($join['left']), '=', $this->escapeTableColumn($join['right']));
            } else {
                $builder->append($join['bool'], $this->escapeTableColumn($join['column']), $join['operator'], $this->escapeColumnForBind($join['column'], true));

                $this->bindValue($join['column'], $join['value']);
            }
        }

        return $builder->getIfHas();
    }

    public function getWhere(): string
    {
        $builder = new StringBuilder();

        foreach ($this->where as $record) {
            // is it a "raw" value?
            if (isset($record['raw'])) {
                $builder->append($record['raw']);
            } else {
                $builder->append($this->escapeTableColumn($record['column']), $record['operator'], $this->escapeColumnForBind($record['column'], true));

                $this->bindValue($record['column'], $record['value']);
            }
        }

        return $builder->getIfHas('WHERE ');
    }

    public function getOrderBy(): string
    {
        $builder = new StringBuilder($this->implodeComma);

        foreach ($this->orderBy as $column => $dir) {
            $builder->append(trim($this->escapeTableColumn($column) . ' ' . $dir));
        }

        return $builder->getIfHas('ORDER BY ');
    }

    public function getLimit(): string
    {
        $builder = new StringBuilder();

        if (isset($this->limit['limit'])) {
            if ($this->limit['offset'] == -1) {
                $builder->append($this->limit['limit']);
            } else {
                $builder->append($this->limit['limit'], 'OFFSET', $this->limit['offset']);
            }
        }

        return $builder->getIfHas('LIMIT ');
    }

    public function getPagingOffset(int $pageNumber, int $perPage): int
    {
        // returns offset for paging
        return ($pageNumber <= 1) ? 0 : ($pageNumber * $perPage - $perPage);
    }

    public function table(string $tablename = ''): self
    {
        if (!empty($tablename)) {
            $this->tablename = trim($tablename);
        }

        return $this;
    }

    public function query(string $sql, array $args = []): PDOStatement
    {
        $this->reset();

        $this->lastSQL = trim($sql);
        $this->lastArgs = $args;

        try {
            if (empty($args)) {
                $this->pdoStatement = $this->pdo->query($sql);

                $this->updateFetchMode();
            } else {
                $this->pdoStatement = $this->pdo->prepare($sql);

                $this->updateFetchMode();

                //check if args is associative or sequential?
                $isAssociative = (array() === $args) ? false : array_keys($args) !== range(0, count($args) - 1);

                if ($isAssociative) {
                    foreach ($args as $identifier => $value) {
                        if (is_int($value)) {
                            $this->pdoStatement->bindValue(':' . $identifier, $value, PDO::PARAM_INT);
                        } else {
                            $this->pdoStatement->bindValue(':' . $identifier, $value);
                        }
                    }

                    $this->pdoStatement->execute();
                } else {
                    $this->pdoStatement->execute($args);
                }
            }
        } catch (Throwable $e) {
            $this->captureError($e);
        }

        return $this->pdoStatement;
    }

    public function throwExceptions(bool $bool): self
    {
        $this->throwException = $bool;

        return $this;
    }

    public function captureError(Throwable $e): void
    {
        $this->errorCode = $e->getCode();
        $this->errorMsg = $e->getMessage();

        if ($this->throwException) {
            throw new SqlBuilderException((string)$e->getMessage(), (int)$e->getCode());
        }
    }

    public function setFetchMode(int|string $fetchMode, array $fetchArgs = []): self
    {
        if (is_integer($fetchMode)) {
            $this->fetchMode = $fetchMode;
            $this->fetchClass = null;
        } else {
            $this->fetchMode = PDO::FETCH_CLASS;
            $this->fetchClass = $fetchMode;
            $this->fetchArgs = $fetchArgs;
        }

        return $this;
    }

    public function updateFetchMode(): self
    {
        if ($this->fetchMode == PDO::FETCH_CLASS) {
            $this->pdoStatement->setFetchMode(PDO::FETCH_CLASS, $this->fetchClass, $this->fetchArgs);
        } else {
            $this->pdoStatement->setFetchMode($this->fetchMode);
        }

        return $this;
    }

    public function escapeTableColumn(string $input): string
    {
        $input = trim($input);

        if (preg_match('/(?<tablecolumn>.*) (?<as>as) (?<alias>.*)/i', $input, $matches, 0, 0)) {
            $output = $this->escapeTableColumn($matches['tablecolumn']) . ' AS ' . $this->escapeTableColumn($matches['alias']);
        } elseif (strpos($input, ' ') !== false) {
            list($a, $b) = explode(' ', $input, 2);
            $output = $this->escapeTableColumn($a) . ' AS ' . $this->escapeTableColumn($b);
        } elseif (strpos($input, '.') !== false) {
            // separate on spaces trim and add ` marks. then rejoin on .
            $output = implode('.', array_map(function ($s) {
                return '`' . trim($s) . '`';
            }, explode('.', $input)));
        } else {
            $output = '`' . trim($input) . '`';
        }

        return trim($output);
    }

    public function escapeColumnForBind(string $input, bool $appendIdentifier = false): string
    {
        $input = str_replace([':', '`'], '', trim($input));

        if (strpos($input, '.') !== false) {
            list($table, $column) = explode('.', $input, 2);

            $input = 'table_' . $table . '_column_' . $column;
        } else {
            $input = 'column_' . $input;
        }

        return ($appendIdentifier) ? ':' . $input : $input;
    }
}
