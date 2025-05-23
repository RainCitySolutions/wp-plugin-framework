<?php
declare(strict_types = 1);
namespace RainCity\WPF\Logging;

use RainCity\SingletonTrait;

/**
 * Intercept PHP error_log messages and filter out any messages that we don't
 * want sent to the log file.
 *
 * When constructing an instance, pass an array where the key for each entry
 * is a PHP error number (e.g. E_ERROR). The value for each entry should be
 * and array of string which are the error messages or partial messages to be
 * ignored. For example, '_load_textdomain_just_in_time'
 *
 * [
 *  E_NOTICE => [
 *      '_load_textdomain_just_in_time'
 *      ]
 *  ];
 */
class ErrorLogInterceptor
{
    use SingletonTrait;

    private mixed $origHandler;

    /**
     *
     * @var array<int, string[]> $ignoreErrors
     */
    private array $ignoreErrors = [];

    /**
     *
     * @param array<int, string[]> $ignoreErrors
     */
    protected function __construct(array $ignoreErrors)
    {
        $validErrors = [
            E_ERROR,
            E_WARNING,
            E_PARSE,
            E_NOTICE,
            E_CORE_ERROR,
            E_CORE_WARNING,
            E_COMPILE_ERROR,
            E_COMPILE_WARNING,
            E_USER_ERROR,
            E_USER_WARNING,
            E_USER_NOTICE,
            E_STRICT,
            E_RECOVERABLE_ERROR,
            E_DEPRECATED,
            E_USER_DEPRECATED
            ];

        $this->ignoreErrors = array_filter(
            $ignoreErrors,
            fn($key) => in_array($key, $validErrors),
            ARRAY_FILTER_USE_KEY
            );
    }

    protected function initializeInstance(): void
    {
        $this->origHandler = set_error_handler([$this, 'handler']);
    }

    public function handler(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        $result = false;

        if ($this->isIgnoredError($errno, $errstr)) {
            $result = true;
        } else {
            if (!is_null($this->origHandler)) {
                $result = call_user_func($this->origHandler, $errno, $errstr, $errfile, $errline);
            }
        }

        return $result;
    }

    private function isIgnoredError(int $errno, string $errstr): bool
    {
        $result = false;

        if (isset($this->ignoreErrors[$errno])) {
            $ignoredErrors = $this->ignoreErrors[$errno];

            foreach ($ignoredErrors as $ignoredError) {
                if (str_contains($errstr, $ignoredError)) {
                    $result = true;
                    break;
                }
            }
        }

        return $result;
    }
}
