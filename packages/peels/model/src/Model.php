<?php

declare(strict_types=1);

namespace peels\model;

use PDO;
use peels\validate\interfaces\ValidateInterface;

abstract class Model
{
    private static $instance;

    protected PDO $pdo;
    protected array $config = [];

    // extended by child models
    protected array $rules = [];
    /* rules example:
      'id' => ['isRequired|isInteger', 'Id'],
      'firstname' => ['isRequired|isString|isAlphaNumericSpace|maxLength[32]', 'First Name'],
      'lastname' => ['isRequired|isString|isAlphaNumericSpace|maxLength[32]', 'Last Name'],
      'age' => ['isRequired|isInteger|isGreaterThan[17]|isLessThan[111]', 'Age'],
    */

    protected array $ruleSets = [];
    /* ruleSets example:
      'create' => ['firstname', 'lastname', 'age'],
      'update' => ['id', 'firstname', 'lastname', 'age'],
      'delete' => ['id'],
    */

    // required in extending class
    protected string $tablename;
    protected string $primaryColumn = 'id';

    protected string $entityClass;

    // https://www.php.net/manual/en/pdostatement.fetch.php
    protected int $defaultFetchType = PDO::FETCH_ASSOC;

    // if type is PDO::FETCH_CLASS provide the class here
    protected string $fetchClass = '';

    // throw an exception on error or simply capture for further processing
    protected bool $throwException = false;

    protected Sql $sql;
    protected Crud $crud;
    protected ValidateInterface $validateService;

    protected function __construct(?array $config, PDO $pdo, ValidateInterface $validateService)
    {
        $this->config = $config ?? [];

        if (empty($this->tablename)) {
            $this->tablename = $this->generateTablename();
        }

        $this->pdo = $pdo;
        $this->validateService = $validateService;

        // setup sql config
        $this->config = [
            'primaryColumn' => $config['primaryColumn'] ?? $this->primaryColumn,
            'tablename' => $config['tablename'] ?? $this->tablename,
            'defaultFetchType' => $config['defaultFetchType'] ?? $this->defaultFetchType,
            'fetchClass' => $config['fetchClass'] ?? $this->fetchClass,
            // should the model throw exceptions?
            'throwException' => $config['throwException'] ?? $this->throwException,
        ];

        // validateService should throw exceptions for failed rules so we can catch them
        $this->validateService->throwExceptionOnFailure(true);

        // setup our own personal versions
        $this->sql = new Sql($this->config, $pdo);

        // crud always throws sql exceptions
        $this->crud = new Crud($this->config, $pdo);
    }

    public static function getInstance(array $config, PDO $pdo, ValidateInterface $validate): self
    {
        if (!isset(self::$instance)) {
            $extendingClass = get_called_class();

            self::$instance = new $extendingClass($config, $pdo, $validate);
        }

        return self::$instance;
    }

    public function getRules(string $set): array
    {
        // we only need these rules
        return array_intersect_key($this->rules, array_flip($this->ruleSets[$set]));
    }

    public function filterFields(string $set, array $fields): array
    {
        // we only need these columns (any additional are removed!)
        return array_intersect_key($fields, array_flip($this->ruleSets[$set]));
    }

    public function validateFields(string $set, array $fields): bool
    {
        // now let's validation the input against the models column rules
        $this->validateService->reset();

        // we only need these rules
        $rules = $this->getRules($set);

        // we only need these columns (any additional are removed!)
        $fields = $this->filterFields($set, $fields);

        // validate our record and get out our values
        $fields = $this->validateService->input($fields, $rules)->values();

        // return bool
        return $this->validateService->hasNoErrors();
    }

    public function generateTablename(): string
    {
        $tablename = static::class;

        $pos = strrpos($tablename, '\\');

        if ($pos) {
            $tablename = substr($tablename, $pos + 1);
        }

        if (str_ends_with(strtolower($tablename), 'model')) {
            $tablename = substr($tablename, 0, -5);
        }

        return $tablename;
    }
}
