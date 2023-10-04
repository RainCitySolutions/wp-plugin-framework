<?php
namespace RainCity\WPF;

use RainCity\Logging\Logger;
use RuntimeException;

/**
 * Class used to manage cookies with an WordPress environment.
 *
 * The value used for the HTTP cookie is a unique hash value. The hash value
 * is used then used as the key for the actual value which is stored as
 * WordPress transient data. This prevents any internal data from being used
 * in the cookie value and keeps the HTTP cookie value to a fixed length.
 */
class WordPressCookie
{
    private $logger;
    private $cookieName;
    private $lifetime;
    private $hashValue;

    /**
     * Constructs an instance of WordPressCookie.
     *
     * If the cookie has previously been set, the value of the cookie is used
     * as the hash value. Otherwise, assuming no data has been sent to the
     * browse a hash value is created and the cookie is set to that value.
     *
     * <p>In the event that data has already been sent to the browser and the
     * cookie was not previously set a warning is logged but
     *
     * @param string $cookieName The name of the cookie.
     * @param string $hashKey A key to be used in creating the hash value.
     * @param int $lifetime The time, in seconds, before the cookie should expire. Defaults to 24 hours.
     * @param string $path The HTTP path the cookie should apply to. Defaults to '/'.
     *
     * @throws WordPressCookieException Thrown if the cookie isn't currently
     *     set and cannot be set because data has already been sent to the
     *     browser. This would generally indicate that the object was
     *     instantiated at the wrong place in the code.
     */
    public function __construct(string $cookieName, string $hashKey, int $lifetime = 86400, string $path = '/') {
        $this->cookieName = $cookieName;
        $this->lifetime = $lifetime;

        $this->logger = Logger::getLogger(get_class($this));

        if (isset($_COOKIE[$this->cookieName])) {
            $this->hashValue = $_COOKIE[$this->cookieName];
        }
        else {
            $file = '';
            $line = 0;

            if (headers_sent ($file, $line)) {
                $msg = sprintf('Unable to create cookie, headers already sent, line %d in %s', $line, $file);
                $this->logger->warning($msg);
                throw new WordPressCookieException($msg);
            }
            else {
                $this->hashValue = hash('sha256', $hashKey . '-' . uniqid());

                setcookie($this->cookieName, $this->hashValue, time() + $this->lifetime, $path, '', false, true);
            }
        }
    }

    /**
     * Gets the value for the cookie.
     *
     * If the cookie hash value could not be determined during construction
     * the value will not be returned. This should only happen if the cookie
     * was not present in the request and data had already been sent to the
     * browser.
     *
     * @return NULL|mixed Returns the value or false if the value does not exist.
     */
    public function getCookieValue() {
        $value = null;

        if (isset($this->hashValue)) {
            $transient = get_transient($this->hashValue);

            if ($transient !== false) {
                $value = $transient;
            }
        }

        return $value;
    }

    /**
     * Sets the value for the cookie.
     *
     * If the cookie hash value could not be determined during construction
     * the value will not be set/saved. This should only happen if the cookie
     * was not present in the request and data had already been sent to the
     * browser.
     *
     * @param mixed $value The value to be associated with the cookie.
     *
     * @return boolean Returns true if the value is saved, otherwise returns false.
     */
    public function setCookieValue($value) {
        $result = false;

        if (isset($this->hashValue)) {
            $result = set_transient($this->hashValue, $value, $this->lifetime);
        }

        return $result;
    }

    /**
     * Delete the cookie value.
     *
     * Removes the value associated with the cookie.
     */
    public function deleteCookieValue() {
        if (isset($this->hashValue)) {
            delete_transient($this->hashValue);
        }
    }

    /**
     * Delete the cookie and the value associated with it.
     *
     * The cookie value is removed and the HTTP cookie is set to expired in
     * the past so it is removed from the browser. In the event that data has
     * already been sent to the brower the cookie is/cannot be expired.
     */
/*
 * Removing for now (20200206).
 * deleteCookieValue() allows the removal of the value associated with the cookie,
 * allowing a hash to be reused, temporarily at least.
    public function deleteCookie() {
        if (isset($this->hashValue)) {
            delete_transient($this->hashValue);

            $file = '';
            $line = 0;

            if (headers_sent ($file, $line)) {
                $this->logger->warning(
                    'Unable to delete cookie, headers already sent, line {line} in {file}',
                     array('line' => $line, 'file' => $file)
                     );
            }
            else {
                setcookie($this->cookieName, $this->hashValue, time() - 3600);
            }
        }
    }
*/
}

/**
 * Exception thrown if a WordPressCookie object is instantiated but the cookie
 * is not currently set and cannot be set because data has already been sent
 * to the browser.
 */
class WordPressCookieException extends RuntimeException {

}

