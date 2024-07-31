<?php
namespace RainCity\WPF;

use RainCity\SerializeAsArrayTrait;

abstract class WordPressUserData
{
    use SerializeAsArrayTrait;

    /** Key for data stored per user in the WordPress usermeta table */
    const USER_META_KEY = self::USER_META_KEY;

    /** @var int WordPress user id */
    protected int $wpUserId;

    /** @var array<string, string> Data storage array */
    private array $data = [];

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

    /**
     *
     * @param string $key
     * @param string|array<mixed> $value
     */
    public function setData(string $key, string|array $value): void
    {
        $this->data[$key] = serialize($value);
        $this->save();
    }

    /**
     *
     * @param string $key
     *
     * @return null|string|array<mixed>
     */
    public function getData(string $key): null|string|array
    {
        $result = null;

        if (isset($this->data[$key])) {
            $result = unserialize($this->data[$key]);
        }

        return $result;
    }

    /**
     * Saves the current object state to the user's meta data.
     */
    private function save(): void
    {
        update_user_meta($this->wpUserId, static::USER_META_KEY, $this);
    }
}
