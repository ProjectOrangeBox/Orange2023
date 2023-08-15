<?php

declare(strict_types=1);

namespace dmyers\orange;

use PDO;
use Exception;
use Throwable;
use PDOStatement;

abstract class ModelAbstract
{
    protected PDO $pdo;
    protected PDOStatement $PDOStatement;
    protected array $config = [];

    protected bool $hasError = false;
    protected string $errorCode = '';
    protected string $errorMsg = '';

    protected string $lastSQL = '';
    protected array $lastArgs = [];

    // required in extending class
    protected string $tablename = '';
    protected string $primaryColumn = '';

    protected int $defaultFetchType = PDO::FETCH_ASSOC;
    protected string $fetchClass = '';

    protected array $where = [];
    protected array $values = [];
    protected array $orderBy = [];
    protected array $limit = [];
    protected array $join  = [];

    public function __construct(PDO $pdo, array $config = [])
    {
        // inject your connection when building your service
        $this->pdo = $pdo;

        $this->config = $config;

        $this->_reset();
    }

    public function hasError(): bool
    {
        return $this->hasError;
    }

    /**
     * Can also be "collected" by the Error Class
     */
    public function errors(): array
    {
        return [
            'code' => $this->errorCode,
            'msg' => $this->errorMsg,
        ];
    }

    /* protected */
    protected function _reset(): self
    {
        // cleared before each _run()
        unset($this->PDOStatement);

        $this->hasError = false;
        $this->errorCode = '';
        $this->errorMsg = '';

        $this->values = [];

        $this->where = [];
        $this->orderBy = [];
        $this->limit = [];
        $this->join = [];

        $this->lastSQL = '';
        $this->lastArgs = [];

        return $this;
    }

    protected function _getFrom(): string
    {
        return 'FROM ' . $this->_tablename();
    }

    protected function _getById($id, string $columns = '*', int $fetchMode = -1)
    {
        return $this->_where($this->primaryColumn, $id)->_row($this->_getSelectSql($columns), $this->_getValues(), $fetchMode);
    }

    protected function _getColumnById(string $column, $id, int $fetchMode = -1)
    {
        return $this->_where($this->primaryColumn, $id)->_row($this->_getSelectSql($column), $this->_getValues(), $fetchMode);
    }

    protected function _getValueById(string $column, $id)
    {
        $record = $this->_where($this->primaryColumn, $id)->_row($this->_getSelectSql($column), $this->_getValues(), PDO::FETCH_ASSOC);

        return ($record) ? $record[$column] : null;
    }

    protected function _select(string $columns = '*', int $fetchMode = -1): array|bool
    {
        return $this->_run($this->_getSelectSql($columns), $this->_getValues(), $fetchMode)->fetchAll();
    }

    protected function _getSelectSql(string $columns = '*'): string
    {
        return 'SELECT ' . $this->_columns($columns) . ' ' . $this->_getFrom() . ' ' . $this->_getJoin() . ' ' . $this->_getWhere() . ' ' . $this->_getOrderBy() . ' ' . $this->_getLimit();
    }

    protected function _row(string $sql, array $args = [], int $fetchMode = -1)
    {
        return $this->_run($sql, $args, $fetchMode)->fetch();
    }

    protected function _rows(string $sql, array $args = [], int $fetchMode = -1): array|bool
    {
        return $this->_run($sql, $args, $fetchMode)->fetchAll();
    }

    protected function _existsById($id): bool
    {
        return $this->_where($this->primaryColumn, $id)->_limit(1)->_exists($this->_getSelectSql($this->primaryColumn), $this->_getValues());
    }

    protected function _exists(string $sql, array $args = []): bool
    {
        return ($this->_row($sql, $args, PDO::FETCH_ASSOC) === false) ? false : true;
    }

    protected function _insert(array $data): self
    {
        $placeholders = [];

        foreach ($data as $column => $value) {
            $placeholders[$column] = '?';

            $this->_addValue($value);
        }

        $this->_run('INSERT INTO ' . $this->_tablename() . ' (' . $this->_columns(array_keys($data)) . ') VALUES (' . implode(',', $placeholders) . ')', $this->_getValues());

        return $this;
    }

    protected function _lastInsertId(): string|false
    {
        return $this->pdo->lastInsertId();
    }

    protected function _updateById(array $data, $id): self
    {
        $this->_where($this->primaryColumn, $id);

        return $this->_update($data);
    }

    protected function _update(array $data): self
    {
        //setup fields
        $fieldDetails = [];

        foreach ($data as $key => $value) {
            $fieldDetails[] = $this->_escapeTableColumn($key) . ' = ?';
            $this->_addValue($value);
        }

        $this->PDOStatement = $this->_run('UPDATE ' . $this->_tablename() . ' SET ' . implode(',', $fieldDetails) . $this->_getWhere() . $this->_getLimit(), $this->_getValues());

        return $this;
    }

    protected function _lastAffectedRows(): int
    {
        return $this->PDOStatement->rowCount();
    }

    protected function _delete(): self
    {
        $this->PDOStatement = $this->_run('DELETE ' . $this->_getFrom() . $this->_getWhere() . $this->_getLimit(), $this->_getValues());

        return $this;
    }

    protected function _deleteById($id): self
    {
        $this->_where($this->primaryColumn, $id);

        return $this->_delete();
    }

    protected function _addValue($value): self
    {
        $this->values[] = $value;

        return $this;
    }

    protected function _getValues(): array
    {
        return $this->values;
    }

    protected function _whereAndArray(array $where): self
    {
        end($where);
        $lastKey = key($where);

        foreach ($where as $key => $value) {
            $this->_where($key, $value);

            if ($key != $lastKey) {
                $this->_whereAnd();
            }
        }

        return $this;
    }

    protected function _where(string $column, $value): self
    {
        $this->where[] = [$column, $value];

        return $this;
    }

    protected function _whereAnd(): self
    {
        $this->_whereRaw('AND');

        return $this;
    }

    protected function _whereOr(): self
    {
        $this->_whereRaw('OR');

        return $this;
    }

    protected function _whereNot(): self
    {
        $this->_whereRaw('NOT');

        return $this;
    }

    protected function _whereIn(): self
    {
        $this->_whereRaw('IN');

        return $this;
    }

    protected function _whereBetween(): self
    {
        $this->_whereRaw('BETWEEN');

        return $this;
    }

    protected function _whereGroupStart(): self
    {
        $this->_whereRaw('(');

        return $this;
    }

    protected function _whereGroupEnd(): self
    {
        $this->_whereRaw(')');

        return $this;
    }

    protected function _whereRaw(string $raw): self
    {
        $this->where[] = [' ' . $raw . ' ', true];

        return $this;
    }

    protected function _getWhere(): string
    {
        $sql = '';

        foreach ($this->where as $record) {
            // is it a "raw" value?
            if ($record[1] === true) {
                $sql .= $record[0];
            } else {
                $sql .= $this->_escapeTableColumn($record[0]) . ' = ?';

                $this->_addValue($record[1]);
            }
        }

        return (!empty($sql)) ? 'WHERE ' . trim($sql) : '';
    }

    protected function _limit(int $limit, int $count = -1): self
    {
        $this->limit[] = [$limit, $count];

        return $this;
    }

    protected function _getLimit(): string
    {
        $sql = '';

        foreach ($this->limit as $record) {
            if ($record[1] == -1) {
                $sql = 'LIMIT ' . $record[0];
            } else {
                $sql = 'LIMIT ' . $record[0] . ',' . $record[1];
            }
        }

        return $sql;
    }

    protected function _orderBy(string $columnName, string $dir = ''): self
    {
        $this->orderBy[$columnName] = $dir;

        return $this;
    }

    protected function _getOrderBy(): string
    {
        $sql = [];

        foreach ($this->orderBy as $column => $dir) {
            $sql[] = trim($this->_escapeTableColumn($column) . ' ' . $dir);
        }

        return (!empty($sql)) ? 'ORDER BY ' . implode(', ', $sql) : '';
    }

    protected function _join(string $joinTable, string $on, string $left, string $right): self
    {
        $this->join[$left . $on . $right] = ['on' => strtoupper($on), 'tablename' => $joinTable, 'left' => $left, 'right' => $right];

        return $this;
    }

    protected function _joinInner(string $joinTable, string $left, string $right): self
    {
        $this->_join($joinTable, 'INNER',  $left, $right);

        return $this;
    }

    protected function _joinLeft(string $joinTable, string $left, string $right): self
    {
        $this->_join($joinTable, 'LEFT',  $left, $right);

        return $this;
    }

    protected function _joinRight(string $joinTable, string $left, string $right): self
    {
        $this->_join($joinTable, 'RIGHT',  $left, $right);

        return $this;
    }

    protected function _getJoin(): string
    {
        $sql = [];

        foreach ($this->join as $join) {
            $sql[] = $join['on'] . ' JOIN ' . $this->_escapeTableColumn($join['tablename']) . ' ON ' . $this->_escapeTableColumn($join['left']) . '=' . $this->_escapeTableColumn($join['right']);
        }

        return (!empty($sql)) ? implode(' ', $sql) : '';
    }

    protected function _run(string $sql, array $args = [], int $fetchMode = -1): PDOStatement
    {
        $this->_reset();

        $this->lastSQL = trim($sql);
        $this->lastArgs = $args;

        if (empty($args)) {
            try {
                $this->PDOStatement = $this->pdo->query($sql);
            } catch (Throwable $e) {
                $this->_captureError($e);
            }
        } else {
            $this->PDOStatement = $this->pdo->prepare($sql);

            //check if args is associative or sequential?
            $is_assoc = (array() === $args) ? false : array_keys($args) !== range(0, count($args) - 1);

            if ($is_assoc) {
                foreach ($args as $key => $value) {
                    if (is_int($value)) {
                        $this->PDOStatement->bindValue(':' . $key, $value, PDO::PARAM_INT);
                    } else {
                        $this->PDOStatement->bindValue(':' . $key, $value);
                    }
                }
                try {
                    $this->PDOStatement->execute();
                } catch (Throwable $e) {
                    $this->_captureError($e);
                }
            } else {
                try {
                    $this->PDOStatement->execute($args);
                } catch (Throwable $e) {
                    $this->_captureError($e);
                }
            }
        }

        $this->_setFetchMode($fetchMode);

        return $this->PDOStatement;
    }

    protected function _captureError(Throwable $e): void
    {
        $this->errorCode = $e->getCode();
        $this->errorMsg = $e->getMessage();

        $this->hasError = true;

        if (isset($this->config['throw error']) && $this->config['throw error'] == true) {
            throw new Exception($this->errorMsg . ' [' . $this->errorCode . ']', 500);
        }
    }

    protected function _setFetchMode(int $fetchMode = -1): PDOStatement
    {
        if ($fetchMode == -1 && !empty($this->fetchClass)) {
            $this->PDOStatement->setFetchMode(PDO::FETCH_CLASS, $this->fetchClass);
        } elseif ($fetchMode != -1) {
            $this->PDOStatement->setFetchMode($fetchMode);
        } else {
            $this->PDOStatement->setFetchMode($this->defaultFetchType);
        }

        return $this->PDOStatement;
    }

    protected function _columns($columns): string
    {
        // convert to array
        if (is_string($columns)) {
            $columns = explode(',', $columns);
        }

        $escapedColumns = [];

        foreach ($columns as $column) {
            // let's make sure they didn't add spaces
            $column = trim($column);

            $escapedColumns[] = ($column == '*') ? '*' : $this->_escapeTableColumn($column);
        }

        return implode(',', $escapedColumns);
    }

    protected function _tablename(): string
    {
        return $this->_escapeTableColumn($this->tablename);
    }

    protected function _escapeTableColumn(string $input): string
    {
        if (preg_match_all('/(?<tablecolumn>.*) (?<as>as) (?<alias>.*)/i', $input, $matches, PREG_SET_ORDER, 0)) {
            $output = $this->_escapeTableColumn($matches[0]['tablecolumn']) . ' AS ' . $this->_escapeTableColumn($matches[0]['alias']);
        } elseif (strpos($input, ' ') !== false) {
            list($a, $b) = explode(' ', $input, 2);
            $output = $this->_escapeTableColumn($a) . ' AS ' . $this->_escapeTableColumn($b);
        } elseif (preg_match_all('/(?<table>.*)\.(?<column>.*)/i', $input, $matches, PREG_SET_ORDER, 0)) {
            $output = '`' . trim($matches[0]['table']) . '`.`' . trim($matches[0]['column']) . '`';
        } else {
            $output = '`' . trim($input) . '`';
        }

        return $output;
    }

    public function __debugInfo(): array
    {
        return [
            'config' => $this->config,
            'hasError' => $this->hasError,
            'errorCode' => $this->errorCode,
            'errorMsg' => $this->errorMsg,

            'lastSQL' => $this->lastSQL,
            'lastArgs' => $this->lastArgs,

            'tablename' => $this->tablename,
            'primaryColumn' => $this->primaryColumn,

            'defaultFetchType' => $this->defaultFetchType,
            'fetchClass' => $this->fetchClass,

            'values' => $this->values,

            'where' => $this->where,
            'orderby' => $this->orderBy,
            'limit' => $this->limit,
            'join' => $this->join,
        ];
    }
}
