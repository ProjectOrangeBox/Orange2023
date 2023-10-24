<?php

declare(strict_types=1);

namespace peels\model;

use PDO;
use Throwable;
use PDOStatement;
use dmyers\orange\exceptions\SqlBuilderException;

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
    protected PDO $pdo;
    protected PDOStatement $PDOStatement;
    protected array $config = [];

    protected string $errorCode = '';
    protected string $errorMsg = '';
    protected string $errorFormat = '[%1$s] %2$s';
    protected bool $throwException = false;

    protected string $lastSQL = '';
    protected array $lastArgs = [];

    // required in extending class
    protected string $tablename = '';
    protected string $primaryColumn = '';

    protected int $defaultFetchType = PDO::FETCH_ASSOC;
    protected string $fetchClass = '';

    protected string $method = '';
    protected array $columns = [];
    protected array $values = [];

    protected array $where = [];
    protected array $orderBy = [];
    protected array $limit = [];
    protected array $join  = [];

    public function __construct(array $config, PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->config = $config;

        $this->primaryColumn = $this->config['primaryColumn'] ?? $this->primaryColumn;
        $this->tablename = $this->config['tablename'] ?? $this->tablename;
        $this->defaultFetchType = $this->config['defaultFetchType'] ?? $this->defaultFetchType;
        $this->fetchClass = $this->config['fetchClass'] ?? $this->fetchClass;
        $this->throwException = $this->config['ThrowException'] ?? $this->throwException;
        $this->errorFormat = $this->config['errorFormat'] ?? $this->errorFormat;

        $this->reset();
    }

    public function hasError(): bool
    {
        return (!empty($this->errorCode));
    }

    public function error(): string
    {
        return $this->errorFormat();
    }

    /**
     * Used by the Error Class "collector"
     */
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
        $this->PDOStatement = new PDOStatement();

        $this->errorCode = '';
        $this->errorMsg = '';

        $this->where = [];
        $this->orderBy = [];
        $this->limit = [];
        $this->join = [];

        $this->lastSQL = '';
        $this->lastArgs = [];

        $this->columns = [];
        $this->values = [];

        return $this;
    }

    /**
     * returns SQL string
     */
    public function build(): string
    {
        $sql = '';

        switch ($this->method) {
            case 'select':
                $sql = 'SELECT' . $this->getSelectColumns() .  $this->getFrom() . ' ' . $this->getJoins() . $this->getWhere() . $this->getOrderBy() . $this->getLimit();
                break;
            case 'insert':
                $sql = 'INSERT ' . $this->getInto() . $this->getInsertColumns() . ' VALUES ' . $this->getInsertValues();
                break;
            case 'update':
                $sql = 'UPDATE' . $this->getTable() . $this->getUpdateSet() . $this->getWhere() . $this->getLimit();
                break;
            case 'delete':
                $sql = 'DELETE' . $this->getFrom() . $this->getWhere() . $this->getLimit();
                break;
        }

        return trim($sql);
    }

    /**
     * builds the query and runs it
     */
    public function run($fetchMode = -1): mixed
    {
        $this->query($this->build(), $this->boundValues(), $fetchMode);

        return $this;
    }

    public function column(int $column = 0): mixed
    {
        return $this->PDOStatement->fetchColumn($column);
    }

    public function row(): mixed
    {
        return $this->PDOStatement->fetch();
    }

    public function rows(): mixed
    {
        return $this->PDOStatement->fetchAll();
    }

    public function lastInsertId(): mixed
    {
        return $this->pdo->lastInsertId();
    }

    public function rowCount(): mixed
    {
        return $this->PDOStatement->rowCount();
    }

    public function connection(): PDOStatement
    {
        return $this->PDOStatement;
    }

    public function set(array|string $arg1, mixed $value = null, bool $isRaw = false): self
    {
        if (is_string($arg1)) {
            $this->set($arg1, $value);
        } else {
            $key = ($isRaw) ? 'raw' : 'column';

            foreach ($arg1 as $column => $value) {
                if ($value === null) {
                    $this->columns[] = [
                        $key => $column
                    ];
                } else {
                    $this->columns[] = [
                        $key => $column,
                        'value' => $value
                    ];
                }
            }
        }

        return $this;
    }

    public function setRaw(array|string $arg1, mixed $value = null): self
    {
        return $this->set($arg1, $value, true);
    }

    public function values(array $array): self
    {
        return $this->set($array);
    }

    public function value(string $name, mixed $value): self
    {
        return $this->set($name, $value);
    }

    public function valuesRaw(array $array): self
    {
        return $this->set($array, null, true);
    }

    public function valueRaw(string $name, mixed $value): self
    {
        return $this->set($name, $value, true);
    }

    /**
     * SELECT column1, column2, ... FROM table_name;
     */
    public function getSelectColumns(): string
    {
        $columNames = [];

        foreach ($this->columns as $record) {
            if (isset($record['raw'])) {
                $columNames[] = $record['raw'];
            } else {
                $columNames[] = $this->escapeTableColumn($record['column']);
            }
        }

        return ' ' . implode(',', $columNames);
    }

    /**
     * INSERT INTO table_name (column1, column2, column3, ...) VALUES (value1, value2, value3, ...);
     */
    public function getInsertColumns(): string
    {
        $columNames = [];

        foreach ($this->columns as $record) {
            if (isset($record['raw'])) {
                $columNames[] = $record['raw'];
            } else {
                $columNames[] = $this->escapeTableColumn($record['column']);
            }
        }

        return ' (' . implode(',', $columNames) . ')';
    }

    /**
     * INSERT INTO table_name (column1, column2, column3, ...) VALUES (value1, value2, value3, ...);
     */
    public function getInsertValues(): string
    {
        $fieldDetails = [];

        foreach ($this->columns as $record) {
            $fieldDetails[] = '?';

            $this->bindValue($record['value']);
        }

        return ' (' . implode(',', $fieldDetails) . ')';
    }

    /**
     * UPDATE table_name SET column1 = value1, column2 = value2 WHERE condition;
     */
    public function getUpdateSet(): string
    {
        $fieldDetails = [];

        foreach ($this->columns as $record) {
            if (isset($record['raw'])) {
                $fieldDetails[] = $record['raw'] . ' = ?';
            } else {
                $fieldDetails[] = $this->escapeTableColumn($record['column']) . ' = ?';
            }

            $this->bindValue($record['value']);
        }

        return ' SET ' . implode(' , ', $fieldDetails);
    }


    public function bindValue($value): self
    {
        $this->values[] = $value;

        return $this;
    }

    public function boundValues(): array
    {
        return $this->values;
    }

    public function select(array|string $columns = '*'): self
    {
        $this->reset();

        $this->method = 'select';

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

        $this->method = 'update';

        return $this->setTableName($tablename);
    }

    public function insert(string $tablename = ''): self
    {
        $this->reset();

        $this->method = 'insert';

        return $this->setTableName($tablename);
    }

    public function delete(string $tablename = ''): self
    {
        $this->reset();

        $this->method = 'delete';

        return $this->setTableName($tablename);
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

        return $output;
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

        $this->where($tablename . '.' . $this->primaryColumn, '=', $value);

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

    /**
     * whereColumnRaw('columnname','in (?,?,?,?)',[12,23,45,789]);
     * whereColumnRaw('foo','IS NULL');
     * whereColumnRaw('Price','NOT BETWEEN ? AND ?',[10,20]);
     */
    public function whereColumnRaw(string $column, string $append, $value = null): self
    {
        return $this->whereRaw($this->escapeTableColumn($column) . ' ' . $append, $value);
    }

    public function whereRaw(string $raw, $value = null): self
    {
        $where['raw'] = $raw;

        if ($value !== null) {
            $where['value'] = $value;
        }

        $this->where[] = $where;

        return $this;
    }

    public function getWhere(): string
    {
        $sql = '';

        foreach ($this->where as $record) {
            // is it a "raw" value?
            if (isset($record['raw'])) {
                $sql .= ' ' . $record['raw'] . ' ';
            } else {
                $sql .= $this->escapeTableColumn($record['column']) . ' ' . $record['operator'] . ' ?';
            }

            if (isset($record['value'])) {
                if (is_array($record['value'])) {
                    foreach ($record['value'] as $v) {
                        $this->bindValue($v);
                    }
                } else {
                    $this->bindValue($record['value']);
                }
            }
        }

        return (!empty($sql)) ? ' WHERE ' . trim($sql) : '';
    }

    public function limit(int $limit, int $count = -1): self
    {
        $this->limit[] = ['limit' => $limit, 'count' => $count];

        return $this;
    }

    public function getLimit(): string
    {
        $sql = '';

        foreach ($this->limit as $record) {
            if ($record['count'] == -1) {
                $sql = ' LIMIT ' . $record['limit'];
            } else {
                $sql = ' LIMIT ' . $record['limit'] . ',' . $record['count'];
            }
        }

        return $sql;
    }

    public function orderBy(string $columnName, string $dir = ''): self
    {
        // some basic shorthand
        $shorthand = [
            'd' => 'DESC',
            'a' => 'ASC',
            'az' => 'DESC',
            'za' => 'ASC'
        ];

        $dir = $shorthand[$dir] ?? strtoupper($dir);

        $this->orderBy[$columnName] = $dir;

        return $this;
    }

    public function getOrderBy(): string
    {
        $sql = [];

        foreach ($this->orderBy as $column => $dir) {
            $sql[] = trim($this->escapeTableColumn($column) . ' ' . $dir);
        }

        return (!empty($sql)) ? ' ORDER BY ' . implode(', ', $sql) : '';
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

    public function getJoins(): string
    {
        $sql = [];

        foreach ($this->join as $join) {
            if (isset($join['on'])) {
                $sql[] = $join['on'] . ' JOIN ' . $this->escapeTableColumn($join['tablename']) . ' ON ' . $this->escapeTableColumn($join['left']) . '=' . $this->escapeTableColumn($join['right']);
            } else {
                $sql[] = $join['bool'] . ' ' . $this->escapeTableColumn($join['column']) . ' ' . $join['operator'] . ' ?';

                $this->bindValue($join['value']);
            }
        }

        return (!empty($sql)) ? ' ' . implode(' ', $sql) : '';
    }

    public function into(string $tablename = ''): self
    {
        return $this->setTableName($tablename);
    }

    public function getInto(): string
    {
        return ' INTO' . $this->getTable();
    }

    public function from(string $tablename = ''): self
    {
        return $this->setTableName($tablename);
    }

    public function getFrom(): string
    {
        return ' FROM' . $this->getTable();
    }

    public function table(string $tablename = ''): self
    {
        return $this->setTableName($tablename);
    }

    public function getTable(): string
    {
        return ' ' . $this->escapeTableColumn($this->tablename);
    }

    public function query(string $sql, array $args = [], int $fetchMode = -1): PDOStatement
    {
        $this->reset();

        $this->lastSQL = trim($sql);
        $this->lastArgs = $args;

        try {
            if (empty($args)) {
                $this->PDOStatement = $this->pdo->query($sql);

                $this->setFetchMode($fetchMode);
            } else {
                $this->PDOStatement = $this->pdo->prepare($sql);

                $this->setFetchMode($fetchMode);

                //check if args is associative or sequential?
                $isAssociative = (array() === $args) ? false : array_keys($args) !== range(0, count($args) - 1);

                if ($isAssociative) {
                    foreach ($args as $key => $value) {
                        if (is_int($value)) {
                            $this->PDOStatement->bindValue(':' . $key, $value, PDO::PARAM_INT);
                        } else {
                            $this->PDOStatement->bindValue(':' . $key, $value);
                        }
                    }

                    $this->PDOStatement->execute();
                } else {
                    $this->PDOStatement->execute($args);
                }
            }
        } catch (Throwable $e) {
            $this->captureError($e);
        }

        return $this->PDOStatement;
    }

    public function captureError(Throwable $e): void
    {
        $this->errorCode = (string)$e->getCode();
        $this->errorMsg = $e->getMessage();

        if ($this->throwException) {
            throw new SqlBuilderException($this->errorFormat(), 500);
        }
    }

    public function setFetchMode(int $fetchMode = -1): void
    {
        // if it's not select it doesn't really matter
        if ($fetchMode == PDO::FETCH_CLASS || $this->defaultFetchType == PDO::FETCH_CLASS) {
            $class = (!empty($this->fetchClass)) ? $this->fetchClass : null;

            $this->PDOStatement->setFetchMode(PDO::FETCH_CLASS, $class);
        } elseif ($fetchMode != -1) {
            $this->PDOStatement->setFetchMode($fetchMode);
        } else {
            $this->PDOStatement->setFetchMode($this->defaultFetchType);
        }
    }

    public function setTableName(string $tablename): self
    {
        if ($tablename !== '') {
            $this->tablename = $tablename;
        }

        return $this;
    }
}
