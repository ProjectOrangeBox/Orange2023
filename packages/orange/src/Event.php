<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\Singleton;
use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\EventInterface;

/**
 *
 * Example
 *
 * return [
 *   'before.router' => [
 *       [\app\libraries\OutputCors::class . '::handleCrossOriginResourceSharing', Event::PRIORITY_HIGHEST],
 *       [\app\libraries\Middleware::class . '::beforeRouter'],
 *   ],
 *   'before.controller' => [
 *       [\app\libraries\Middleware::class . '::beforeController'],
 *   ],
 *   'after.controller' => [
 *       [\app\libraries\Middleware::class . '::afterController'],
 *   ],
 *   'after.output' => [
 *       [\app\libraries\Middleware::class . '::afterOutput'],
 *   ],
 *   'some.bogus_Event' => [
 *       ['\app\bogus\class::bogus_method', Event::PRIORITY_LOWEST],
 *   ],
 * ];
 *
 */

class Event extends Singleton implements EventInterface
{
    private static ?EventInterface $instance = null;
    protected array $events = [];
    protected array $config = [];

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct(array $config)
    {
        $this->config = mergeDefaultConfig($config, __DIR__ . '/config/events.php');

        foreach ($this->config as $trigger => $events) {
            foreach ($events as $options) {
                // option[0] is either a Closure or a string containing the class name and method separated by :: (double colons)
                // option[1] is either empty or a PRIORITY (see interface)
                $this->registerEvent($trigger, $options[0], $options[1] ?? self::PRIORITY_NORMAL);
            }
        }
    }

    public static function getInstance(array $config): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    /**
     * Register a listener
     *
     * register('open.page',function(&$var1) { echo "hello $var1"; },EVENT::PRIORITY_HIGH);
     */
    public function register($trigger, $callable, int $priority = self::PRIORITY_NORMAL): int
    {
        return $this->registerEvent($trigger, $callable, $priority);
    }

    public function registerMultiple(array $multiple, int $priority = self::PRIORITY_NORMAL): array
    {
        $registered = [];

        foreach ($multiple as $trigger => $callable) {
            $registered[] = $this->registerEvent($trigger, $callable, $priority);
        }

        return $registered;
    }

    /**
     * Trigger an event
     *
     * trigger('open.page',$var1);
     */
    public function trigger(string $trigger, &...$arguments): self
    {
        logMsg('DEBUG', __METHOD__ . ' ' . $trigger);

        $trigger = $this->normalizeName($trigger);

        // do we even have any events with this name?
        if (isset($this->events[$trigger])) {
            foreach ($this->listeners($trigger) as $listener) {
                // stop processing on return of false
                if ($listener(...$arguments) === false) {
                    break;
                }
            }
        }

        return $this;
    }

    /**
     *
     * Are there any listeners for a certain event?
     *
     */
    public function has(string $trigger): bool
    {
        return isset($this->events[$this->normalizeName($trigger)]);
    }

    /**
     *
     * Return an array of all of the event names
     *
     */
    public function triggers(): array
    {
        return array_keys($this->events);
    }

    /**
     *
     * Removes a single listener from an event by its register Id.
     *
     */
    public function unregister(int $eventId): bool
    {
        $removed = false;

        foreach ($this->events as $trigger => $events) {
            foreach (array_keys($events) as $eventIdKey) {
                if ($eventIdKey == $eventId) {
                    unset($this->events[$trigger][$eventIdKey]);

                    $removed = true;

                    // if it's completely empty remove it
                    if (empty($this->events[$trigger])) {
                        unset($this->events[$trigger]);
                    }

                    break 2;
                }
            }
        }

        return $removed;
    }

    /**
     *
     * Removes all listeners.
     *
     * If the event name is specified, only listeners for that event will be
     * removed, otherwise all listeners for all events are removed.
     *
     */
    public function unregisterAll(string $trigger = null): bool
    {
        $trigger = $this->normalizeName($trigger);

        $success = false;

        if ($trigger) {
            if (isset($this->events[$trigger])) {
                unset($this->events[$trigger]);
                $success = true;
            }
        } else {
            $this->events = [];
            $success = true;
        }

        return $success;
    }

    /* protected */

    /**
     * get back priority sorted
     */
    protected function listeners(string $trigger): array
    {
        $trigger = $this->normalizeName($trigger);

        krsort($this->events[$trigger]);

        return $this->events[$trigger];
    }

    protected function registerEvent(string $trigger, $callable, int $priority): int
    {
        $eventId = 0;

        if ($callable instanceof \Closure) {
            // register a closure
            //
            // function(&$var) {
            //   $var = 'Hello ' . $var. ' how are you?';
            // }
            //
            $eventId = $this->registerClosureEvent($trigger, $callable, $priority);
        } elseif (is_array($callable) && count($callable) == 2) {
            //
            // register a class & method
            //
            // [\app\libraries\Middleware::class,'before']

            $eventId = $this->registerClosureEvent($trigger, function (&...$arguments) use ($callable) {
                list($className, $methodName) = $callable;

                return (new $className())->$methodName(...$arguments);
            }, $priority);
        } else {
            throw new InvalidValue(json_encode($callable));
        }

        return $eventId;
    }

    protected function registerClosureEvent(string $trigger, $callable, int $priority): int
    {
        $eventId = \intval((string)$priority . (string)\hrtime(true));

        $this->events[$this->normalizeName($trigger)][$eventId] = $callable;

        return $eventId;
    }

    protected function normalizeName(string $trigger): string
    {
        return mb_convert_case($trigger, MB_CASE_LOWER, mb_detect_encoding($trigger));
    }
}
