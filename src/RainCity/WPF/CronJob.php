<?php
namespace RainCity\WPF;

use Psr\Log\LoggerInterface;
use RainCity\Logging\Logger;
use RainCity\TestHelper\ReflectionHelper;

/**
 * Base class for plugin cron jobs
 *
 * @since      1.0.0
 */
abstract class CronJob implements CronJobInf
{
    const ONE_TIME_CRONJOB = 'oneTimeJob';
    const EVERY_5_MINUTES  = 'every5minutes';
    const EVERY_10_MINUTES = 'every10minutes';
    const EVERY_15_MINUTES = 'every15minutes';
    const EVERY_6_HOURS    = 'every6hours';

    /** @var array<string> */
    static array $jobList = [];

    protected string $cronJobName;
    protected string $cronJobInterval;

    /** @var LoggerInterface */
    protected $log;

    public function __construct(string $jobName, string $interval) {
        $this->cronJobName = $jobName;
        $this->cronJobInterval = $interval;
        $this->log = Logger::getLogger(get_class($this));

        add_action($this->cronJobName, array($this, 'cronEntryPoint'));

        if ($this->scheduleCron($this->cronJobName, $interval) &&
            !in_array($jobName, static::$jobList) )
        {
            array_push(static::$jobList, $jobName);
        }
    }

    private function scheduleCron(string $jobName, string $interval): bool {
        $result = false;

        if (array_key_exists ($interval, wp_get_schedules())) {

            if ( ! wp_next_scheduled($jobName) ) {
                wp_schedule_event( time(), $interval, $jobName );
            }

            $result = true;
        }
        else {
            $this->log->error('Unsupported Cron Job interval: ' . $interval);
        }

        return $result;
    }


    public function cronEntryPoint(): void
    {
        $this->runJob();

        if (self::ONE_TIME_CRONJOB == $this->cronJobInterval) {
            self::$jobList = array_diff(self::$jobList, array($this->cronJobName));
            wp_clear_scheduled_hook($this->cronJobName);
        }
    }

    public static function activate(): void
    {
    }

    public static function deactivate(): void
    {
        Logger::getLogger(get_called_class())->debug('Deactivating cron jobs', CronJob::$jobList);

        foreach(self::$jobList as $name) {
            wp_clear_scheduled_hook($name);
        }
    }

    public static function uninstall():void
    {
    }

    public static function addCustomIntervalsFilter():void
    {
        // Protect for case where running tests without WordPress
        if (function_exists('add_filter')) {
            add_filter( 'cron_schedules', [get_class(), 'addCustomIntervals']);
        }
    }

    /**
     * @param array<string, array<string, mixed>> $schedules
     *
     * @return array<string, array<string, mixed>>
     */
    public static function addCustomIntervals(array $schedules): array
    {
        $schedules[self::ONE_TIME_CRONJOB] = array(
            'interval' => 1 * MINUTE_IN_SECONDS,    // Set to 1 minute but will be cancelled after firing once
            'display'  => esc_html__( 'One Time' )
        );
        $schedules[self::EVERY_5_MINUTES] = array(
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display'  => esc_html__( 'Every Five Minutes' )
        );
        $schedules[self::EVERY_10_MINUTES] = array(
            'interval' => 10 * MINUTE_IN_SECONDS,
            'display'  => esc_html__( 'Every Ten Minutes' )
        );
        $schedules[self::EVERY_15_MINUTES] = array(
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display'  => esc_html__( 'Every Fifteen Minutes' )
        );
        $schedules[CronJob::EVERY_6_HOURS] = array(
            'interval' => 6 * HOUR_IN_SECONDS,
            'display'  => esc_html__( 'Every Six Hours' )
        );

        return $schedules;
    }

    /**
     * Wrapper method to handle the deliciousbrains vs a5hleyrich versions of
     * the WP_Background_Process class that might be loaded in memory.
     *
     * In the deliciousbrains version we can check if the background process
     * is active. In the a5shleyrich version we just assume it is not.
     *
     * @param \WP_Background_Process $bgProcess An instance of
     *      \WP_Background_Process from either the deliciousbrains or
     *      a5shleyrich packages.
     *
     * @return bool True if the job is active, false otherwise.
     */
    protected static function isJobActive(\WP_Background_Process $bgProcess): bool
    {
        $isJobActive = false;

        // Does not exist in the a5shleyrich version
        if (method_exists($bgProcess, 'is_active')) { // @phpstan-ignore function.alreadyNarrowedType
            $isJobActive = $bgProcess->is_active();
        } elseif (method_exists($bgProcess, 'is_process_running')) {
            // The method is protected so we can't call it directly
            $isJobActive = ReflectionHelper::invokeObjectMethod(
                get_class($bgProcess),
                $bgProcess,
                'is_process_running'
                );
        }

        return $isJobActive;
    }

}

interface CronJobInf
{
    public function runJob(): void;

    public static function activate(): void;
    public static function deactivate(): void;
    public static function uninstall(): void;
}

CronJob::addCustomIntervalsFilter();
