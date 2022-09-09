<?php
namespace RainCity\WPF\Validation;

/**
 * This class is responsible for validating email addresses.
 *
 * @since         1.0.0
 *
 * @implements    Input_Validator
 * @package       Acme/classes
 */
class UrlValidator extends StringValidator {

    /**
     * Determines if the specified input is valid.
     *
     * @param   mixed   $input  A single address as a string or multiple addresses as an array of strings
     * @return  bool            True if the input is valid; otherwise, false
     */
    public function is_valid( $input ) {
        $is_valid = parent::is_valid($input);

        if ($is_valid &&
            !filter_var($input, FILTER_VALIDATE_URL) && // full URL
            !filter_var('http://'.$input, FILTER_VALIDATE_URL) && // host name only
            !strpos($input, '/') == 0)
        {
                $is_valid = false;
        }

        return $is_valid;
    }
}
