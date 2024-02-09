<?php
namespace RainCity\WPF;


use RainCity\SerializeAsArrayTrait;

/**
 *
 *
 */
abstract class WordPressUserData
{
    use SerializeAsArrayTrait;

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
}
