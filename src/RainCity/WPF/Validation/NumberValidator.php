<?php
namespace RainCity\WPF\Validation;

class NumberValidator extends BaseValidator
{
    private int $min = 0;
    private int $max = PHP_INT_MAX;

    /**
     * Sets the allowed valid range.
     *
     * @param   int $min    The minimum value allowed
     * @param   int $max    The maximum value allowed
     */
    public function setRange(int $min, int $max): void
    {
        $this->min = $min;
        $this->max = $max;
    }

    /**
     * Determines if the specified input is valid.
     *
     * @param    string    $input    The string
     * @return   bool                True if the input is valid; otherwise, false
     */
    public function isValid(string $input): bool
    {
        $isValid = true;

        // If the input is an empty string, add the error message and mark the validity as false
        if ( '' == trim( $input ) ) {

            $this->addError('You must provide a value.' );
            $isValid = false;
        }
        else {
            if (is_numeric($input)) {
                $value = intval($input);
                if ($value >= $this->min && $value <= $this->max) {
                    $isValid = true;
                }
                else {
                    $this->addError('Value not within range ('.$this->min.'-'.$this->max.').' );
                    $isValid = false;
                }
            }
            else {
                $this->addError('Not a number.' );
                $isValid = false;
            }
        }

        return $isValid;
    }
}
