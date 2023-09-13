<?php

declare(strict_types=1);

namespace dmyers\orange;

use Throwable;
use dmyers\orange\exceptions\InvalidValue;
use dmyers\orange\interfaces\LogInterface;
use dmyers\orange\exceptions\FileNotWritable;
use dmyers\orange\exceptions\FolderNotWritable;
use dmyers\orange\exceptions\invalidConfigurationValue;

class Log implements LogInterface
{
    private static LogInterface $instance;
    protected array $config = [];
    // monolog instance or this class ie. handle myself
    protected $handler = null;
    protected bool $enabled = false;
    protected int $threshold = self::NONE;

    protected array $psrLevels = [
        'NONE'      => self::NONE,
        'EMERGENCY' => self::EMERGENCY,
        'ALERT'     => self::ALERT,
        'CRITICAL'  => self::CRITICAL,
        'ERROR'     => self::ERROR,
        'WARNING'   => self::WARNING,
        'NOTICE'    => self::NOTICE,
        'INFO'      => self::INFO,
        'DEBUG'     => self::DEBUG,
    ];
    protected array $psrLevelsInt = [];

    public function __construct(array $config)
    {
        $this->config = mergeDefaultConfig($config, __DIR__ . '/config/log.php');

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

            $this->changeThreshold($this->config['threshold']);
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

    public function write(int $level, string $message): void
    {
        $this->__call($this->convert2($level), [0 => $message]);
    }

    public function __call($name, $arguments)
    {
        if ($this->handler == $this) {
            // convert method name to int
            if (!is_string($arguments[0])) {
                throw new InvalidValue($arguments[0]);
            }

            $this->internalWrite($this->convert2($name, true), $arguments[0]);
        } else {
            // pass to attached handler (usually monolog)
            $method = strtolower($name);

            $this->handler->$method($arguments[0]);
        }
    }

    public function convert2(int|string $input, bool $asInt = false): mixed
    {
        // psrLevels / string->int
        // psrLevelsInt / int->string

        if (is_string($input)) {
            if (!isset($this->psrLevels[strtoupper($input)])) {
                throw new InvalidValue('Unknown message log level "' . $input . '".');
            }

            $method = strtoupper($input);
        } else {
            // integer
            if (!isset($this->psrLevelsInt[$input])) {
                throw new InvalidValue('Unknown message log level "' . $input . '".');
            }

            $method = $this->psrLevelsInt[$input];
        }

        // always converted to string "method"
        return ($asInt) ? $this->psrLevels[$method] : $method;
    }

    protected function internalWrite(int $level, string $message): void
    {
        if ($this->enabled && $this->threshold & $level) {
            $write = '';
            $isNewFile = false;

            if (!file_exists($this->config['filepath'])) {
                $isNewFile = true;
            }

            $write .= str_replace(
                ['%timestamp','%level','%message'],
                [date($this->config['timestamp format']),$this->psrLevelsInt[$level],$message],
                $this->config['line format']
            );

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
            'config' => $this->config,
            'enabled' => $this->enabled,
            'threshold' => $this->threshold,
            'psrLevels' => $this->psrLevels,
            'psrLevelsInt' => $this->psrLevelsInt,
        ];
    }
    
    protected function isFileWritable(string $file): bool
    {
        // check we can write in the directory
        $dir = dirname($file);

        if (!file_exists($dir)) {
            try {
                mkdir($dir, 0777, true);
            } catch (Throwable $e) {
                throw new FolderNotWritable($dir);
            }
        }

        if (!is_writable($dir)) {
            throw new FileNotWritable($dir);
        }

        return true;
    }
}
