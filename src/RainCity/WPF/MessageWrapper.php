<?php
namespace RainCity\WPF;

use Soundasleep\Html2Text;

class MessageWrapper {
    private $fromAddress;
    private $fromName;

    public function __construct($fromName, $fromAddress) {
        $this->fromName = $fromName;
        $this->fromAddress = $fromAddress;
    }

    public function createMsgBody ($htmlBody) {
        $textBody = Html2Text::convert($htmlBody);

        return
        'Content-Type: text/plain;'.PHP_EOL
        .PHP_EOL
        .$textBody.PHP_EOL
        .'Content-Type: text/html;'.PHP_EOL
        .PHP_EOL
        .$htmlBody.PHP_EOL;
    }

    public function getContentType() {
        return 'multipart/alternative';
    }

    public function getFromName() {
        return $this->fromName;
    }

    public function getFromAddress() {
        return $this->fromAddress;
    }
}
