<?php
namespace RainCity\WPF;

/**
 * This class provides methods for accessing the Formidable Forms tables
 *
 * @since    1.0.0
 * @package    formidable
 */
class Formidable
{
    private const FRM_CACHE_FLAG = 'prevent_caching';

    /** @var array<string, int> */
    private static array $formIdCache = [];
    /** @var array<string, int> */
    private static array $fieldIdCache = [];
    /** @var array<string, int> */
    private static array $viewIdCache = [];
    /** @var int[] */
    private static array $dbCacheState = [];

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
        return self::getId('\FrmForm', $key, self::$formIdCache);
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
        return self::getId('\FrmField', $key, self::$fieldIdCache);
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
        if (class_exists('\FrmViewsDisplay')) {
            $classname = '\FrmViewsDisplay';       // new Formidable View plugin (Dec 2020-)
        } elseif (class_exists('\FrmProDisplay')) {
            $classname = '\FrmProDisplay';         // Pre Formidable View plugin (-Dec 2020)
        }

        if (isset($classname)) {
            $id = self::getId($classname, $key, self::$viewIdCache);
        } else {
            $id = null;
        }

        return $id;
    }


    /**
     * Returns the ID for a Formidable class entry given its key.
     *
     * @param string $classname The name of the class to use in looking
     *             the key.
     * @param string $key The key of a Formidable entry.
     * @param array<string, int> $cache A reference to an array to use for cacing the id.
     *
     * @return int|NULL The ID of the entry or null if no entry was found
     *         with the specified key.
     */
    private static function getId(string $classname, string $key, array &$cache): ?int
    {
        $id = null;

        // If the class we need is available, continue
        if (class_exists($classname)) {
            if (isset($cache[$key])) {
                $id = $cache[$key];
            }
            else {
                $dbId = $classname::get_id_by_key($key);
                if (0 !== $dbId) {
                    $cache[$key] = $dbId;
                    $id = $dbId;
                }
            }
        }

        return $id;
    }

    /**
     * Disable Formidable from caching results from database queries.
     *
     * Calls to disableDbCache() should be paired with calls to
     * restoreDbCache().
     */
    public static function disableDbCache(): void
    {
        global $frm_vars;

        if (isset($frm_vars[self::FRM_CACHE_FLAG])) {
            array_push(self::$dbCacheState, $frm_vars[self::FRM_CACHE_FLAG]);
        }
        else {
            // In the event that the flag gets removed elsewhere (e.g. by
            // Formidable) ensure that our state is starting from scratch.
            self::$dbCacheState = array(-1);
        }

        $frm_vars[self::FRM_CACHE_FLAG] = true;
    }


    /**
     * Restore Formidable caching state to the previous value.
     *
     * Calls to restoreDbCache() should be paired with calls to
     * disableDbCache().
     */
    public static function restoreDbCache(): void
    {
        global $frm_vars;

        if (!empty(self::$dbCacheState)) {
            $prevValue = array_pop(self::$dbCacheState);

            if (-1 === $prevValue) {
                unset($frm_vars[self::FRM_CACHE_FLAG]);
            }
            else {
                $frm_vars[self::FRM_CACHE_FLAG] = $prevValue;
            }
        }
    }

    /**
     * Retrieve the label for the specified option value on a field.
     *
     * @param int $fieldId A field identier
     * @param string $optionValue The value for a field option
     *
     * @return string The label associated with the field value, or an empty
     *      string if the field doesn't exist, is not an options field, or
     *      the provided value is not valid for the field.
     */
    public static function getFieldOptionLabel(int $fieldId, string $optionValue): string
    {
        $result = '';

        $field = \FrmField::getOne($fieldId);

        if (isset($field) && isset($field->options)) {
            foreach ($field->options as $option) {
                if ($option['value'] == $optionValue) {
                    $result = $option['label'];
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Retrieve the value for the specified option label on a field.
     *
     * @param int $fieldId A field identier
     * @param string $optionLabel The label for a field option
     *
     * @return string The value associated with the field label, or an empty
     *      string if the field doesn't exist, is not an options field, or
     *      the provided label is not valid for the field.
     */
    public static function getFieldOptionValue(int $fieldId, string $optionLabel): string
    {
        $result = '';

        $field = \FrmField::getOne($fieldId);

        if (isset($field) && isset($field->options)) {
            foreach ($field->options as $option) {
                if ($option['label'] == $optionLabel) {
                    $result = $option['value'];
                    break;
                }
            }
        }

        return $result;
    }
}
