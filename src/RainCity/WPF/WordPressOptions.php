<?php
namespace RainCity\WPF;

use RainCity\Singleton;

/**
 * This class manages the options for the plugin
 *
 * @since      1.0.0
 * @package    WordPressOptions
 */
abstract class WordPressOptions extends Singleton
{
    /** @var string */
    private string $optionName;

    /** @var string[] */
    private array $validKeys = array();

    /** @var array<string, string> */
    private array $values = array();


    /**
     * Initialize the collections used to maintain the values.
     *
     * @param string $name
     * @param array<string> $validKeys
     */
    protected function initializeOptions(string $name, array $validKeys): void
    {
        $this->optionName = $name;
        $this->validKeys = $validKeys;
        $dbValue = get_option($name);

        if (!isset($dbValue) || !is_array($dbValue)) {
            $this->values = array();
            foreach ($this->validKeys as $key) {
                $this->values[$key] = '';
            }
            add_option($name, $this->values);
        } else {
            $this->values = $dbValue;
        }

        foreach ($this->validKeys as $key) {
            if (!isset($this->values[$key])) {
                $this->values[$key] = '';
            }
        }

        if (!empty($validKeys)) {
            $oldKeys = array_diff(array_keys($this->values), $validKeys);
            foreach ($oldKeys as $key) {
                unset($this->values[$key]);
            }
        }

        update_option($name, $this->values);
    }

    public function getOptionName(): string
    {
        return $this->optionName;
    }

    /**
     *
     * @return array<string, string>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function setValue(string $key, string $value): void
    {
        if ($this->isValidKey($key)) {
            $this->values[$key] = $value;
        }
    }

    public function getValue(string $key): ?string
    {
        return $this->isValidKey($key) ? $this->values[$key] : null;
    }

    /**
     * Returns an array of values for use in HTML forms.
     *
     * [0] => The key passed in for use as a field id
     * [1] => '{optionName}[{key}]' for use as a field name
     * [2] => The field value
     *
     * @param string $key The option to retrieve the information for.$this
     *
     * @return array<string>|NULL The array of values or null if the key is invalid.
     */
    public function getFormFieldInfo(string $key): ?array
    {
        $result = null;

        if ($this->isValidKey($key)) {
            $result = array(
                $key,
                sprintf("%s[%s]", $this->optionName, $key),
                $this->values[$key] ?? ''
            );
        }

        return $result;
    }

    public function save(): void
    {
        update_option($this->optionName, $this->values);
    }

    private function isValidKey(string $key): bool
    {
        return in_array($key, $this->validKeys);
    }
}
