<?php
namespace RainCity\WPF\Settings;

use RainCity\Logging\Logger;
use RainCity\WPF\WordPressOptions;
use RainCity\WPF\Validation\EmailValidator;
use RainCity\WPF\Validation\StringValidator;
use Psr\Log\LoggerInterface;

abstract class AdminSettingsTab
{
    protected LoggerInterface $log;
    protected string $tabName;
    protected string $tabId;
    protected WordPressOptions $options;

    protected string $pageSlug;

    public function __construct(string $tabName, WordPressOptions $options)
    {
        $this->tabName = $tabName;
        $this->tabId = $this->generateTabId();
        $this->options = $options;

        $this->log = Logger::getLogger(get_class($this));
    }

    /**
     * Retreive the name of the tab.
     *
     * @return  string  The name of the tab.
     */
    public function getName(): string
    {
        return $this->tabName;
    }

    /**
     * Retreive the identifier for the tab.
     *
     * @return  string  The id for the tab.
     */
    public function getId(): string
    {
        return $this->tabId;
    }

    /**
     * Add the sections and fields to be displayed on the tab.
     *
     * Generally an implementation will be a series of calls to
     * add_settings_field() and optionally divided into sections with calls
     * to add_settings_section().
     * <p>
     * An implementation may also make calls to add_action() and add_filter()
     * for events that are specific to the tab, such as an Ajax handler for
     * something on the tab.
     *
     * @param   string  $pageSlug The name of the page slug.
     */
    abstract public function addSettings(string $pageSlug): void;

    final public function initSettings(string $pageSlug): void
    {
        $this->pageSlug = $pageSlug;

        register_setting(
            $pageSlug,
            $this->getOptionName(),
            [
                'sanitize_callback' => function (?array $input): ?array {
                    return $this->sanitize($this->pageSlug, $input);
                }
            ]
        );

        $this->addSettings($pageSlug);
    }

    /**
     * Sanitize data entered on the tab.
     *
     * @param   string  $pageSlug   The page slug, used for validation classes.
     * @param   array<string, mixed> $input      An array of value entered on the tab.
     *
     * @return  NULL|array<string, mixed> Returns the, potentially modified, input data.
     */
    abstract public function sanitize(string $pageSlug, ?array $input): ?array;

    /**
     * Register any scripts or styles needed by the tab.
     *
     * Admin tabs should override this function to register scripts or styles
     * so they are only loaded when the tab is active.
     *
     * @param   string  $pluginName     The name of the plugin.
     * @param   string  $pluginBaseUrl  The base URL of the plugin.
     * @param   string  $pluginVersion  The version of the plugin.
     */
    public function onEnqueueScripts(string $pluginName, string $pluginBaseUrl, string $pluginVersion): void
    {
    }

    /**
     * Opportunity for tabs to register any additional actions such as Ajax
     * handlers.
     */
    public function registerActions():void
    {
    }

    /**
     * Retreive the name of the option used to store values from the tab.
     *
     * @return  string  The name of the option.
     */
    public function getOptionName(): string
    {
        return $this->options->getOptionName();
    }


    /**
     * Generate an id for the tab.
     *
     * It doesn't have to be secure, just consistently unique.
     * <p>
     * This identifier assumes that there will/can only be one instance of
     * the tab class.
     *
     * @return string A string to use as the identifier.
     */
    private function generateTabId(): string
    {
        return hash_hmac(
            'md5',
            get_class($this),   // actual class name as data
            self::class         // our class name as key
            );
    }


    /**
     *  Renders any desired output after the Setting form has been rendered.
     *
     *  Useful for displaying additional information or providing ancillary
     *  functions such as buttons for invoking Ajax calls.
     */
    public function renderPostFormData(): void
    {
    }


    /**
     * Render a Text field
     *
     * Defaults for attributes that can be overwritten:<br>
     *   size: 64<br>
     *   maxlength: 255
     *
     * @param WordPressOptions  $optionsObj Options object containing the field.
     * @param string            $optionName Name of the option field being rendered.
     * @param string            $description Optional: A description for the field.
     * @param array<string, mixed> $attrs Optional: Additional attributes to be added to the input element.
     */
    protected function renderTextField(
        WordPressOptions $optionsObj,
        string $optionName,
        string $description = '',
        array $attrs = array()
        ): void
    {
        $fieldInfo = $optionsObj->getFormFieldInfo($optionName);
        $attrs = array_merge (
            // these attributes can be overwritten
            array(
                'size'  => 64,
                'maxlength' => 255
            ),
            $attrs,
            // these attributes cannot be overwritten
            array(
                'type'  => 'text',
                'class' => 'regular-text',
                'id'    => $fieldInfo[0],
                'name'  => $fieldInfo[1],
                'value' => (3 == count($fieldInfo)) ? esc_attr( $fieldInfo[2] ) : ''
            )
            );

        $this->renderInputField($attrs);
        $this->renderDescription($optionName, $description);
    }


    /**
     * Render a Password field
     *
     * Defaults for attributes that can be overwritten:<br>
     *   size: 64<br>
     *   maxlength: 255
     *
     * @param WordPressOptions  $optionsObj Options object containing the field.
     * @param string            $optionName Name of the option field being rendered.
     * @param string            $description Optional: A description for the field.
     * @param array<string, mixed> $attrs Optional: Additional attributes to be added to the input element.
     */
    protected function renderPasswordField(
        WordPressOptions $optionsObj,
        string $optionName,
        string $description = '',
        array $attrs = array()
        ): void
    {
        $fieldInfo = $optionsObj->getFormFieldInfo($optionName);
        $attrs = array_merge (
            // these attributes can be overwritten
            array(
                'size'  => 64,
                'maxlength' => 255
            ),
            $attrs,
            // these attributes cannot be overwritten
            array(
                'type'  => 'password',
                'class' => 'regular-text',
                'id'    => $fieldInfo[0],
                'name'  => $fieldInfo[1],
                'value' => (3 == count($fieldInfo)) ? esc_attr( $fieldInfo[2] ) : ''
            )
            );

        $this->renderInputField($attrs);
        $this->renderDescription($optionName, $description);
    }


    /**
     * Render a Checkbox field
     *
     * @param WordPressOptions  $optionsObj Options object containing the field.
     * @param string            $optionName Name of the option field being rendered.
     * @param string            $description Optional: A description for the field.
     * @param array<string, mixed> $attrs Optional: Additional attributes to be added to the input element.
     */
    protected function renderCheckboxField(
        WordPressOptions $optionsObj,
        string $optionName,
        string $description = '',
        array $attrs = array()
        ): void
    {
        $fieldInfo = $optionsObj->getFormFieldInfo($optionName);

        if (!is_null($fieldInfo)) {
            $attrs = array_merge (
                // these attributes can be overwritten
                array(
                ),
                $attrs,
                // these attributes cannot be overwritten
                array(
                    'type'  => 'checkbox',
                    'class' => 'regular-text',
                    'id'    => $fieldInfo[0],
                    'name'  => $fieldInfo[1],
                    'value' => true
                )
                );

            if (!empty($fieldInfo[2]) && filter_var($fieldInfo[2], FILTER_VALIDATE_BOOLEAN) ) {
                $attrs['checked'] = null;
            }

            // Include a hidden field with the same name and a "false" value so
            // something is always posted back to the server.
            $this->renderInputField(
                array (
                    'type'  => 'hidden',
                    'value' => false,
                    'name'  => $fieldInfo[1]
                )
                );

            $this->renderInputField($attrs);
        }

        $this->renderDescription($optionName, $description);
    }


    /**
     * Render a matrix/set of Checkbox fields
     *
     * @param WordPressOptions $optionsObj Options object containing the field.
     * @param string           $optionName Name of the option field being rendered.
     * @param string           $description Optional: A description for the field.
     * @param array<CheckboxMatrixEntry> $matrixEntries The options to display in the matrix. @see CheckboxMatrixEntry
     * @param array<string, mixed>  $attrs Optional: Additional attributes to be added to the input element.
     * @param bool             $showSelectAll Optional: Whether to include an option to select/clear all of the options.
     */
    protected function renderCheckboxMatrixField(
        WordPressOptions $optionsObj,
        string $optionName,
        string $description = '',
        array $matrixEntries = array(),
        array $attrs = array(),
        bool $showSelectAll = false): void
    {
        $fieldInfo = $optionsObj->getFormFieldInfo($optionName);
        $attrs = array_merge (
            // these attributes can be overwritten
            array(
            ),
            $attrs,
            // these attributes cannot be overwritten
            array(
                'type'  => 'checkbox',
                'class' => 'checkall_' . $optionName
            )
            );

        $this->renderDescription($optionName, $description);

        $half = ( count( $matrixEntries ) / 2 );
        $ndx    = 0;
        $bColBrkDone = false;

        print
            '<table style="width: 100%; border-collapse: separate; border-spacing: 2px; *border-collapse: ' .
            'expression(\'separate\', cellSpacing = \'2px\');" class="editform">' . "\r\n";
        print '<tr style="vertical-align: top;"><td style="width: 50%; text-align: left;">' . "\r\n";

        if ($showSelectAll) {
            print
                '<label><input type="checkbox" name="checkall" value="checkall_' . $optionName .
                '" />Select/Clear All</label><br />' . "\r\n";
        }

        foreach ( $matrixEntries as $entry ) {
            if ( $ndx >= $half && !$bColBrkDone ) {
                print '</td><td style="width: 50%; text-align: left;">' . "\r\n";
                if ($showSelectAll) {
                    print '<br />';
                }
                $bColBrkDone = true;
            }

            if ($entry->isSelected) {
                $attrs['checked'] = null;
            }
            else {
                unset($attrs['checked']);
            }
            $attrs['id']    = $fieldInfo[0].'['.$entry->value.']';
            $attrs['name']  = $fieldInfo[1].'['.$entry->value.']';
            $attrs['value'] = $entry->value;

            print '<label>';
            $this->renderInputField($attrs);
            print $entry->name . '</label><br />' . "\r\n";

            $ndx++;
        }
        print '</td></tr>' . "\r\n";
        print '</table>' . "\r\n";

    }


    /**
     * Render a Number field
     *
     * Defaults for attributes that can be overwritten:<br>
     *   size: 10<br>
     *   maxlength: 10
     *
     * @param WordPressOptions  $optionsObj Options object containing the field.
     * @param string            $optionName Name of the option field being rendered.
     * @param string            $description Optional: A description for the field.
     * @param array<string, mixed>  $attrs Optional: Additional attributes to be added to the input element.
     */
    protected function renderNumberField(
        WordPressOptions $optionsObj,
        string $optionName,
        string $description = '',
        array $attrs = array()
        ): void
    {
        $fieldInfo = $optionsObj->getFormFieldInfo($optionName);
        $attrs = array_merge (
            // these attributes can be overwritten
            array(
                'size'      => 10,
                'maxlength' => 10
            ),
            $attrs,
            // these attributes cannot be overwritten
            array(
                'type'      => 'number',
                'class'     => 'small-text',
                'id'        => $fieldInfo[0],
                'name'      => $fieldInfo[1],
                'value'     => (3 == count($fieldInfo)) ? esc_attr( $fieldInfo[2] ) : '',
                'required'  => null
            )
            );

        $this->renderInputField($attrs);
        $this->renderDescription($optionName, $description);
    }


    /**
     * Render an RTE text field.
     *
     * @param WordPressOptions  $optionsObj Options object containing the field.
     * @param string            $optionName Name of the option field being rendered.
     * @param string            $description Optional: A description for the field.
     * @param int               $rows Number of rows for the text area.
     */
    protected function renderRteTextField(
        WordPressOptions $optionsObj,
        string $optionName,
        string $description = '',
        int $rows = 10
        ): void
    {
        $fieldInfo = $optionsObj->getFormFieldInfo($optionName);

        wp_editor(
            $fieldInfo[2],
            $fieldInfo[0],
            array(
                'textarea_name' => $fieldInfo[1],
                'media_buttons' => false,
                'textarea_rows' => $rows
            )
            );

        $this->renderDescription($optionName, $description);
    }

    /**
     * Render an Email type field
     *
     * Defaults for attributes that can be overwritten:<br>
     *   size: 40 (80 if $allowMultiple is true)<br>
     *   maxlength: 64 (100 if $allowMultiple is true)
     *
     * @param WordPressOptions  $optionsObj Options object containing the field.
     * @param string            $optionName Name of the option field being rendered.
     * @param string            $description Optional: A description for the field.
     * @param bool              $allowMultiple Optional: Whether to allow multiple addresses. Defaults to false.
     * @param array<string, mixed>  $attrs Optional: Additional attributes to be added to the input element.
     */
    protected function renderEmailField(
        WordPressOptions $optionsObj,
        string $optionName,
        string $description = '',
        bool $allowMultiple = false,
        array $attrs = array()
        ): void
    {
        $fieldInfo = $optionsObj->getFormFieldInfo($optionName);
        $attrs = array_merge (
            // these attributes can be overwritten
            array(
                'size'  => $allowMultiple ? 80 : 40,
                'maxlength' => $allowMultiple ? 100 : 64
            ),
            $attrs,
            // these attributes cannot be overwritten
            array(
                'type'  => 'email',
                'id'    => $fieldInfo[0],
                'name'  => $fieldInfo[1],
                'value' => (3 == count($fieldInfo)) ? esc_attr($fieldInfo[2]) : ''
            )
            );

        if ($allowMultiple) {
            $attrs['multiple'] = true;
        }

        $this->renderInputField($attrs);
        $this->renderDescription($optionName, $description);
    }



    /**
     * Render a URL type field.
     *
     * Defaults for attributes that can be overwritten:<br>
     *   size: 90<br>
     *   maxlength: 128
     *
     * @param WordPressOptions  $optionsObj  Options object containing the field.
     * @param string            $optionName Name of the option field being rendered.
     * @param string            $description Optional: A description for the field.
     * @param array<string, mixed>  $attrs Optional: Additional attributes to be added to the input element.
     */
    protected function renderUrlField(
        WordPressOptions $optionsObj,
        string $optionName,
        string $description = '',
        array $attrs = array()
        ): void
    {
        $fieldInfo = $optionsObj->getFormFieldInfo($optionName);
        $attrs = array_merge (
            // these attributes can be overwritten
            array(
                'size'  => 90,
                'maxlength' => 128
            ),
            $attrs,
            // these attributes cannot be overwritten
            array(
                'type'  => 'text',
                'class' => 'regular-text',
                'id'    => $fieldInfo[0],
                'name'  => $fieldInfo[1],
                'value' => (3 == count($fieldInfo)) ? esc_attr($fieldInfo[2]) : ''
            )
            );

        $this->renderInputField($attrs);
        $this->renderDescription($optionName, $description);
    }


    /**
     * Renders an &lt;Input&gt; field using the attributes provided.
     *
     * @param array<string, mixed> $attrs An array of attributes to use.
     */
    private function renderInputField(array $attrs): void
    {
        print '<input ';
        array_walk($attrs, function($value, $key) {
            print $key;
            if (isset($value)) {
                print '="' . $value . '"';
            }

            print ' ';
        });
            print '/>';
    }


    /**
     * Renders the description for a field if one is provided.
     *
     * @param string $optionName Name of the option field.
     * @param string $description Description for the field.
     */
    private function renderDescription(string $optionName, string $description): void
    {
        if (!empty($description)) {
            printf('<p class="description" id="%s-description">%s</p>', $optionName, $description);
        }
    }


    protected function validateStringValue(string $key, string $value, string $pageSlug, string $errMsg = 'Missing value'): void
    {
        $validator = new StringValidator($pageSlug, $key, self::$fieldNameMap[$key]);

        if ($validator->isValid($value)) {
            $this->options->setValue($key, trim($value));
        }
        else {
            $validator->addError($errMsg);
        }
    }

    protected function validateEmailAddress(string $key, string $value, string $pageSlug, string $errMsg = 'Invalid Email address'): void
    {
        $validator = new EmailValidator($pageSlug, $key, self::$fieldNameMap[$key]);

        if ($validator->isValid($value)) {
            $this->options->setValue($key, trim($value));
        }
        else {
            $validator->addError($errMsg);
        }
    }
}

class CheckboxMatrixEntry
{
    public string $name;
    public string $value;
    public bool $isSelected;
}
