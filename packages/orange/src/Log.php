<?php

declare(strict_types=1);

namespace orange\framework;

use Throwable;
use Psr\Log\LoggerInterface;
use orange\framework\base\Singleton;
use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\LogInterface;
use orange\framework\traits\ConfigurationTrait;
use orange\framework\exceptions\IncorrectInterface;
use orange\framework\exceptions\filesystem\DirectoryNotWritable;

/**
 * Class Log
 *
 * Implements logging functionality, supports PSR-3 LoggerInterface,
 * and can utilize custom logging handlers.
 * Use Singleton::getInstance() to obtain an instance.
 *
 * @package orange\framework
 */
class Log extends Singleton implements LogInterface, LoggerInterface
{
    /** include ConfigurationTrait methods */
    use ConfigurationTrait;

    /**
     * Logging handler instance (PSR-3 compatible or internal handler).
     */
    protected $handler;

    /**
     * Determines whether logging is enabled.
     */
    protected bool $enabled = false;

    /**
     * Logging threshold level.
     */
    protected int $threshold = 0;

    /**
     * Mapping of PSR logging levels to their integer representations.
     *
     * @var array<string, int>
     */
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

    /**
     * Reverse mapping of integer levels to PSR logging level names.
     *
     * @var array<int, string>
     */
    protected array $psrLevelsInt = [];

    /**
     * Constructor is protected to enforce the singleton pattern.
     *
     * @param array $config Configuration data.
     * @throws InvalidValue If the handler is not an object.
     * @throws IncorrectInterface If the handler does not implement LoggerInterface.
     * @throws DirectoryNotWritable If the log file directory is not writable.
     */
    protected function __construct(array $config)
    {
        $this->config = $this->mergeWithDefault($config);

        // default off
        $this->enabled = false;

        $this->psrLevelsInt = array_flip($this->psrLevels);

        $this->changeThreshold($this->config['threshold']);

        if (isset($this->config['handler'])) {
            if (!is_object($this->config['handler'])) {
                throw new InvalidValue('handler is not an object');
            }

            if (!$this->config['handler'] instanceof LoggerInterface) {
                throw new IncorrectInterface('handler is not an instance of LoggerInterface');
            }

            $this->handler = $this->config['handler'];
        } else {
            $this->handler = $this;
            $this->isFileWritable($this->config['filepath']);
        }
    }

    /**
     * Changes the logging threshold.
     *
     * @param int $threshold Logging threshold level.
     * @return self
     */
    public function changeThreshold(int $threshold): self
    {
        logMsg('INFO', __METHOD__ . ' ' . $threshold);

        $this->threshold = $threshold;

        $this->enabled = $this->threshold !== 0;

        return $this;
    }

    /**
     * Retrieves the current logging threshold.
     *
     * @return int
     */
    public function getThreshold(): int
    {
        return $this->threshold;
    }

    /**
     * Checks if logging is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Writes a log entry to the specified handler or file.
     *
     * @param string|int $level Log level.
     * @param string|\Stringable $message Log message.
     * @param array $context Contextual information.
     */
    public function write(string|int $level, string|\Stringable $message, array $context = []): void
    {
        if ($this->isLevelEnabled($level)) {
            $contextString = !empty($context) ? var_export($context, true) : '';

            $data = str_replace(
                ['%timestamp', '%level', '%message', '%context'],
                [date($this->config['timestamp format']), strtoupper($this->convert2($level, 'string')), $message, $contextString],
                $this->config['line format']
            );

            $isNewFile = !file_exists($this->config['filepath']);

            file_put_contents($this->config['filepath'], $data, FILE_APPEND | LOCK_EX);

            if ($isNewFile) {
                chmod($this->config['filepath'], $this->config['permissions']);
            }
        }
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

    /* match PSR-3 LoggerInterface */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        if ($this->isLevelEnabled($level)) {
            $levelAsString = $this->convert2($level, 'string');

            if ($this->handler == $this) {
                $this->write($levelAsString, $message, $context);
            } else {
                $this->handler->$levelAsString($message, $context);
            }
        }
    }

    /**
     * Checks if a log level is enabled based on the threshold.
     *
     * @param string|int $level Log level.
     * @return bool
     */
    protected function isLevelEnabled(string|int $level): bool
    {
        return $this->enabled && ($this->threshold & $this->convert2($level, 'int'));
    }

    /**
     * Converts a log level between integer and string representation.
     *
     * @param string|int $input Log level.
     * @param string $as Desired return type ('int' or 'string').
     * @return mixed
     * @throws InvalidValue If the level is invalid.
     */
    protected function convert2(int|string $input, string $as): mixed
    {
        if (is_string($input)) {
            if (!isset($this->psrLevels[strtoupper($input)])) {
                throw new InvalidValue('Unknown message log level "' . $input . '".');
            }
            $method = strtolower($input);
        } else {
            if (!isset($this->psrLevelsInt[$input])) {
                throw new InvalidValue('Unknown message log level "' . $input . '".');
            }
            $method = $this->psrLevelsInt[$input];
        }

        return ($as == 'int') ? $this->psrLevels[strtoupper($method)] : $method;
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
