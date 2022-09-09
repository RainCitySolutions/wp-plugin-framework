<?php
namespace RainCity\WPF\Validation;

class NumberValidator extends BaseValidator
{
    private $min = 0;
    private $max = PHP_INT_MAX;

    /**
     * Sets the allowed valid range.
     *
     * @param   int $min    The minimum value allowed
     * @param   int $max    The maximum value allowed
     */
    public function setRange($min, $max) {
        $this->min = $min;
        $this->max = $max;
    }

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
        else {
            if (is_numeric($input)) {
                $value = intval($input);
                if ($value >= $this->min && $value <= $this->max) {
                    $is_valid = true;
                }
                else {
                    $this->add_error('Value not within range ('.$this->min.'-'.$this->max.').' );
                    $is_valid = false;
                }
            }
            else {
                $this->add_error('Not a number.' );
                $is_valid = false;
            }
        }

        return $is_valid;
    }
}
