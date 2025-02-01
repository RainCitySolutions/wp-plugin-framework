<?php
namespace RainCity\WPF\ShortURL;

use RainCity\Singleton;
use RainCity\InterceptDie;
use RainCity\WPF\ActionFilterLoader;
use RainCity\WPF\ActionHandlerInf;


/**
 * ShortUrlHandler implements a mechanism to create short or tiny URLs and
 * subsequently redirect them to their original, long URL.
 *
 */
class ShortUrlHandler
    extends Singleton
    implements ActionHandlerInf
{
    use InterceptDie;   // Implement die method to intercept for unit testing

    const TABLE_NAME = 'raincity_wpf_short_urls';

    public static bool $verifyUrlReferences = true;

    /** @var string */
    protected string $urlPrefix;

    /**
     * {@inheritDoc}
     * @see \RainCity\Singleton::getInstance()
     */
    public static function getInstance($class = null): ShortUrlHandler
    {
        /** @var ShortUrlHandler */
        return parent::getInstance($class);
    }

    /**
     * Initialize the class and set its properties.
     *
     * This class expects a single, optional argument which is the prefix to
     * use for short URLs.
     *
     * @param   array<int, string>  $args    An array of arguments for the class.
     */
    public function __construct(array $args)
    {
        parent::__construct();

        if (!empty($args)) {
            $urlPrefix = $this->slashUrl($args[0]);

            if ($this->validatePrefix($urlPrefix)) {
                $this->urlPrefix = $urlPrefix;
            } else {
                throw new \InvalidArgumentException('Invalid format for Short URL prefix');
            }
        }
        else {
            $this->urlPrefix = '/su/';
        }
    }

    /**
     * Initialize any WordPress actions or filters.
     *
     * @param ActionFilterLoader $loader
     */
    public function loadActions(ActionFilterLoader $loader): void
    {
        $loader->addAction('plugins_loaded', self::class, 'upgradeDbTable');

        // Priority 9 so we're called earlier than the default
        $loader->addAction('template_redirect', $this, 'templateRedirectAction', 9);
    }

    /**
     * Creates or updates the database table used to store the short/long
     * URL mappings.
     *
     * @return bool Returns true on success, otherwise returns false.
     */
    public static function upgradeDbTable (): bool
    {
        global $wpdb;

        $tableName = self::getTableName($wpdb);
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$tableName} (
id int(9) UNSIGNED NOT NULL AUTO_INCREMENT,
front_end tinyint(1) NOT NULL,
short_code varchar(32) NOT NULL,
long_url varchar(255) NOT NULL,
created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (id),
UNIQUE KEY short_code (short_code),
KEY long_url (long_url)
    ) {$charsetCollate};";

        if (defined('ABSPATH')) { // Wrap in case we get invoked via unit testing
            require_once ABSPATH . 'wp-admin/includes/upgrade.php'; // @phpstan-ignore requireOnce.fileNotFound
        }

        dbDelta($sql);

        return empty($wpdb->last_error);
    }

    /**
     * Validate prefix.
     *
     * This prefix is used to form the short url.
     * e.g. a prefix of '/goal/' would result in a URL of https://mycp.org/goal/ad8f32a4.
     *
     * Prefixes must start and end with a slash, and have at least one
     * character between the slashes and not be longer than 30 characters.
     *
     * @param string $prefix A prefix to be used in the short URLs.
     *
     * @throws \Exception Thrown if the prefix does not match the required
     *      pattern.
     */
    protected function validatePrefix(string $prefix): bool
    {
        // check that the prefix matches the required pattern
        return 1 === preg_match('/^\/.{1,30}\/$/', $prefix);
    }

    /**
     * Create a short url for the provided URL.
     *
     * @param string $url The long URL to be mapped.
     * @param bool $isFrontEnd True if the URL is being generated from the
     *      front-end (UI), or False if it is being generated internally.
     *
     * @return string The fully qualified short URL.
     *
     * @throws \InvalidArgumentException Thrown if the $url parameter is
     *      empty, is an invalid URL format or doesn't refer to a valid
     *      location.
     */
    public function createShortUrl (string $url, bool $isFrontEnd = false): string
    {
        if (empty($url)) {
            throw new \InvalidArgumentException("No URL was supplied.", 400);
        }

        if (strlen($url) > 255) {
            throw new \InvalidArgumentException("Long URL must be 255 characters or less.", 400);
        }

        $url = $this->validateUrl($url);

        // if the url already exists in the database use the current short code
        $shortCode = $this->urlExists($url);
        if (false === $shortCode) {
            // if not, create a new short code
            $shortCode = $this->createShortCode($url, $isFrontEnd);
        }

        return home_url($this->urlPrefix . $shortCode);
    }


    /**
     * Create a short url for the provided URL.
     *
     * @param string $shortCode The short url code
     * @param string $longUrl The long URL to be mapped.
     * @param bool $isFrontEnd True if the URL is being generated from the
     *      front-end (UI), or False if it is being generated internally.
     *
     * @return string The fully qualified short URL.
     *
     * @throws \InvalidArgumentException Thrown if the $url parameter is
     *      empty, is an invalid URL format or doesn't refer to a valid
     *      location.
     */
    public function addShortUrl (string $shortCode, string $longUrl, bool $isFrontEnd = false): string
    {
        if (empty($shortCode)) {
            throw new \InvalidArgumentException("No Short URL supplied.", 400);
        }

        if (strlen($shortCode) > 32) {
            throw new \InvalidArgumentException("Short URL must be 32 characters or less.", 400);
        }

        if (empty($longUrl)) {
            throw new \InvalidArgumentException("No URL was supplied.", 400);
        }

        if (strlen($longUrl) > 255) {
            throw new \InvalidArgumentException("Long URL must be 255 characters or less.", 400);
        }

        $longUrl = $this->validateUrl($longUrl);

        // if the url already exists in the database
        $existingShortCode = $this->urlExists($longUrl);
        if (false === $existingShortCode) {
            try {
                $this->insertShortCode($shortCode, $longUrl, $isFrontEnd);
            }
            catch (\Exception $e) {
                if (strstr($e->getMessage(), 'Duplicate')) {
                    throw new \InvalidArgumentException(
                        "A URL has already been added with the short URL '{$shortCode}'",
                        400
                        );
                }
            }
        }
        else {
            throw new \InvalidArgumentException("The URL already has a short URL '{$existingShortCode}'", 400);
        }

        return home_url($this->urlPrefix . $shortCode);
    }

    /**
     * Validates that a URL is in a correct format.
     *
     * @param string $url   The URL to validate
     *
     * @return mixed Returns a fully qualified URL or false if the URL format
     *      is invalid.
     */
    protected function validateUrlFormat(string $url): mixed
    {
        $parsedUrl = parse_url($url);

        // If there is no host name on the URL, add the current host
        if (!isset($parsedUrl['host'])) {
            $parsedUrl = array_merge(parse_url(home_url()), $parsedUrl);
        }

        // If there is a path and it doesn't start with a slash, add one
        if (isset($parsedUrl['path']) && substr($parsedUrl['path'], 0, 1) !== '/') {
            $parsedUrl['path'] = '/' . $parsedUrl['path'];
        }

        // Compose a fully qualified URL
        $fqUrl = $this->build_url($parsedUrl);

        // Check to format of the URL
        $result = filter_var($fqUrl, FILTER_VALIDATE_URL);

        return $result === false ? $result : $fqUrl;
    }

    /**
     * Validates that a URL is a correct format and that,<br>
     * - if the host name matches ours (explicitly, or implicitly) that that
     *      the page exists;<br>
     * - if the host name doesn't match ours, we can reach the URL.
     *
     * @param string $inUrl The URL provided
     *
     * @throws \InvalidArgumentException Thrown if the URL is invalid or
     *      doesn't refer to a valid location.
     *
     * @return string The URL that should be stored in the database.
     */
    protected function validateUrl(string $inUrl): string
    {
        $outUrl = $inUrl;

        $fqUrl = $this->validateUrlFormat($inUrl);
        if (false === $fqUrl) {
            throw new \InvalidArgumentException("URL does not have a valid format.", 400);
        }

        // If the URL contains our hostname
        if (false !== strstr($fqUrl, home_url())) {
            $outUrl = substr($fqUrl, strlen(home_url()));

            // Check if the URL refers to a site page
            $pagePath = get_page_by_path($outUrl, OBJECT, array('page', 'post', 'attachement'));
            if (!$pagePath &&
                self::$verifyUrlReferences &&
                false === $this->verifyUrlExists($fqUrl)) {
                    throw new \InvalidArgumentException("Page or URL does not appear to exist.", 404);
                }
        } else {
            if (self::$verifyUrlReferences && false === $this->verifyUrlExists($fqUrl)) {
                throw new \InvalidArgumentException("URL does not appear to exist.", 404);
            }
        }

        return $outUrl;
    }

    /**
     * Given the parts of a URL (from parse_url), recompose a URL string.
     *
     * @param array<string, string> $parts An associative array of URL parts.
     *
     * @return string A composed URL string.
     */
    protected function build_url(array $parts): string
    {
        return join (
            '',
            array (
                isset($parts['scheme']) ? "{$parts['scheme']}:" : '',
                (isset($parts['user']) || isset($parts['host'])) ? '//' : '',
                isset($parts['user']) ? "{$parts['user']}" : '',
                isset($parts['pass']) ? ":{$parts['pass']}" : '',
                isset($parts['user']) ? '@' : '',
                isset($parts['host']) ? "{$parts['host']}" : '',
                isset($parts['port']) ? ":{$parts['port']}" : '',
                isset($parts['path']) ? "{$parts['path']}" : '',
                isset($parts['query']) ? "?{$parts['query']}" : '',
                isset($parts['fragment']) ? "#{$parts['fragment']}" : ''
                )
            );
    }

    /**
     * Verifies that a URL exists by attempting to fetch it.
     *
     * @param string $fqUrl The fully qualified URL to verify
     *
     * @return bool Returns true if the URL is accessible, otherwise returns
     *      false.
     */
    protected function verifyUrlExists(string $fqUrl): bool
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fqUrl);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return !empty($response) && $response != 404;
    }

    /**
     * Check if a URL exists in the database.
     *
     * @param string $url The URL to check for.
     *
     * @return string|bool Returns the shortCode for the URL if it exists,
     *      otherwise returns false.
     */
    protected function urlExists(string $url): string|bool
    {
        global $wpdb;

        $tableName = self::getTableName($wpdb);
        $shortCode = $wpdb->get_var($wpdb->prepare("SELECT short_code FROM {$tableName} WHERE long_url = %s", $url) );

        return $shortCode ?? false;
    }

    /**
     * Checks if a short code exists in the database.
     *
     *
     * @param string $shortCode A short code
     *
     * @return bool Returns true if the short code exists, otherwise returns
     *      false.
     */
    protected function shortCodeExists(string $shortCode): bool
    {
        global $wpdb;

        $tableName = self::getTableName($wpdb);
        $result = $wpdb->get_var($wpdb->prepare("SELECT 1 FROM {$tableName} WHERE short_code = %s", $shortCode) );

        return $result ? true : false;
    }

    /**
     * Creates a unique short code for the URL and stores the mapping in the
     * database.
     *
     * @param string $url The long URL
     * @param bool $isFrontEnd Indication of whether the code is being
     *      created from the front-end(UI) or by the system.
     *
     * @return string A short code that is mapped to the long URL.
     */
    protected function createShortCode(string $url, bool $isFrontEnd = false): string
    {
        $shortCode = null;

        do {
            $shortCode = hash('crc32', $url . microtime());
        } while ($this->shortCodeExists($shortCode));

        $this->insertShortCode($shortCode, $url, $isFrontEnd);

        return $shortCode;
    }

    /**
     * Inserts a short code and URL into the database.
     *
     * @param string $shortCode The shortCode
     * @param string $url The long URL
     * @param bool $isFrontEnd Indication of whether the code is being
     *      created from the front-end(UI) or by the system.
     *
     * @return string A short code that is mapped to the long URL.
     */
    protected function insertShortCode(string $shortCode, string $url, bool $isFrontEnd = false): string
    {
        global $wpdb;

        $tableName = self::getTableName($wpdb);

        $insertResult = $wpdb->insert($tableName,
            array(
                'short_code' => $shortCode,
                'long_url' => $url,
                'front_end' => $isFrontEnd
            ),
            array(
                '%s',
                '%s',
                '%d'
            ));
        if (false === $insertResult) {
            throw new \Exception($wpdb->last_error);    // NOSONAR
        }

        return $shortCode;
    }

    /**
     * Fetch the URLs previously generated by the front-end(UI)
     *
     * @return array<\stdClass> An array of \stdClass objects.
     */
    public function getFrontEndUrls(): array
    {
        global $wpdb;
        $urlArray = array();

        $tableName = self::getTableName($wpdb);

        $urlRows = $wpdb->get_results("SELECT short_code, long_url FROM {$tableName} WHERE front_end = 1");

        foreach ($urlRows as $urlRow ) {
            $urlArray[] = (object) [
                'shortCode' => $urlRow->short_code,
                'shortUrl' => $this->urlPrefix . $urlRow->short_code,
                'longUrl' => $urlRow->long_url
            ];
        }

        return $urlArray;
    }


    /**
     * Delete a short URL entry from the database.
     *
     * @param string $shortCode The code for the short URL.
     */
    public function deleteShortCode(string $shortCode): void
    {
        global $wpdb;

        if ($this->shortCodeExists($shortCode)) {
            $tableName = self::getTableName($wpdb);
            $wpdb->delete($tableName, array('short_code' => $shortCode) );
        }
    }

    /**
     * WordPress action hook for 'template_redirect'.
     *
     * When called, checks if the requested URI starts with our URL prefix.
     * If it does, and the remainder of the URI is a short code in the
     * database, the user is redirected to the URL mapped to the short code.
     */
    public function templateRedirectAction (): void
    {
        if (isset($_SERVER['REQUEST_URI']) &&
            strpos($_SERVER['REQUEST_URI'], $this->urlPrefix) === 0)
        {
            global $wpdb;

            // Extract the path portion of the URL (no query params)
            $uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

            $shortCode = substr($uriPath, strlen($this->urlPrefix));
            if (!empty($shortCode)) {
                $tableName = self::getTableName($wpdb);
                $stmt = $wpdb->prepare("SELECT long_url FROM {$tableName} WHERE short_code = %s", $shortCode);
                $longUrl = $wpdb->get_var($stmt);
                if (isset($longUrl)) {
                    wp_redirect( $longUrl );
                    $this->die();
                }
            }
        }
    }

    /**
     * Helper to provide the database table name.
     *
     * @param object $wpdb  A Wpdb instance
     *
     * @return string The name of the database table.
     */
    private static function getTableName(object $wpdb): string
    {
        return $wpdb->prefix . self::TABLE_NAME;
    }

    /**
     * Helper to ensure a URL prefix is in the correct format.
     *
     * @param string $url A URL path.
     *
     * @return string The slashed URL.
     */
    private static function slashUrl(string $url): string
    {
        $url = ltrim($url, " \n\r\t\v\0\\\/");
        $url = rtrim($url, " \n\r\t\v\0\\\/");
        $url = '/' . $url . '/';

        return $url;
    }
}
