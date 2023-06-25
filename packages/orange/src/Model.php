<?php

declare(strict_types=1);

namespace dmyers\orange;

use PDO;

abstract class Model
{
    protected PDO $pdo;
    protected string $lastSql = '';
    protected int $errorCode = 0;
    protected string $errorInfo = '';
    protected bool $hasError = false;
    protected string $tablename = '';
    protected string $join = '';
    protected string $limit = '';
    protected string $orderby = '';
    protected $responds = null;

    // if used/different set in child
    protected string $primaryColumn = 'id';

    public function __construct(PDO $pdo)
    {
        // inject your connection when building your service
        $this->pdo = $pdo;

        $this->reset();
    }

    protected function reset(): self
    {
        $this->errorCode = 0;
        $this->errorInfo = '';
        $this->hasError = false;
        $this->join = '';
        $this->limit = '';
        $this->orderby = '';

        return $this;
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
            'info' => $this->errorInfo,
        ];
    }

    protected function getConnection(): PDO
    {
        return $this->pdo;
    }

    protected function getLastQuery(): string
    {
        return $this->lastSql;
    }

    protected function startTransaction()
    {
        $this->pdo->beginTransaction();
    }

    protected function endTransaction()
    {
        $this->pdo->commit();
    }

    protected function rollback()
    {
        $this->pdo->rollback();
    }

    protected function insert(array $insertParams): int
    {
        $keys = array_keys($insertParams);

        $sql = "INSERT INTO `" . $this->tablename . "` (`" . implode('`,`', $keys) . "`) VALUES (:" . implode(", :", $keys) . ")";

        $this->responds = $this->query($sql, $this->prepareBind($insertParams));

        return ($this->responds !== false) ? (int)$this->pdo->lastInsertId() : 0;
    }

    protected function update(array $updateparams, string $where, array $whereparams): int
    {
        $set = [];

        foreach (array_keys($updateparams) as $key) {
            $set[] = '`' . $key . '` = :' . $key;
        }

        $sql = "UPDATE `" . $this->tablename . "` SET " . implode(',', $set) . ' WHERE ' . $where;

        $this->responds = $this->query($sql, $this->prepareBind(array_replace($updateparams, $whereparams)));

        return ($this->responds !== false) ? $this->responds->rowCount() : 0;
    }

    protected function delete(string $where, array $whereparams): int
    {
        $sql = "DELETE FROM `" . $this->tablename . "` WHERE " . $where;

        $this->responds = $this->query($sql, $this->prepareBind($whereparams));

        return ($this->responds !== false) ? $this->responds->rowCount() : 0;
    }

    protected function select(array $columns = ['*'], string $where = null, array $whereparams = null): array
    {
        $this->responds = $this->buildSelect($columns, $where, $whereparams);

        return ($this->responds !== false) ? $this->fetchMany() : [];
    }

    protected function selectOne(array $columns = ['*'], string $where = null, array $whereparams = null): array
    {
        $this->responds = $this->buildSelect($columns, $where, $whereparams);

        return ($this->responds !== false) ? $this->fetchOne() : [];
    }

    protected function buildSelect(array $columns, ?string $where = null, ?array $whereparams = null): mixed
    {
        $sqlColumns = ($columns == ['*']) ? '*' : `' . implode("\`,\`", $columns) . '`;

        $sql = 'SELECT ' . $sqlColumns . ' FROM `' . $this->tablename . '` ';

        $this->append('', 'join', $sql);

        if ($where) {
            $sql .= ' WHERE ' . $where;
        }

        $this->append('ORDER BY', 'orderby', $sql)->append('LIMIT', 'limit', $sql);

        return $this->query($sql, $this->prepareBind($whereparams));
    }

    protected function join(string $string): self
    {
        $this->join = $string;

        return $this;
    }

    protected function orderby(string $string): self
    {
        $this->orderby = $string;

        return $this;
    }

    protected function limit(string $string): self
    {
        $this->limit = $string;

        return $this;
    }

    /**
     * override in child class if necessary
     */
    public function fetchMany(): mixed
    {
        return $this->responds->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * override in child class if necessary
     */
    public function fetchOne(): mixed
    {
        return $this->responds->fetch(PDO::FETCH_ASSOC);
    }

    protected function prepareBind(?array $params): array
    {
        $bind = [];

        if (is_array($params)) {
            foreach ($params as $key => $value) {
                $bind[':' . trim($key, ':')] = $value;
            }
        }

        return $bind;
    }

    protected function query(mixed $sql, array $bind = []): mixed
    {
        $this->reset();

        $this->lastSql = trim($sql);

        $PDOStatement = $this->pdo->prepare($this->lastSql);

        if ($PDOStatement->execute($bind) === false) {
            $this->errorCode = $PDOStatement->errorCode();
            $this->errorInfo = $PDOStatement->errorInfo();
            $this->hasError = true;
        }

        return $PDOStatement;
    }

    protected function append(string $sqlKey, string $key, string &$sql): self
    {
        if (!empty($this->$key)) {
            $sql .= ' ' . $sqlKey . ' ' . $this->$key . ' ';
        }

        return $this;
    }
}
