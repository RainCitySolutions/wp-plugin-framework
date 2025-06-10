<?php
namespace RainCity\WPF;

/**
 * This class provides methods for accessing the Formidable Forms tables
 *
 * @since    1.0.0
 * @package    formidable
 * @deprecated Use \RainCity\WPF\Formidable\Formidable
 */
class Formidable
{
    /**
     * Returns the ID for a Formidable form given its key.
     *
     * @param string $key The key of a Formidable form.
     *
     * @return int|NULL The ID of the form or null if no form was found with
     *         the specified key.
     */
    public static function getFormId(string $key): ?int
    {
        return \RainCity\WPF\Formidable\Formidable::getFormId($key);
    }


    /**
     * Returns the ID for a Formidable field given its key.
     *
     * @param string $key The key of a Formidable field.
     *
     * @return int|NULL The ID of the field or null if no field was found
     *         with the specified key.
     */
    public static function getFieldId(string $key): ?int
    {
        return \RainCity\WPF\Formidable\Formidable::getFieldId($key);
    }

    /**
     * Returns the ID for a Formidable view give its key.
     *
     * @param string $key The key of a Formidable view.
     *
     * @return int|NULL The ID of the view or null if no view was found with
     *         the specified key.
     */
    public static function getViewId(string $key): ?int
    {
        return \RainCity\WPF\Formidable\Formidable::getViewId($key);
    }

    /**
     * Disable Formidable from caching results from database queries.
     *
     * Calls to disableDbCache() should be paired with calls to
     * restoreDbCache().
     */
    public static function disableDbCache(): void
    {
        \RainCity\WPF\Formidable\Formidable::disableDbCache();
    }


    /**
     * Restore Formidable caching state to the previous value.
     *
     * Calls to restoreDbCache() should be paired with calls to
     * disableDbCache().
     */
    public static function restoreDbCache(): void
    {
        \RainCity\WPF\Formidable\Formidable::restoreDbCache();
    }

    /**
     * Retrieve the label for the specified option value on a field.
     *
     * @param int $fieldId A field identier
     * @param string|int $optionValue The value for a field option
     *
     * @return string The label associated with the field value, or an empty
     *      string if the field doesn't exist, is not an options field, or
     *      the provided value is not valid for the field.
     */
    public static function getFieldOptionLabel(int $fieldId, string|int $optionValue): string
    {
        return \RainCity\WPF\Formidable\Formidable::getFieldOptionLabel($fieldId, $optionValue);
    }

    /**
     * Retrieve the value for the specified option label on a field.
     *
     * @param int $fieldId A field identier
     * @param string $optionLabel The label for a field option
     *
     * @return string|int The value associated with the field label, or an empty
     *      string if the field doesn't exist, is not an options field, or
     *      the provided label is not valid for the field.
     */
    public static function getFieldOptionValue(int $fieldId, string $optionLabel): string|int
    {
        return \RainCity\WPF\Formidable\Formidable::getFieldOptionValue($fieldId, $optionLabel);
    }
}
