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
    public function is_valid( $input ) {
        $is_valid = true;

        // If the input is an empty string, add the error message and mark the validity as false
        if ( '' == trim( $input ) ) {

            $this->add_error('You must provide a value.' );
            $is_valid = false;
        }

        return $is_valid;
    }
}
