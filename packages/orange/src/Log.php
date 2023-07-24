<?php

declare(strict_types=1);

namespace dmyers\orange;

use dmyers\orange\exceptions\InvalidValue;
use dmyers\orange\interfaces\LogInterface;
use dmyers\orange\exceptions\FileNotWritable;
use dmyers\orange\exceptions\invalidConfigurationValue;
use Throwable;

class Log implements LogInterface
{
    private static LogInterface $instance;
    protected array $config;
    // monolog instance or this class ie. handle myself
    protected $handler = null;
    protected bool $enabled = false;
    protected int $threshold = 0;

    protected array $psrLevels = [
        'NONE'      => 0,
        'EMERGENCY' => 1,
        'ALERT'     => 2,
        'CRITICAL'  => 4,
        'ERROR'     => 8,
        'WARNING'   => 16,
        'NOTICE'    => 32,
        'INFO'      => 64,
        'DEBUG'     => 128,
    ];
    protected array $psrLevelsInt = [];

    public function __construct(array $config)
    {
        $this->config = $config;

        $this->psrLevelsInt = array_flip($this->psrLevels);

        if (isset($this->config['monolog'])) {
            if (!is_a($this->config['monolog'], '\Monolog\Logger')) {
                throw new invalidConfigurationValue('monolog must be instance of \Monolog\Logger');
            }

            $this->handler = $this->config['monolog'];
        } else {
            $this->handler = $this;

            // throws exception
            $this->isFileWritable($this->config['filepath']);

            $this->changeThreshold($config['threshold']);
        }
    }

    public static function getInstance(array $config): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public function changeThreshold(int $threshold): self
    {
        $this->threshold = $threshold;

        $this->enabled = ($this->threshold > 0);

        return $this;
    }

    public function getThreshold(): int
    {
        return $this->threshold;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function __call($name, $arguments)
    {
        if ($this->handler == $this) {
            // convert to int
            $level = $this->convert2($name, true);

            $this->internalWrite($level, $arguments[0]);
        } else {
            $method = strtolower($name);

            $this->handler->$method($arguments[0]);
        }
    }

    public function convert2(int|string $input, bool $asInt = false): mixed
    {
        $method = '';

        if (is_string($input)) {
            if (isset($this->psrLevels[strtoupper($input)])) {
                $method = $this->psrLevels[strtoupper($input)];
            }
        } elseif (is_int($input)) {
            if (isset($this->psrLevelsInt[$input])) {
                $method = strtolower($this->psrLevelsInt[$input]);
            }
        }

        if ($method == '') {
            throw new InvalidValue('Unknown message log level "' . $input . '".');
        }

        // always converted to string "method"
        return ($asInt) ? $this->psrLevels[strtoupper($input)] : $method;
    }

    protected function internalWrite(int $level, string $message): void
    {
        if ($this->enabled && $this->threshold & $level) {
            $write = '';
            $isNewFile = false;

            if (!file_exists($this->config['filepath'])) {
                $isNewFile = true;
            }

            $write .= date('Y-m-d H:i:s') . ' ' . $this->psrLevelsInt[$level] . ' ' . $message . PHP_EOL;

            // Not atomic but we need append
            file_put_contents($this->config['filepath'], $write, FILE_APPEND | LOCK_EX);

            if ($isNewFile) {
                chmod($this->config['filepath'], $this->config['permissions']);
            }
        }
    }

    public function __debugInfo(): array
    {
        return [
            'config'=>$this->config,
            'enabled'=>$this->enabled,
            'threshold'=>$this->threshold,
            'psrLevels'=>$this->psrLevels,
            'psrLevelsInt'=>$this->psrLevelsInt,
        ];
    }

    protected function isFileWritable(string $file): bool
    {
        // check we can write in the directory
        $dir = dirname($file);

        if (!is_dir($dir)) {
            try {
                mkdir($dir, 0755, true);
            } catch (Throwable $e) {
                throw new FileNotWritable($dir);
            }
        }

        if (!is_writable($dir)) {
            throw new FileNotWritable($dir);
        }

        return true;
    }

}
