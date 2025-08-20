<?php

declare(strict_types=1);

namespace peels\auth;

use PDO;
use orange\framework\traits\ConfigurationTrait;

class Auth implements AuthInterface
{
    use ConfigurationTrait;

    protected array $config = [];

    private AuthInterface $instance;

    protected string $error = '';
    protected int $userId = 0;

    /* database configuration */
    protected PDO $db;
    protected string $table;
    protected string $usernameColumn;
    protected string $passwordColumn;
    protected string $isActiveColumn;

    public function __construct(array $config, PDO $pdo)
    {
        $this->config = $this->mergeConfigWith($config);

        $this->db = $pdo;

        $this->table = $this->config['table'];
        $this->usernameColumn = $this->config['username column'];
        $this->passwordColumn = $this->config['password column'];
        $this->isActiveColumn = $this->config['is active column'];

        /* let make sure the required are present! */


        $this->logout();
    }

    public static function getInstance(array $config, PDO $pdo): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config, $pdo);
        }

        return self::$instance;
    }

    public function error(): string
    {
        return $this->error;
    }

    public function hasError(): bool
    {
        return !empty($this->error);
    }

    public function login(string $login, string $password): bool
    {
        $this->logout();

        /* Does login and password contain anything empty values are NOT permitted for any reason */
        if ((strlen(trim($login)) == 0) || (strlen(trim($password)) == 0)) {
            $this->error = $this->config['empty fields error'];

            /* fail */
            return false;
        }

        /* try to load the user */
        $user = $this->getUser($login);

        if (!is_array($user)) {
            $this->error = $this->config['general error'];

            /* fail */
            return false;
        }

        /* Verify the Password entered with what's in the database */
        if (password_verify($password, $user[$this->passwordColumn]) !== true) {
            $this->error = $this->config['incorrect password error'];

            /* fail */
            return false;
        }

        /* Is this user activated? */
        if ((int) $user[$this->isActiveColumn] !== 1) {
            $this->error = $this->config['not activated error'];

            /* fail */
            return false;
        }

        /* save our user id */
        $this->userId = (int) $user['id'];

        /* successful */
        return true;
    }

    public function logout(): bool
    {
        $this->error = '';
        $this->userId = 0;

        return true;
    }

    public function userId(): int
    {
        return $this->userId;
    }

    protected function getUser(string $login)
    {
        $pdoStatement = $this->db->prepare('select `id`,`' . $this->passwordColumn . '`,`' . $this->isActiveColumn . '` from `' . $this->table . '` where `' . $this->usernameColumn . '` = :login limit 1');

        $pdoStatement->execute([':login' => $login]);

        // https://docstore.mik.ua/orelly/java-ent/jenut/ch08_06.htm
        $error = $pdoStatement->errorInfo();

        if (!empty($error[2])) {
            logMsg('info', __METHOD__ . ' ' . $error[2]);
        }

        return $pdoStatement->fetch(PDO::FETCH_ASSOC);
    }
} /* end class */
