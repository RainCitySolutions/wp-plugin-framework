<?php
namespace RainCity\WPF\Background;

use Psr\Log\LoggerInterface;
use RainCity\Logging\Logger;

/**
 *
 * @author rainc
 *
 */
abstract class BgTask implements \Serializable
{
    /** @var LoggerInterface */
    protected $logger;

    /**
     *
     */
    public function __construct()
    {
        $this->initLogger();
    }

    /**
     * Initialize the logger.
     *
     * Used both during construction and when unserializing an instance.
     */
    private function initLogger()
    {
        $this->logger = Logger::getLogger(get_class($this));
    }

    /**
     * Run the task.
     *
     * @param BgProcess $bgProcess The background process instance. Useful
     *      when additional tasks need to be added to the queue.
     * @param array $params The array of parameters provided to the background
     *      process when it was created.
     *
     * @return bool Returns true if the task is complete. Otherwise returns false.
     */
    abstract public function run(BgProcess $bgProcess, ...$params) : bool;

    public function serialize()
    {
        $vars = get_object_vars($this);
        unset($vars['logger']); // don't serialize the logger
        return serialize($vars);
    }

    public function unserialize($data)
    {
        $vars = unserialize($data);
        foreach ($vars as $var => $value) {
            $this->$var = $value;
        }

        $this->initLogger();
    }
}
