<?php
namespace RainCity\WPF;

use Psr\Log\LoggerInterface;
use RainCity\Logging\Logger;

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

    static array $jobList = array();

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
}

interface CronJobInf
{
    public function runJob();

    public static function activate(): void;
    public static function deactivate(): void;
    public static function uninstall(): void;
}

CronJob::addCustomIntervalsFilter();
