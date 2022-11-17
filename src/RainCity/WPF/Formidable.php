<?php
namespace RainCity\WPF;


if (class_exists('FrmForm')) {

/**
 * This class provides methods for accessing the Formidable Forms tables
 *
 * @since	1.0.0
 * @package	formidable
 */
class Formidable
{
	private static $formIdCache = array();
	private static $fieldIdCache = array();
	private static $viewIdCache = array();

	/**
	 * Returns the ID for a Formidable form given its key.
	 *
	 * @param string $key The key of a Formidable form.
	 *
	 * @return int|NULL The ID of the form or null if no form was found with
	 *         the specified key.
	 */
	public static function getFormId(string $key): ?int {
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
	public static function getFieldId(string $key): ?int {
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
	public static function getViewId(string $key): ?int {
	    if (class_exists('\FrmViewsDisplay')) {
	        $classname = '\FrmViewsDisplay';       // new Formidable View plugin (Dec 2020-)
	    } elseif (class_exists('\FrmProDisplay')) {
	        $classname = '\FrmProDisplay';         // Pre Formidable View plugin (-Dec 2020)
	    }

	    return self::getId($classname, $key, self::$viewIdCache);
	}


	/**
	 * Returns the ID for a Formidable class entry given its key.
	 *
	 * @param string $classname The name of the class to use in looking
	 *             the key.
	 * @param string $key The key of a Formidable entry.
	 * @param array $cache A reference to an array to use for cacing the id.
	 *
	 * @return int|NULL The ID of the entry or null if no entry was found
	 *         with the specified key.
	 */
	private static function getId(string $classname, string $key, array &$cache): ?int {
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
}

}   // if class_exists()
