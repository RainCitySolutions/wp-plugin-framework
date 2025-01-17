<?php
declare(strict_types = 1);
namespace RainCity\WPF\WpMail;

use RuntimeException;
use Throwable;

final class WPMailWrapperException extends RuntimeException
{
    private string $debugData;

    /**
     *
     * @param string $message
     * @param string $debugData
     * @param Throwable $previous
     */
    public function __construct(string $message = '', string $debugData = '', Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->debugData = $debugData;
    }

    public static function createException(\WP_Error $error): WPMailWrapperException
    {
        /** @var string[] $errors */
        $errors = $error->get_error_messages('wp_mail_failed');
        $message = implode("\n", $errors);

        $extra = json_encode($error->errors, JSON_THROW_ON_ERROR);

        return new self(
            sprintf('wp_mail() failure. Message: [%s].', $message),
            sprintf('Errors: [%s].', $extra)
            );
    }

    public function getDebugData(): string
    {
        return $this->debugData;
    }
}
