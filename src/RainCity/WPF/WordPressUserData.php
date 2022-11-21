<?php
namespace RainCity\WPF;

use Serializable;

/**
 *
 *
 */
abstract class WordPressUserData
    implements Serializable
{
    /** @var string Key for data stored per user in the WordPress usermeta table */
    const USER_META_KEY = self::USER_META_KEY;

    /** @var int WordPress user id */
    protected $wpUserId;

    /* @var array data storage array */
    private $data = array();

    /**
     * Construct an instance by fetching the object from the user's meta data.
     *
     * @param int $userId Id of the WordPress user
     */
    public function __construct(int $userId)
    {
        $this->wpUserId = $userId;

        $metaObj = unserialize(get_user_meta($this->wpUserId, static::USER_META_KEY, true));
        if (is_object($metaObj)) {
            $this->data = $metaObj->data;
        }
    }

    public function setData(string $key, $value) {
        $this->data[$key] = serialize($value);
        $this->save();
    }

    public function getData(string $key) {
        $result = null;

        if (isset($this->data[$key])) {
            $result = unserialize($this->data[$key]);
        }

        return $result;
    }

    /**
     * Saves the current object state to the user's meta data.
     */
    private function save() {
        update_user_meta($this->wpUserId, static::USER_META_KEY, $this);
    }

    /**
     * {@inheritDoc}
     *
     * @see Serializable::serialize()
     */
    public function serialize()
    {
        $vars = get_object_vars($this);

        return serialize($vars);
    }

    /**
     * {@inheritDoc}
     *
     * @see Serializable::unserialize()
     */
    public function unserialize($data)
    {
        $vars = unserialize($data);

        foreach ($vars as $var => $value) {
            /**
             * Only set values for properties of the object.
             *
             * Generally this will be the case but this accounts for the
             * possiblity that a field may be removed from the class in the
             * future.
             */
            if(property_exists(__CLASS__,$var))
            {
                $this->$var = $value;
            }
        }
    }

}
