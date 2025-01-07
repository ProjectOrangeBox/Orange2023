<?php

declare(strict_types=1);

namespace orange\framework;

use orange\framework\base\Singleton;
use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\EventInterface;
use orange\framework\traits\ConfigurationTrait;

/**
 * Event Manager Class
 *
 * This class manages event registration, triggering, and unregistration.
 * It supports priority-based listeners and can handle both closures and class methods.
 *
 * Example Configuration:
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
    /** include ConfigurationTrait methods */
    use ConfigurationTrait;

    /** @var array Stores all registered events grouped by triggers. */
    protected array $events = [];

    /** @var bool Indicates whether the event manager is disabled. */
    protected bool $disabled = false;

    /**
     * Constructor is protected to enforce Singleton usage.
     *
     * @param array $config Configuration array.
     */
    protected function __construct(array $config)
    {
        logMsg('INFO', __METHOD__);

        // Merge provided configuration with default configuration.
        $this->config = $this->mergeWithDefault($config);

        $this->disabled = $this->config['disabled'] ?? $this->disabled;

        // Prevent 'disabled' key from being used as an event.
        unset($this->config['disabled']);

        $this->events = [];

        // Register all configured events
        foreach ($this->config as $trigger => $events) {
            foreach ($events as $options) {
                // option[0] is either a Closure or a string containing the class name and method separated by :: (double colons)
                // option[1] is either empty or a PRIORITY (see interface)
                $this->registerEvent($trigger, $options[0], $options[1] ?? self::PRIORITY_NORMAL);
            }
        }
    }

    /**
     * Disable all event triggers.
     */
    public function disable(): void
    {
        logMsg('INFO', __METHOD__);
        $this->disabled = true;
    }

    /**
     * Enable all event triggers.
     */
    public function enable(): void
    {
        logMsg('INFO', __METHOD__);
        $this->disabled = false;
    }

    /**
     * Register a single event listener.
     *
     * @param string $trigger Event trigger name.
     * @param \Closure|array $callable Event callback (closure or class-method pair).
     * @param int $priority Priority of the event listener.
     * @return int Event ID for reference.
     */
    public function register(string $trigger, \Closure|array $callable, int $priority = self::PRIORITY_NORMAL): int
    {
        logMsg('INFO', __METHOD__);
        logMsg('DEBUG', '', ['trigger' => $trigger, 'callable' => $callable, 'priority' => $priority]);

        return $this->registerEvent($trigger, $callable, $priority);
    }

    /**
     * Register multiple event listeners at once.
     *
     * @param array $multiple Array of event trigger => callable pairs.
     * @param int $priority Priority for all listeners.
     * @return array Array of registered event IDs.
     */
    public function registerMultiple(array $multiple, int $priority = self::PRIORITY_NORMAL): array
    {
        $registered = [];

        foreach ($multiple as $trigger => $callable) {
            $registered[] = $this->registerEvent($trigger, $callable, $priority);
        }

        return $registered;
    }

    /**
     * Trigger an event.
     *
     * @param string $trigger Event trigger name.
     * @param mixed ...$arguments Arguments passed to event listeners.
     * @return self Fluent interface.
     */
    public function trigger(string $trigger, &...$arguments): self
    {
        logMsg('INFO', __METHOD__ . ' ' . $trigger);
        logMsg('DEBUG', '', ['trigger' => $trigger, 'arguments' => $arguments, 'disabled' => $this->disabled]);

        if (!$this->disabled) {
            $trigger = $this->normalize($trigger);

            if (isset($this->events[$trigger])) {
                foreach ($this->listeners($trigger) as $listener) {
                    if ($listener(...$arguments) === false) {
                        break; // Stop processing if listener returns false
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Check if any listeners exist for a specific event trigger.
     *
     * @param string $trigger Event trigger name.
     * @return bool True if listeners exist, false otherwise.
     */
    public function has(string $trigger): bool
    {
        logMsg('DEBUG', __METHOD__, ['trigger' => $trigger]);

        return isset($this->events[$this->normalize($trigger)]);
    }

    /**
     * Retrieve all registered event triggers.
     *
     * @return array List of all registered event triggers.
     */
    public function triggers(): array
    {
        logMsg('DEBUG', __METHOD__, array_keys($this->events));

        return array_keys($this->events);
    }

    /**
     * Unregister a specific event listener by its ID.
     *
     * @param int $eventId Event ID to remove.
     * @return bool True if removed successfully, false otherwise.
     */
    public function unregister(int $eventId): bool
    {
        logMsg('DEBUG', __METHOD__, ['eventId' => $eventId]);

        foreach ($this->events as $trigger => &$listeners) {
            if (isset($listeners[$eventId])) {
                unset($listeners[$eventId]);

                if (empty($listeners)) {
                    unset($this->events[$trigger]);
                }
                return true;
            }
        }

        return false;
    }

    /**
     * Unregister all event listeners, optionally for a specific trigger.
     *
     * @param string|null $trigger Event trigger name (optional).
     * @return bool True if listeners were removed.
     */
    public function unregisterAll(string $trigger = null): bool
    {
        logMsg('DEBUG', __METHOD__, ['trigger' => $trigger]);

        if ($trigger) {
            $trigger = $this->normalize($trigger);

            if (isset($this->events[$trigger])) {
                unset($this->events[$trigger]);
                return true;
            }
        } else {
            $this->events = [];
            return true;
        }

        return false;
    }

    /**
     * Retrieve listeners for a given trigger, sorted by priority.
     *
     * @param string $trigger Event trigger name.
     * @return array Sorted array of listeners.
     */
    protected function listeners(string $trigger): array
    {
        $trigger = $this->normalize($trigger);
        // Sort by priority (highest first)
        krsort($this->events[$trigger]);
        return $this->events[$trigger];
    }

    /**
     * Register an event listener.
     *
     * @param string $trigger Event trigger name.
     * @param \Closure|array $callable Callback.
     * @param int $priority Priority level.
     * @return int Event ID.
     */
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
            throw new InvalidValue(__METHOD__ . ' trigger "' . $trigger . '"');
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
