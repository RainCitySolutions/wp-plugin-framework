<?php
namespace RainCity\WPF\Validation;

class StringValidator extends BaseValidator
{
    /**
     * Determines if the specified input is valid.
     *
     * @param    string    $input    The string
     * @return   bool                True if the input is valid; otherwise, false
     */
    public function isValid( $input ) {
        $isValid = true;

        // If the input is an empty string, add the error message and mark the validity as false
        if ( '' == trim( $input ) ) {

            $this->addError('You must provide a value.' );
            $isValid = false;
        }

        return $isValid;
    }
}
