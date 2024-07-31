<?php
namespace RainCity\WPF;

use RainCity\Logging\Logger;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;


class HttpResponseFilter
{
    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var int */
    private int $respCode;

    /** @var string */
    private string $respMsg;

    /** @var string */
    private string $respBody;

    /** @var ClientException */
    private ClientException $clientException;

    /**
     * Hooks into the 'http_response' filter.
     *
     * When an instance goes out of scope the filter is removed.
     */
    public function __construct()
    {
        $this->logger = Logger::getLogger(get_class($this));

        add_filter('http_response', array($this, 'capture'), 10, 3);
    }

    public function __destruct()
    {
        remove_filter('http_response', array($this, 'capture'), 10);
    }


    /**
     *
     * @param array<mixed> $resp
     * @param array<string, mixed> $reqArgs
     * @param string|UriInterface $url
     *
     * @return array<mixed>
     */
    public function capture (array $resp, array $reqArgs, string|UriInterface $url): array
    {
        $this->logger->debug('Capturing HTTP Response for {url}', array('url' => $url));

        $this->respCode = wp_remote_retrieve_response_code( $resp );
        $this->respMsg = wp_remote_retrieve_response_message( $resp );
        $this->respBody = wp_remote_retrieve_body( $resp );

        if ($this->respCode >= 400 && $this->respCode <= 499) {
            $respHeaders = wp_remote_retrieve_headers( $resp );
            if (is_object($respHeaders)) {
                $respHeaders = $respHeaders->getAll();
            }

            $this->clientException = new ClientException(
                $this->respMsg,
                new \GuzzleHttp\Psr7\Request (
                    $reqArgs['method'],
                    $url,
                    $reqArgs['headers'],
                    null,
                    $reqArgs['httpversion']
                    ),
                new \GuzzleHttp\Psr7\Response (
                    wp_remote_retrieve_response_code( $resp ),
                    $respHeaders,
                    wp_remote_retrieve_body( $resp ),
                    $resp["http_response"]->get_response_object()->protocol_version,
                    wp_remote_retrieve_response_message( $resp ))
                );
        }

        // make filter instance a one time use?
        remove_filter('http_response', array($this, 'capture'), 10);

        return $resp;
    }

    public function getRespCode(): int
    {
        return $this->respCode;
    }

    public function getRespMsg(): string
    {
        return $this->respMsg;
    }

    public function getRespBody(): string
    {
        return $this->respBody;
    }

    public function getException(): ClientException
    {
        return $this->clientException;
    }
}
