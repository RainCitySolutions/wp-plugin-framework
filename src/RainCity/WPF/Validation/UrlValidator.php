<?php
namespace RainCity\WPF\Validation;

/**
 * This class is responsible for validating URLs.
 */
class UrlValidator extends StringValidator
{
    /**
     * Determines if the specified input is valid.
     *
     * @param string|array<string> $input A single address as a string or
     *      multiple addresses as an array of strings
     *
     * @return bool True if the input is valid; otherwise, false
     */
    public function isValid(string|array $input): bool
    {
        $isValid = parent::isValid($input);

        if ($isValid &&
            !filter_var($input, FILTER_VALIDATE_URL) && // full URL
            !filter_var('http://'.$input, FILTER_VALIDATE_URL) && // host name only
            !strpos($input, '/') == 0)
        {
                $isValid = false;
        }

        return $isValid;
    }
}
