<?php
namespace RainCity\WPF\Logging;

use Psr\Log\LoggerInterface;
use RainCity\Logging\BaseLogger;
use RainCity\Logging\Logger;
use RainCity\Logging\RotatingFileHandler;
use RainCity\WPF\Utils;
use RainCity\WPF\WordPressPlugin;

const LOGGER_OPTION_NAME = 'raincity_wpf_logger_options';

/**
 * Object for managing loggers created on a per plugin basis.
 *
 */
class WordPressLogger extends BaseLogger
{
    /**
     * {@inheritDoc}
     * @see \RainCity\Logging\Logger
     */
    public static function getLogger(string $loggerName, ?string $loggerKey = null): LoggerInterface {
        return parent::getLogger($loggerName, Utils::getPluginPackageName());
    }

    /**
     * {@inheritDoc}
     * @see \RainCity\Logging\BaseLogger::setupLogger()
     */
    protected function setupLogger(\Monolog\Logger $logger)
    {
        parent::setupLogger($logger);

        $logger->pushProcessor(function ($record) {
            $reqId = getenv(WordPressPlugin::REQUEST_ID);

            if ($reqId) {
                $record['extra']['reqId'] = $reqId;
            }

            if (function_exists( 'wp_get_current_user' ) ) {
                $wpUser = wp_get_current_user();

                if ($wpUser->exists()) {
                    $record['extra']['userId'] = $wpUser->ID;
                    $record['extra']['userName'] = $wpUser->user_login;
                }
            }

            return $record;
        });
    }

    /**
     * {@inheritDoc}
     * @see \RainCity\Logging\BaseLogger::getLogMsgFormat()
     */
    protected function getLogMsgFormat(): string
    {
        return join(' ', [
            '%datetime%',
            '%level_name%',
            '%channel%',
            '[%extra.reqId%]',
            '(%extra.userId%/%extra.userName%):',
            ' %message% %context% %extra%'
        ])
        .PHP_EOL;
    }

    /**
     *
     * {@inheritDoc}
     * @see \RainCity\Logging\BaseLogger::getLogFile()
     */
    protected function getLogFile (): string {
        return Utils::getPluginWriteDir() . '/logs/application.log';
    }


    /**
     *
     * {@inheritDoc}
     * @see \RainCity\Logging\BaseLogger::getLogLevel()
     */
    protected function getLogLevel() {
        $pluginName = Utils::getPluginName();
        $option = get_option(LOGGER_OPTION_NAME, array());

        if (!isset($option[$pluginName])) {
            $option[$pluginName] = \Monolog\Logger::DEBUG;
            update_option(LOGGER_OPTION_NAME, $option);
        }

        return $option[$pluginName];
    }


    /**
     *
     * {@inheritDoc}
     * @see \RainCity\Logging\BaseLogger::setLogLevel()
     */
    protected function setLogLevel($level) {
        $pluginName = Utils::getPluginPackageName();

        $option = get_option(LOGGER_OPTION_NAME);

        if (!isset($option)) {
            $option = array();
        }

        $option[$pluginName] = $level;
        update_option(LOGGER_OPTION_NAME, $option);
    }

    public static function uninstall() {
        Logger::getLogger(static::BASE_LOGGER)->info('Logger::uninstall() called');

        $option = get_option(LOGGER_OPTION_NAME);
        $pluginName = Utils::getPluginPackageName();

        if (is_array($option)) {
            unset($option[$pluginName]);

            if (empty($option)) {
                delete_option(LOGGER_OPTION_NAME);
            }
            else {
                update_option(LOGGER_OPTION_NAME, $option);
            }
        }

        // close the handlers
        $logger = static::getLogger(static::BASE_LOGGER);
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof RotatingFileHandler) {
                $filename = $handler->getLogFilename();

                $pattern = $handler->getFilenameGlobPattern();

                // remove the log files
                foreach (glob($pattern, GLOB_NOSORT) as $file) {
                    unlink($file);
                }

                // remove the log directory
                rmdir(dirname($filename));

            }
            $handler->close();
        }
    }

}
