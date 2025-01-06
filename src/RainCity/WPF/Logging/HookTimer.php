<?php
declare(strict_types = 1);
namespace RainCity\WPF\Logging;

use RainCity\Timer;

class HookTimer
{
    /** @var string[] List of hooks to time. */
    private array $hooks = [];

    /** @var callable A function which takes a string parameter to log the results. */
    private $logFunction;

    /** @var \stdClass[] */
    private array $stack = [];

    /**
     * Construct an instance of the timer.
     *
     * If the $hooks parameter is empty all hooks will be timed with the
     * execution time being reported after all functions for that hook have
     * executed. If the $hooks parameter contains a list of hooks, only
     * those hooks will be timed and the execution time will be reported
     * after all function for that hook at each priority have executed.
     * <p>
     * The $logFunction must be a function which takes a single string
     * parameter. The function will be called to report execution times.
     *
     * @param array $hooks The array of hooks to time. Defaults to all hooks.
     * @param callable $logMethod A function, which takes a string parameter,
     *       to log the results.
     */
    public function __construct(array $hooks = [], $logFunction = 'error_log')
    {
        if (!is_callable($logFunction)) {
            throw new \InvalidArgumentException('$logFunction parameter must be \'callable\'');
        }

        $this->hooks = array_unique($hooks);
        $this->logFunction = $logFunction;

        add_action('all', [$this, 'allActionHandler'], 10, 10);
    }

    public function allActionHandler(...$args) {
        $hook = $args[0];

        if (empty($this->hooks) || in_array($hook, $this->hooks)) {
            // Push main hook timer first, first on/last off
            array_push($this->stack, $this->allocateTimer($hook));

            if (!empty($this->hooks)) {
                global $wp_filter;

                /** @var \WP_Hook */
                $hookObj = $wp_filter[$hook];

                // reverse the priorities to the last priority is pushed onto the stack first
                $priorities = array_reverse(array_keys($hookObj->callbacks));

                foreach($priorities as $priority) {
                    array_push($this->stack, $this->allocateTimer($hook, $priority));
                    add_action($hook, [$this, 'timerCallback'], $priority, 10);
                }
            }

            // Don't add the 'total' callback until have adding any priority callbacks
            add_action($hook, [$this, 'timerCallback'], PHP_INT_MAX, 10);
        }
    }

    private function allocateTimer(string $hook, int $priority = PHP_INT_MAX): \stdClass
    {
        $obj = new \stdClass();
        $obj->hook = $hook;
        $obj->timer = new Timer(true);
        $obj->priority = $priority;

        return $obj;
    }

    private function restartTimerCallback()
    {
        if (!empty($this->stack)) {
            $ndx = array_key_last($this->stack);
            $obj = $this->stack[$ndx];

            if (PHP_INT_MAX != $obj->priority) {
                $obj->timer->start();
            }
        }
    }

    public function timerCallback(...$args)
    {
        $obj = array_pop($this->stack);

        $obj->timer->stop();

        $elapsed = $obj->timer->getTime();

        if (Timer::NO_TIME_MESSAGE !== $elapsed) {
            if (empty($this->hooks)) {
                $msg = sprintf('Hook %s took %s', $obj->hook, $elapsed);
            } else {
                $msg = sprintf('Hook %s (%d) took %s', $obj->hook, $obj->priority, $elapsed);
            }
            call_user_func($this->logFunction, $msg);
        }

        $this->restartTimerCallback();

        if (empty($args)) {
            return;
        } else {
            return $args[0];
        }
    }
}
