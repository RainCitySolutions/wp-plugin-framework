<?php
namespace RainCity\WPF\Background;

use Psr\Log\LoggerInterface;
use RainCity\Logging\Logger;
use WP_Background_Process;

/**
 * Class for running background tasks.
 *
 * An instance of this class can be used as is if no additional handling
 * is necessary before running a task.
 * <p>
 * One example of when it might be necessary to inherit this class instead
 * would be if there needs to some type of throttling implemented around the
 * running of each task such as when the task makes calls to an API.
 */

class BgProcess extends \WP_Background_Process
{
    private const ACTION_NAME = 'BaseBgProcess';

    // Override prefix and action properties
    // Note: We don't define the member types as they are not defined in the parent classes.
    protected $prefix = 'raincity';
    protected $action = self::ACTION_NAME;

    protected LoggerInterface $logger;

    /** @var array<mixed> */
    protected array $taskParams;

    /**
     * {@inheritDoc}
     * @see \WP_Background_Process::unlock_process()
     */
    protected function unlock_process()
    {
        // if the tasks generated any new tasks, save them
        $this->save();

        return parent::unlock_process();
    }

    /**
     * Initialize the instance.
     *
     * @param array<mixed> $params A set of parameters that will be provided to each
     *      task when it is run.
     */
    public function __construct(...$params)
    {
        parent::__construct();
        $this->logger = Logger::getLogger(get_class($this));

        $this->taskParams = $params;
    }

    /**
     * Add a task to the queue
     *
     * @param BgTask $task
     *
     * @return $this
     */
    public function addTask(BgTask $task): BgProcess
    {
        return parent::push_to_queue($task);
    }

    /**
     * {@inheritDoc}
     * @see WP_Background_Process::push_to_queue()
     */
    public function push_to_queue($data): BgProcess
    {
        _doing_it_wrong(__FUNCTION__, __('Don\'t call this function, call addTask() instead.', 'raincity'), '1.0');

        return $this;
    }

    /**
     * Run the task.
     *
     * If necessary, this would be the method to overload in a derived class
     * to wrap the running of a task with additional code such as throttling.
     *
     * @param BgTask $task The background task to be run.
     *
     * @return bool|BgTask Returns false if the task is complete, otherwise
     *      the task is returned.
     */
    protected function runTask(BgTask $task): BgTask|bool
    {
        $result = $task;

        if ($task->run($this, ...$this->taskParams)) {
            $result = false;
        }

        return $result;
    }


    /**
     *
     * {@inheritDoc}
     * @see WP_Background_Process::task()
     */
    final protected function task($item)
    {
        if ($item instanceof BgTask) {
            $result = $this->runTask($item);
        } else {
            $result = false;
        }

        return $result;
    }
}
