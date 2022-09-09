<?php
namespace RainCity\WPF\Validation;

/**
 * This class is responsible for validating email addresses.
 */
class EmailValidator extends BaseValidator {

    /**
     * Determines if the specified input is valid.
     *
     * @param   mixed   $input  A single address as a string or multiple addresses as an array of strings
     * @return  bool            True if the input is valid; otherwise, false
     */
    public function is_valid( $input ) {

        $is_valid = true;

        if (is_string($input) || is_array($input)) {
            $addrs = is_array($input) ? $input : array($input);

            foreach ($addrs as $addr) {
                if (!is_email(trim($addr))) {
//                if (!preg_match (
//                    '/^([a-zA-Z\-0-9\.]+@)([a-zA-Z\-0-9\.]+)$/',
//                    trim($addr)) ) {
                        $is_valid = false;
                        break;
                }
            }
        }
        else {
            $is_valid = false;
        }

        // If the input is an empty string, add the error message and mark the validity as false
        if ( ! $is_valid ) {

            $this->add_error('Invalid email address.' );
            $is_valid = false;
        }

        return $is_valid;

    }
}
