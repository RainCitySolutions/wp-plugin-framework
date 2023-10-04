<?php
namespace RainCity\WPF\Validation;

/**
 */
abstract class BaseValidator {

    /**
     * @var     string  Slug title of the setting to which this error applies
     *      as defined via the implementation of the Settings API.
     *
     * @access  private
     */
    private $setting;

    /**
     * @var     string  Field Key of the setting to which this error applies.
     *
     * @access private
     */
    private $key;

    /**
     * @var     string  Name of the field to be validated.
     *
     * @access private
     */
    private $fieldName;

    /**
     * Creates an instance of the class and associates the specified setting
     * and key with the property of this class.
     *
     * @param    string    $setting    The title of the setting we're validating.
     * @param    string    $key        The key for the field we're validating.
     * @param    string    $fieldName  The name of the field we're validating. Defaults to ''.
     */
    public function __construct(string $setting, string $key, string $fieldName= '') {
        $this->setting = $setting;
        $this->key = $key;
        $this->fieldName = $fieldName;
    }

    /**
     * Adds an error message to WordPress' error collection to be displayed in the dashboard.
     *
     * @access   protected
     *
     * @param    string    $message    The message to display in the dashboard
     */
    public function addError( $message ) {
        if (!empty($this->fieldName)) {
            $message = $this->fieldName . ': ' . $message;
        }

        add_settings_error(
            $this->setting,
            $this->key,
            $message,
            'error'
            );
    }

}
