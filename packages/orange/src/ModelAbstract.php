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
        $this->errorCode = '';
        $this->errorMsg = '';
        $this->hasError = false;

        $this->lastSQL = '';
        $this->lastArgs = [];

        return $this;
    }

    protected function _getById($id, int $fetchMode = -1)
    {
        return $this->_row("SELECT * FROM " . $this->_table() . " WHERE " . $this->_escapeTableColumn($this->primaryColumn) . " = ?", [$id], $fetchMode);
    }

    protected function _getColumnById(string $column, $id, int $fetchMode = -1)
    {
        return $this->_row("SELECT " . $this->_escapeTableColumn($column) . " FROM " . $this->_table() . " WHERE " . $this->_escapeTableColumn($this->primaryColumn) . " = ?", [$id], $fetchMode);
    }

    protected function _getValueById(string $column, $id)
    {
        $record = $this->_row("SELECT " . $this->_escapeTableColumn($column) . " FROM " . $this->_table() . " WHERE " . $this->_escapeTableColumn($this->primaryColumn) . " = ?", [$id], PDO::FETCH_ASSOC);

        return $record[$column];
    }

    protected function _row(string $sql, array $args = [], int $fetchMode = -1)
    {
        $this->_run($sql, $args);

        $this->_setFetchMode($fetchMode);

        return $this->PDOStatement->fetch();
    }

    protected function _rows(string $sql, array $args = [], int $fetchMode = -1)
    {
        $this->_run($sql, $args);

        $this->_setFetchMode($fetchMode);

        return $this->PDOStatement->fetchAll();
    }

    protected function _insert(array $data): self
    {
        //add columns into comma seperated string
        $columns = implode(',', array_keys($data));

        //get values
        $values = array_values($data);

        $placeholders = array_map(function ($val) {
            return '?';
        }, array_keys($data));

        //convert array into comma seperated string
        $placeholders = implode(',', array_values($placeholders));

        $this->_run("INSERT INTO " . $this->_table() . " ($columns) VALUES ($placeholders)", $values);

        return $this;
    }

    protected function _lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    protected function _updateById(array $data, $id): self
    {
        return $this->_update($data, [$this->primaryColumn => $id]);
    }

    protected function _update(array $data, array $where): self
    {
        //collect the values from data and where
        $values = [];

        //setup fields
        $fieldDetails = [];

        foreach ($data as $key => $value) {
            $fieldDetails[] = $key . " = ?";
            $values[] = $value;
        }

        //setup where 
        $whereDetails = [];

        foreach ($where as $key => $value) {
            $whereDetails[] = "`" . $key . "` = ?";
            $values[] = $value;
        }

        $this->PDOStatement = $this->_run("UPDATE " . $this->_table() . " SET " . implode(',', $fieldDetails) . " WHERE " . implode(' AND ', $whereDetails), $values);

        return $this;
    }

    protected function _lastAffectedRows()
    {
        return $this->PDOStatement->rowCount();
    }

    protected function _delete(array $where, int $limit = 1): self
    {
        //collect the values from collection
        $values = array_values($where);

        //setup where 
        $whereDetails = [];

        foreach (array_keys($where) as $key) {
            $whereDetails[] = "`" . $key . "` = ?";
        }

        //if limit is a number use a limit on the query
        if (is_numeric($limit)) {
            $limit = " LIMIT " . $limit;
        }

        $this->PDOStatement = $this->_run("DELETE FROM " . $this->_table() . " WHERE " . implode(' AND ', $whereDetails) . $limit, $values);

        return $this;
    }

    protected function _deleteById($id): self
    {
        return $this->_delete([$this->primaryColumn => $id], 1);
    }

    protected function _run(string $sql, array $args = []): PDOStatement
    {
        $this->_reset();

        $this->lastSQL = $sql;
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
                        $this->PDOStatement->bindValue(":$key", $value, PDO::PARAM_INT);
                    } else {
                        $this->PDOStatement->bindValue(":$key", $value);
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

    protected function _columns(array $columns): string
    {
        $escapedColumns = [];

        foreach ($columns as $column) {
            $escapedColumns[] = $this->_escapeTableColumn($column);
        }

        return implode(',', $escapedColumns);
    }

    protected function _table(string $tablename = null): string
    {
        $tablename = ($tablename) ?? $this->tablename;

        return $this->_escapeTableColumn($tablename);
    }

    protected function _primary(string $column = null): string
    {
        $column = ($column) ?? $this->primaryColumn;

        return $this->_escapeTableColumn($column);
    }

    protected function _escapeTableColumn(string $input): string
    {
        $output = '`' . $input . '`';

        if (strpos($input, '.') !== false) {
            list($a, $b) = explode('.', $input);

            $output = '`' . $a . '`.`' . $b . '`';
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
        ];
    }
}
