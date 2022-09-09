<?php
namespace RainCity\WPF;

use TheIconic\Tracking\GoogleAnalytics\Analytics;
use TheIconic\Tracking\GoogleAnalytics\AnalyticsResponse;


/**
 * Helper class to provide metrics to Google Analytics
 */
class GoogleAnalyticsHelper
{
    const UNKNOWN_CLIENT_IDENTIFIER = 'unknownClient';
    const GOOGLE_COOKIE_NAME = '_ga';

    /** @var Analytics */
    private $analytics;

    /** @var bool */
    private $useRemoteIp = true;

    /**
     * Construct an instance.
     *
     * @param string $trackingId The tracking ID to use in requests sent to Google.
     *
     * @throws \InvalidArgumentException Thrown if the tracking id is not provided or is blank.
     */
    public function __construct(string $trackingId) {
        if ('' != trim($trackingId)) {
            $this->analytics = new Analytics(true);

            $this->analytics
                ->setProtocolVersion('1')
                ->setTrackingId($trackingId);

            // Set default client Id
            if (isset($_COOKIE[self::GOOGLE_COOKIE_NAME])) {
                $this->analytics->setClientId($_COOKIE[self::GOOGLE_COOKIE_NAME]);
            } else {
                $this->analytics->setClientId(self::UNKNOWN_CLIENT_IDENTIFIER);
            }
        }
        else {
            throw new \InvalidArgumentException('Invalid or missing tracking ID');
        }
    }

    /**
     * Set the client id to use for events sent to Google.
     *
     * By default the client id will be set to the value of the '_ga' cookie
     * or 'unknowClient' if the cookie is not set.
     *
     * @param string $clientId A client identifier.
     *
     * @throws \InvalidArgumentException Thrown if the client id is not provided or is blank.
     */
    public function setClientId(string $clientId): void {
        if ('' == trim($clientId)) {
            throw new \InvalidArgumentException('Client ID not provided or blank');
        } else {
            $this->analytics->setClientId($clientId);
        }
    }

    /**
     * Indicate whether to use the remote IP address.
     *
     * Enabled by default.
     *
     * @param bool $useRemoteIp Set to true to enable, false to disable.
     */
    public function setUseRemoteIP(bool $useRemoteIp): void {
        $this->useRemoteIp = $useRemoteIp;
    }

    /**
     * Sends an event to Google using the specified attributes.
     *
     * @param string $category The category for the event.
     * @param string $action   The action or event that occured.
     * @param string $label    Additional inforamation about the event.
     * @param int $value (Optional) A value to be associated with the event
     *      label. If specified must be a positive value.
     *
     * @return bool Returns true if the event is succesfully sent, otherwise
     *      returns false.
     *
     * @throws \InvalidArgumentException Thrown if any of the parameters are invalid.
     */
    public function sendEvent(string $category, string $action, string $label, int $value = null): bool {
        $result = false;

        if ('' == trim($category) ||
            '' == trim($action) ||
            '' == trim($label) ||
            (isset($value) && 0 > $value) )
        {
            throw new \InvalidArgumentException('An argument to sendEvent is invalid');
        }
        else {
            // Build the GA hit using the Analytics class methods
            $this->analytics
                ->setEventCategory(trim($category))
                ->setEventAction(trim($action))
                ->setEventLabel(trim($label));

            if (isset($value)) {
                $this->analytics->setEventValue($value);
            }

            if ($this->useRemoteIp && isset($_SERVER['REMOTE_ADDR'])) {
                $this->analytics->setIpOverride($_SERVER['REMOTE_ADDR']); // remote ip
            }

            /** @var AnalyticsResponse */
            $resp = $this->analytics->sendEvent();
            if (200 == $resp->getHttpStatusCode()) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Set the value for a Google Analytics Custom Dimension.
     *
     * Note: The custom dimensions must be defined in GA prior to their use.
     *
     * @param int $index The index of a custom dimension.
     * @param string $value The value to send for the custom dimension.
     */
    public function setCustomDimension(int $index, string $value) {
        if ($index <= 0) {
            throw new \InvalidArgumentException('GA Custom Dimension index must be greater than 0.');
        }

        if (!isset($value)) {
            throw new \InvalidArgumentException('Value for GA Custom Dimension must be provided.');
        }

        $this->analytics->setCustomDimension($value, $index);
    }

    /**
     * Set the value for a Google Analytics Custom Metric.
     *
     * Note: The custom metrics must be defined in GA prior to their use.
     *
     * @param int $index The index of a custom metric.
     * @param int $value The value to send for the custom metric.
     */
    public function setCustomMetric(int $index, int $value) {
        if ($index <= 0) {
            throw new \InvalidArgumentException('GA Custom Metric index must be greater than 0.');
        }

        $this->analytics->setCustomMetric($value, $index);
    }

    /**
     * Sets the underlying Analytics object.
     *
     * Used for unit testing.
     *
     * @internal
     * @param Analytics $analyticsObj
     *
     * @return $this
     */
    public function setAnalytics(Analytics $analyticsObj)
    {
        $this->analytics = $analyticsObj;

        return $this;
    }

}
