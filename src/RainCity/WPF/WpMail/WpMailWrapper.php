<?php
declare(strict_types = 1);
namespace RainCity\WPF\WpMail;

use Psr\Log\LoggerInterface;
use RainCity\Logging\Logger;

/**
 * A wrapper around sending message throught wp_mail() to consolidate the
 * formatting and filtering usually necessary.
 */
class WpMailWrapper
{
    private LoggerInterface $logger;

    private string $fromEmail;
    private string $fromName = '';
    private string $subject = '';
    private string $plainBody = '';
    private string $htmlBody = '';

    private string $replyTo = '';
    /** @var string[] */
    private array $toAddresses = [];
    /** @var string[] */
    private array $ccAddresses = [];
    /** @var string[] */
    private array $bccAddresses = [];
    /** @var string[] */
    private array $customHdrs = [];

    /** @var string[] */
    private array $attachments = [];

    private ?WPMailWrapperPriority $priority = null;

    public function __construct()
    {
        $this->logger = Logger::getLogger(self::class);
    }

    public function setFrom(string $email, ?string $name = null): static
    {
        $this->fromEmail = trim($email);
        $this->fromName = trim($name ?? '');

        return $this;
    }

    public function addTo(string $email, ?string $name = null): static
    {
        $email = trim($email);

        if (!empty($email)) {
            $this->toAddresses[] = $this->formatEmailAddress($email, $name);
        }

        return $this;
    }

    public function addCc(string $email, ?string $name = null): static
    {
        $email = trim($email);

        if (!empty($email)) {
            $this->ccAddresses[] = $this->formatEmailAddress($email, $name);
        }

        return $this;
    }

    public function addBcc(string $email, ?string $name = null): static
    {
        $email = trim($email);

        if (!empty($email)) {
            $this->bccAddresses[] = $this->formatEmailAddress($email, $name);
        }

        return $this;
    }

    public function setReplyTo(string $email, ?string $name = null): static
    {
        $email = trim($email);

        if (!empty($email)) {
            $this->replyTo = $this->formatEmailAddress($email, $name);
        }

        return $this;
    }

    public function addHeader(string $name, string $value): static
    {
        $name = trim($name);
        $value = trim($value);

        if (!empty($name) && !empty($value)) {
            $this->customHdrs[] = sprintf('%s: %s', $name, $value);
        }

        return $this;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = trim($subject);

        return $this;
    }

    public function setHtmlBody(string $htmlBody): static
    {
        $this->htmlBody = trim($htmlBody);

        return $this;
    }

    public function setPlainBody(string $plainBody): static
    {
        $this->plainBody = trim($plainBody);

        return $this;
    }

    public function addAttachment(string $path): static
    {
        $path = trim($path);

        if (!empty($path)) {
            $this->attachments[] = realpath($path);
        }

        return $this;
    }

    public function setPriority(WPMailWrapperPriority $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    private function getWpMailFailedHandler(): callable
    {
        return function (\WP_Error $wpError): void {
            $this->logger->error('wp_mail had an error: ', $wpError->get_error_messages());
            $this->logger->error('wp_mail error data: ', array($wpError->get_error_data()));

            throw WPMailWrapperException::createException($wpError);
        };
    }

    private function getFromAddressHandler(): callable
    {
        return function(): string {
            return $this->fromEmail;
        };
    }

    private function getFromNameHandler(): callable
    {
        return function(): string {
            return $this->fromName;
        };
    }

    private function getContentTypeHandler(): callable
    {
        return function(): string {
            return empty($this->htmlBody) ? 'text/plain' : 'text/html';
        };
    }

    private function getPhpInitHandler(): callable
    {
        return function (\PHPMailer\PHPMailer\PHPMailer $phpMailer): void {
            if (isset($this->priority)) {
                $phpMailer->Priority = $this->priority->value;
            }

            $this->logger->info('phpmailer_hook() called');

            $phpMailer->SMTPDebug = 2;
            $phpMailer->Debugoutput = function (string $str, bool $smtpDebug): void
            {
                $this->logger->info('PhpMailer: '.$str);
            };

            if (!empty($this->htmlBody)) {
                $phpMailer->msgHTML($this->htmlBody);
            }

            if (!empty($this->plainBody) && !empty($this->htmlBody)) {
                $phpMailer->AltBody = $this->plainBody;
            }

            if (empty($this->plainBody) && empty($this->htmlBody) && count($this->attachments)) {
                $phpMailer->AllowEmpty = true;
            }
        };
    }

    private function formatEmailAddress(string $email, ?string $name): string
    {
        return empty($name) ? $email : sprintf('%s <%s>', $name, $email);
    }

    private function resetPHPMailer(): void
    {
        // Reset properties that wp_mail does not flush by default();
        /** @var \PHPMailer $mailer */
        $mailer = $GLOBALS['phpmailer'];
        $mailer->Body = '';
        $mailer->AltBody = '';
        $mailer->ContentType = 'text/plain';
        $mailer->Priority = null;
        $mailer->AllowEmpty = false;
        $mailer->clearCustomHeaders();
        $mailer->clearReplyTos();
    }

    public function sendMessage(): bool
    {
        $result = false;

        $wpMailFailedHdlr = $this->getWpMailFailedHandler();
        $fromAddressHdlr = $this->getFromAddressHandler();
        $fromNameHdlr = $this->getFromNameHandler();
        $contentTypeHdlr = $this->getContentTypeHandler();
        $phpInitHdlr = $this->getPhpInitHandler();

        \add_action('wp_mail_failed', $wpMailFailedHdlr, 99999);
        \add_action('phpmailer_init', $phpInitHdlr, 99999);

        // Max sure our filter is called last to use our from name and address
        \add_filter('wp_mail_from', $fromAddressHdlr, PHP_INT_MAX);
        \add_filter('wp_mail_from_name', $fromNameHdlr, PHP_INT_MAX);
        \add_filter('wp_mail_content_type', $contentTypeHdlr);

        // Note how many times the phpmailer_init action has been called previously
        $initialPhpMailerCallCnt = \did_action('phpmailer_init');

        $headers = [
            'From: '.$this->formatEmailAddress($this->fromEmail, $this->fromName)
        ];

        if (!empty($this->replyTo)) {
            $headers[] = 'ReplyTo: ' . $this->replyTo;
        }

        $headers = array_merge(
            $headers,
            array_map(
                fn(string $addr) => 'Cc: ' . $addr,
                $this->ccAddresses
                )
            );

        $headers = array_merge(
            $headers,
            array_map(
                fn(string $addr) => 'Bcc: ' . $addr,
                $this->bccAddresses
                )
            );

        $headers = array_merge($headers, $this->customHdrs);

        try {
            if (function_exists('\wp_mail')) {
                $mailFunction = '\wp_mail';
            } else {
                $mailFunction = '\mail';
            }

            $result = $mailFunction(
                $this->toAddresses,
                $this->subject,
                empty($this->htmlBody) ? $this->plainBody : $this->htmlBody,
                $headers, // headers
                $this->attachments
                );
        } finally {
            \remove_filter('wp_mail_content_type', $contentTypeHdlr);
            \remove_filter('wp_mail_from_name', $fromNameHdlr, PHP_INT_MAX);
            \remove_filter('wp_mail_from', $fromAddressHdlr, PHP_INT_MAX);

            \remove_filter('phpmailer_init', $phpInitHdlr, 99999);
            \remove_action('wp_mail_failed', $wpMailFailedHdlr, 99999);

            if (\did_action('phpmailer_init') > $initialPhpMailerCallCnt) {
                // We only modify PHPMailer on the phpmailer_init hook.
                // So if it did not fire, we can't/don't have to reset it.
                $this->resetPHPMailer();
            }
        }

        return $result;
    }
}
