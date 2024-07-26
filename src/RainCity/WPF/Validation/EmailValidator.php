<?php
namespace RainCity\WPF\Validation;

/**
 * This class is responsible for validating email addresses.
 */
class EmailValidator extends BaseValidator
{
    /**
     * Determines if the specified input is valid.
     *
     * @param   mixed   $input  A single address as a string or multiple addresses as an array of strings
     * @return  bool            True if the input is valid; otherwise, false
     */
    public function isValid(string $input): bool
    {
        $isValid = true;

        if (is_string($input) || is_array($input)) {
            $addrs = is_array($input) ? $input : array($input);

            foreach ($addrs as $addr) {
                if (!is_email(trim($addr))) {
//                if (!preg_match (
//                    '/^([a-zA-Z\-0-9\.]+@)([a-zA-Z\-0-9\.]+)$/',
//                    trim($addr)) ) {
                        $isValid = false;
                        break;
                }
            }
        }
        else {
            $isValid = false;
        }

        // If the input is an empty string, add the error message and mark the validity as false
        if ( ! $isValid ) {

            $this->addError('Invalid email address.' );
            $isValid = false;
        }

        return $isValid;
    }
}
