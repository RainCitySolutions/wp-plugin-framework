<?php
namespace RainCity\WPF;

use Soundasleep\Html2Text;

class MessageWrapper {
    private string $fromAddress;
    private string $fromName;

    public function __construct(string $fromName, string $fromAddress)
    {
        $this->fromName = $fromName;
        $this->fromAddress = $fromAddress;
    }

    public function createMsgBody (string $htmlBody): string
    {
        $textBody = Html2Text::convert($htmlBody);

        return
            'Content-Type: text/plain;'.PHP_EOL
            .PHP_EOL
            .$textBody.PHP_EOL
            .'Content-Type: text/html;'.PHP_EOL
            .PHP_EOL
            .$htmlBody.PHP_EOL;
    }

    public function getContentType(): string
    {
        return 'multipart/alternative';
    }

    public function getFromName(): string
    {
        return $this->fromName;
    }

    public function getFromAddress(): string
    {
        return $this->fromAddress;
    }
}
