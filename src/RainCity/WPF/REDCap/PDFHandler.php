<?php
namespace RainCity\WPF\REDCap;

use RainCity\Logging\Logger;
use RainCity\REDCap\RedCapProject;
use RainCity\TestHelper\InterceptDie;
use RainCity\WPF\ActionFilterLoader;
use RainCity\WPF\ActionHandlerInf;

class PDFHandler
    implements ActionHandlerInf
{
    use InterceptDie;   // Implement die method to intercept for unit testing

    private $logger;
    private $redcapProject;

    const FETCH_PDF_ACTION = 'FetchRedcapPdf';

    public function __construct(RedCapProject $redcapProject) {
        $this->logger = Logger::getLogger(get_class($this));
        $this->redcapProject = $redcapProject;
    }

    /**
     * Initialize any actions or filters for the helper.
     *
     * @param ActionFilterLoader $loader
     */
    public function loadActions(ActionFilterLoader $loader) {
        $loader->add_action('wp_ajax_'.self::FETCH_PDF_ACTION, $this, 'fetchPdf' );
    }

    /**
     * WordPress action method for handling AJAX requests for the PDF of a
     * form/instrument.
     *
     */
    public function fetchPdf() {
        if (isset($_REQUEST['data']) &&
            isset($_REQUEST['nonce'])) {
            $dataObj = PDFHandlerData::fromString($_REQUEST['data']);
            if (isset($dataObj) &&
                wp_verify_nonce( $_REQUEST['nonce'], $dataObj->getForm()) )
            {
                $filename = sanitize_file_name( $dataObj->getPdf());
                if (!preg_match('/.*\.pdf$/i', $filename) ) {
                    $filename .= '.pdf';
                }
                $pdfFile = $this->redcapProject->exportPdfFileOfInstruments(null, $dataObj->getRecord(), $dataObj->getEvent(), $dataObj->getForm());

                if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                }
                else {
                    header("Content-Disposition: attachment; filename={$filename}");
                    echo $pdfFile;
                }
            }
            $this->die();
        }
        else {
            $this->die("No naughty business please");
        }
    }


    /**
     * Creates the URL for making a request to the server for the PDF
     * associated with the specified record/instrument/event.
     *
     * @param string $recordId  A REDCap record id
     * @param string $formName  A REDCap form/instrument name
     * @param string $eventName A REDCap event name or null if not applicable
     * @param string $pdfName   The name of the PDF file to be generated
     *
     * @return string|NULL A URL to be used in requesting the PDF. If called
     *      in a non-WordPress environment returns null.
     */
    public static function createPdfLink(string $recordId, string $formName, string $eventName, string $pdfName): ?string {
        $link = null;

        $nonce = wp_create_nonce($formName);

        $dataObj = new PDFHandlerData($recordId, $formName, $eventName, $pdfName);
        $urlStr = $dataObj->toString();

        $link = admin_url('admin-ajax.php?action='.self::FETCH_PDF_ACTION."&nonce={$nonce}&data={$urlStr}");

        return $link;
    }
}
