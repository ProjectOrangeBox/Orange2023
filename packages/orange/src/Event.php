<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\base\Singleton;
use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\EventInterface;
use orange\framework\traits\ConfigurationTrait;

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
    use ConfigurationTrait;

    protected array $events = [];
    protected bool $disabled = false;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    protected function __construct(array $config)
    {
        logMsg('INFO', __METHOD__);

        $this->config = $this->mergeWithDefault($config);

        $this->disabled = $this->config['disabled'] ?? $this->disabled;

        // unset if it's set because this is an internally used key and not a trigger
        unset($this->config['disabled']);

        $this->events = [];

        foreach ($this->config as $trigger => $events) {
            foreach ($events as $options) {
                // option[0] is either a Closure or a string containing the class name and method separated by :: (double colons)
                // option[1] is either empty or a PRIORITY (see interface)
                $this->registerEvent($trigger, $options[0], $options[1] ?? self::PRIORITY_NORMAL);
            }
        }
    }

    public function disable(): void
    {
        logMsg('INFO', __METHOD__);

        $this->disabled = true;
    }

    public function enable(): void
    {
        logMsg('INFO', __METHOD__);

        $this->disabled = false;
    }

    /**
     * Register a listener
     *
     * register('open.page',function(&$var1) { echo "hello $var1"; },EVENT::PRIORITY_HIGH);
     * register('open.page',['\foo\bar','method'],EVENT::PRIORITY_LOW);
     */
    public function register(string $trigger, \Closure|array $callable, int $priority = self::PRIORITY_NORMAL): int
    {
        logMsg('INFO', __METHOD__);
        logMsg('DEBUG', '', ['trigger' => $trigger, 'callable' => $callable, 'priority' => $priority]);

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
        logMsg('INFO', __METHOD__ . ' ' . $trigger);
        logMsg('DEBUG', '', ['trigger' => $trigger, 'arguments' => $arguments, 'disabled' => $this->disabled]);

        if (!$this->disabled) {
            $trigger = $this->normalize($trigger);

            // do we even have any events with this name?
            if (isset($this->events[$trigger])) {
                foreach ($this->listeners($trigger) as $listener) {
                    // stop processing on return of false
                    if ($listener(...$arguments) === false) {
                        break;
                    }
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
        logMsg('DEBUG', __METHOD__, ['trigger' => $trigger]);

        return isset($this->events[$this->normalize($trigger)]);
    }

    /**
     *
     * Return an array of all of the event names
     *
     */
    public function triggers(): array
    {
        logMsg('DEBUG', __METHOD__, array_keys($this->events));

        return array_keys($this->events);
    }

    /**
     *
     * Removes a single listener from an event by its register Id.
     *
     */
    public function unregister(int $eventId): bool
    {
        logMsg('DEBUG', __METHOD__, ['eventId' => $eventId]);

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
        logMsg('DEBUG', __METHOD__, ['trigger' => $trigger]);

        $trigger = $this->normalize($trigger);

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
        $trigger = $this->normalize($trigger);

        krsort($this->events[$trigger]);

        return $this->events[$trigger];
    }

    protected function registerEvent(string $trigger, \Closure|array $callable, int $priority): int
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

    protected function registerClosureEvent(string $trigger, \Closure $callable, int $priority): int
    {
        $eventId = \intval((string)$priority . (string)\hrtime(true));

        $this->events[$this->normalize($trigger)][$eventId] = $callable;

        return $eventId;
    }
}
