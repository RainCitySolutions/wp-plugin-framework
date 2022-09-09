<?php
declare(strict_types=1);
namespace RainCity\WPF;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use RainCity\Logging\Logger;

class PDFService
{
    protected $svcUrl;
    protected $xsltFilename;
    protected $supportFiles;

    private $isSvcActive;
    private $httpClient;
    private $logger;

    /**
     * Constructs a PDF Serivce instance.
     *
     * @param string $svcUrl The URL to the PDF Web Service
     * @param string $xsltFilename The fully qualified name of the XSLT file.
     * @param array $supportFiles An array of fully qualified filenames to be
     *      included with the request to the PDF service. These might include
     *      background images, or child XSLT files referenced by the XSLT file.
     */
    public function __construct(string $svcUrl, string $xsltFilename, array $supportFiles) {
        $this->isSvcActive = false;

        $this->logger = Logger::getLogger(get_class());

        $this->svcUrl = $svcUrl;
        $this->xsltFilename = $xsltFilename;
        $this->supportFiles = $supportFiles;

        if (isset($svcUrl)) {
            $this->httpClient = new Client([
                // Base URI is used with relative requests
                'base_uri' => $svcUrl,
                // You can set any number of default request options.
                'timeout'  => 30.0,
                'read_timeout' => 30.0,
                'cookies' => true,
                'verify' => true
            ]);

            $this->isSvcActive = filter_var($svcUrl, FILTER_VALIDATE_URL) !== false ? self::isValidServiceUrl($this->httpClient, $svcUrl) : false;
        }
    }

    public static function isValidServiceUrl(Client $httpClient, string $url) {
        $isValid = false;

        // get the initial login page (just contains the email/username field
        try {
            $request = new Request('OPTIONS', $url);
            $response = $httpClient->send($request, ['http_errors' => false]);
            if (200 === $response->getStatusCode()) {
                $verbs = $response->getHeader('Allow');
                if (is_array($verbs) && count($verbs) > 0 && 'POST,OPTIONS' === $verbs[0]) {
                    $isValid = true;
                }
            }
        }
        catch (\Exception $e) {
            Logger::getLogger(get_class())->warning('Unable to retrieve Options from URL {url}, {error}', array('url' => $url, 'error' => $e->getMessage()));
        }

        return $isValid;
    }

    public function isServiceActive () {
        return $this->isSvcActive;
    }


    /**
     * Sends the XML to the PDF Service and retrieves the PDF file.
     *
     * @param string $xmlFilename   The fully qualified name of the XML file.
     * @param string $pdfFilename   The fully qualified name of the PDF file.
     *
     * @return boolean  Returns true if the PDF file was retrieved, otherwise false.
     */
    public function fetchPDF(string $xmlFilename, string $pdfFilename) {
        $result = false;

        $multipartData = array();

        // set the names of the input and output files
        array_push($multipartData, array('name' => 'xmlfile', 'contents' => basename($xmlFilename)));
        array_push($multipartData, array('name' => 'xsltfile', 'contents' => basename($this->xsltFilename)));
        array_push($multipartData, array('name' => 'pdffile', 'contents' => basename($pdfFilename)));

        // Add the support files
        foreach ($this->supportFiles as $file) {
            array_push($multipartData, array(
                'name' => 'files[]',
                'filename' => $file,
                'contents' => fopen($file, 'r')
            ));
        }

        // add the xml file
        array_push($multipartData, array(
            'name' => 'files[]',
            'filename' => $xmlFilename,
            'contents' => fopen($xmlFilename, 'r')
        ));

        // add the XSLT file
        array_push($multipartData, array(
            'name' => 'files[]',
            'filename' => $this->xsltFilename,
            'contents' => fopen($this->xsltFilename, 'r')
        ));

        // send the conversion request to the service as Multipart form data
        $response = $this->httpClient->post('', ['multipart' => $multipartData]);

        // If the request was successful, extract the URL of the PDF file and fetch it
        if (201 === $response->getStatusCode()) {
            $location = $response->getHeader('Location');

            if (!empty($location)) {
                // fetch the PDF file and write it to the specified file
                $response = $this->httpClient->get($location[0], ['sink' => $pdfFilename]);
                if (200 === $response->getStatusCode()) {
                    $result = true;
                }
            }
        }

        return $result;
    }
}
