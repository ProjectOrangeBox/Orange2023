<?php

declare(strict_types=1);

namespace orange\framework;

use Throwable;
use Psr\Log\LoggerInterface;
use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\LogInterface;
use orange\framework\exceptions\IncorrectInterface;
use orange\framework\exceptions\filesystem\DirectoryNotWritable;

class Log implements LogInterface, LoggerInterface
{
    private static ?LogInterface $instance;

    protected array $config;
    // monolog instance or this class ie. handle myself
    protected $handler;
    protected bool $enabled;
    protected int $threshold;

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
    protected array $psrLevelsInt;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct(array $config)
    {
        $this->config = Application::mergeDefaultConfig($config, __DIR__ . '/config/log.php');

        $this->enabled = false;
        $this->threshold = self::NONE;

        $this->psrLevelsInt = array_flip($this->psrLevels);

        /**
         * The handler MUST implement the PSR-3 LoggerInterface
         * 
         * in order to user Monolog for example your could
         * set the handler to monolog
         * 
         */
        if (isset($this->config['handler'])) {
            if (!is_object($this->config['handler'])) {
                throw new InvalidValue('handler is not an object');
            }

            if (!$this->config['handler'] instanceof LoggerInterface) {
                throw new IncorrectInterface('handler is not an instance of LoggerInterface');
            }

            $this->handler = $this->config['handler'];
        } else {
            // use this class as the fall back if another handler is NOT setup
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

    public function write(int $level, string $message, array $context = []): void
    {
        $this->log($level, $message, $context);
    }

    /* PSR-3 methods */

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(self::ALERT, $message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(self::NOTICE, $message, $context);
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(self::DEBUG, $message, $context);
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $levelAsInt = $this->convertLevelToInt($level);
        $levelAsString = $this->convertLevelToString($level);
        
        if ($this->enabled && $this->threshold & $levelAsInt) {
            if ($this->handler == $this) {
                $this->internalWrite($levelAsString, $message, $context);
            } else {
                // pass to attached handler (usually monolog)
                $this->handler->$levelAsString($message, $context);
            }
        }
    }

    /* protected */

    protected function convertLevelToString(int|string $input): string
    {
        return $this->convert2($input, false);
    }

    protected function convertLevelToInt(int|string $input): int
    {
        return $this->convert2($input, true);
    }

    protected function convert2(int|string $input, bool $asInt = false): mixed
    {
        // psrLevels / string->int
        // psrLevelsInt / int->string

        if (is_string($input)) {
            if (!isset($this->psrLevels[strtoupper($input)])) {
                throw new InvalidValue('Unknown message log level "' . $input . '".');
            }

            $method = strtolower($input);
        } else {
            // integer
            if (!isset($this->psrLevelsInt[$input])) {
                throw new InvalidValue('Unknown message log level "' . $input . '".');
            }

            $method = $this->psrLevelsInt[$input];
        }

        // always converted to string "method"
        return ($asInt) ? $this->psrLevels[strtoupper($method)] : $method;
    }

    protected function internalWrite(string $level, string $message, array $context = []): void
    {
        $write = '';
        $isNewFile = false;

        if (!file_exists($this->config['filepath'])) {
            $isNewFile = true;
        }

        $write .= str_replace(
            ['%timestamp', '%level', '%message'],
            [date($this->config['timestamp format']), $level, $message],
            $this->config['line format']
        );

        if (!empty($context)) {
            $write .= var_export($context, true);
        }

        // Not atomic but we need append
        file_put_contents($this->config['filepath'], $write, FILE_APPEND | LOCK_EX);

        if ($isNewFile) {
            chmod($this->config['filepath'], $this->config['permissions']);
        }
    }

    protected function isFileWritable(string $file): bool
    {
        // check we can write in the directory
        $dir = dirname($file);

        if (!file_exists($dir)) {
            try {
                mkdir($dir, 0777, true);
            } catch (Throwable $e) {
                throw new DirectoryNotWritable($dir);
            }
        }

        if (!is_writable($dir)) {
            throw new DirectoryNotWritable($dir);
        }

        return true;
    }
}
