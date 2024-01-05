<?php
namespace RainCity\WPF\PopupMaker;

use RainCity\WPF\ActionFilterLoader;
use RainCity\WPF\ActionHandlerInf;

/**
 * Helper class implemeting a date range condition for PopupMaker.
 */
final class DateRangeCondition implements ActionHandlerInf
{
    private const POPPER_HANDLE = 'pum-popper';
    private const POPPER_VERSION = '2.11.6';

    private const TEMPUS_DOMINUS_HANDLE = 'pum-tempus-dominus';
    private const TEMPUS_DOMINUS_VERSION = '6.9.4';

    private const FONTAWESOME_HANDLE = 'font-awesome-official-css';
    private const FONTAWESOME_VERSION = '5.15.4';

    /**
     * Add hooks for the PopupMaker condition.
     *
     * @param ActionFilterLoader $loader
     */
    public function loadActions(ActionFilterLoader $loader) {
        if (in_array( 'popup-maker/popup-maker.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            $loader->add_action('admin_enqueue_scripts', $this, 'adminEnqueueScripts');
            $loader->add_action('admin_print_footer_scripts', $this, 'adminPrintFooterScripts', -1);
            $loader->add_filter('wp_script_attributes', $this, 'wpScriptAttributes', 10, 1 );
            $loader->add_filter('style_loader_tag', $this, 'styleLoaderTag', 10, 2);
            
            $loader->add_filter('pum_registered_conditions', $this, 'registerConditions');
        }
    }
    
    /**
     * Callback for the 'pum_registered_conditions' filter.
     *
     * Adds our custom conditions to the list of possible conditions.
     *
     * @param array $conditions An array of conditions.
     *
     * @return array The modified conditions array.
     */
    public function registerConditions(array $conditions = array()): array {
        $conditions = array_merge(
            $conditions,
            [
                'raincity_date_range' => [
                    'group'    => 'General',
                    'name'     => 'Date Range',
                    'fields'   => [
                        'selected' => [
                            'type'      => 'raincity_datetimepicker',
                            'priority'  => 1,
                        ],
                    ],
                    'callback' => array ($this,'isInDateRange')
                ]
            ]
            );
        
        return $conditions;
    }
    
    public function isInDateRange(array $condition): bool {
        $isInRange = false;
        
        $now = new \DateTime();
        
        $startDate = \DateTime::createFromFormat(
            'm/d/Y g:i A',
            $condition['selected']['start'],
            new \DateTimeZone($condition['selected']['tz'])
            );
        $endDate = \DateTime::createFromFormat(
            'm/d/Y g:i A',
            $condition['selected']['end'],
            new \DateTimeZone($condition['selected']['tz'])
            );
        
        // if startDate is before now invert will be set to 0, otherwise it will be 1
        $startInterval = $startDate->diff($now);
        // if now is before endDate invert will be set to 0, otherwise it will be 1
        $endInterval = $now->diff($endDate);
        
        if ($startInterval->invert == 0 && $endInterval->invert == 0) {
            $isInRange = true;
        }
        
        return $isInRange;
    }

    public function adminPrintFooterScripts()
    {
        if ((wp_script_is('pum-admin-general') || wp_script_is('popup-maker-admin')) &&
            (did_action('admin_footer') || doing_action('admin_footer')) )
        {
            ?>
            <script type="text/html" id="tmpl-pum-field-raincity_datetimepicker">
    			<#
                    let nowStr = new Date()
                            .toLocaleString(
                                'en-US',
                                {month: '2-digit', day: '2-digit', year: 'numeric', hour: 'numeric', minute: 'numeric'}
                                )
                            .replace(',','');

                    data.value = _.extend({
                        tz: Intl.DateTimeFormat().resolvedOptions().timeZone,
                        start: nowStr,
                        end: nowStr,
                    }, data.value);
                #>

                <div id="{{data.id}}-container">
                    <div
                        class="input-group"
                        id="{{data.id}}-start_wrapper"
                        data-td-target-input="nearest"
                        data-td-target-toggle="nearest"
                    >
                        <label for="{{data.id}}_start">Start Date/Time:</label>
                        <input
                            id="{{data.id}}_start"
                            type="text"
                            class="form-control {{data.size}}-text"
                            name="{{data.name}}[start]"
                            data-td-target="#{{data.id}}-start_wrapper"
                            value="{{data.value.start}}"
                            {{{data.meta}}}
                            readonly
                            />
                        <span
                            class="input-group-text"
                            title="Select Date/Time"
                            data-td-target="#{{data.id}}-start_wrapper"
                            data-td-toggle="datetimepicker"
                            >
                            <i class="fas fa-calendar-alt"></i>
                        </span>
                    </div>
                    <div
                        class="input-group"
                        id="{{data.id}}-end_wrapper"
                        data-td-target-input="nearest"
                        data-td-target-toggle="nearest"
                    >
                        <label for="{{data.id}}_end">End Date/Time:</label>
                        <input
                            id="{{data.id}}_end"
                            type="text"
                            class="form-control {{data.size}}-text"
                            name="{{data.name}}[end]"
                            data-td-target="#{{data.id}}-end_wrapper"
                            value="{{data.value.end}}"
                            {{{data.meta}}}
                            readonly
                            />
                        <span
                            class="input-group-text"
                            title="Select Date/Time"
                            data-td-target="#{{data.id}}-end_wrapper"
                            data-td-toggle="datetimepicker"
                            >
                            <i class="fas fa-calendar-alt"></i>
                        </span>
                    </div>
                </div>
                <input
                    id="{{data.id}}_tz"
                    type="hidden"
                    name="{{data.name}}[tz]"
                    value="{{data.value.tz}}"
                    />

                <#
                    if (data.id !== '') {
                        // Use a Promise to delay accessing the pickers which won't be complete yet.
                        new Promise(resolve => setTimeout(resolve, 1000)).then(() => {
                            let startWrapper = document.getElementById(data.id + "-start_wrapper");
                            let endWrapper = document.getElementById(data.id + "-end_wrapper")

                            let pickerOptions = {
                                container: document.getElementById(data.id + "-container"),
                                display: {
                                    icons: {
                                        type: 'icons',
                                        time: 'fas fa-clock',
                                        date: 'fas fa-calendar-alt',
                                        up: 'fas fa-arrow-up',
                                        down: 'fas fa-arrow-down',
                                        previous: 'fas fa-chevron-left',
                                        next: 'fas fa-chevron-right',
                                        today: 'fas fa-calendar-check',
                                        clear: 'fas fa-trash',
                                        close: 'fas fa-xmark'
                                        },
                                    buttons: {
                                        today: true,
                                        clear: true
                                        }
                                    }
                                };

                            if (startWrapper !== null && endWrapper !== null) {
                                let startPicker = new tempusDominus.TempusDominus(startWrapper, pickerOptions);
                                let endPicker = new tempusDominus.TempusDominus(endWrapper, pickerOptions);

                                // Set initial limits
                                startPicker.updateOptions({
                                    restrictions: {
                                        maxDate: endPicker.dates.lastPicked,
                                    },
                                });
                                endPicker.updateOptions({
                                    restrictions: {
                                        minDate: startPicker.dates.lastPicked,
                                    },
                                });

                                // Update the limits as the selection changes
                                startPicker.subscribe(tempusDominus.Namespace.events.change, (e) => {
                                    endPicker.updateOptions({
                                        restrictions: {
                                            minDate: e.date,
                                        },
                                    });
                                });
                                endPicker.subscribe(tempusDominus.Namespace.events.change, (e) => {
                                    startPicker.updateOptions({
                                        restrictions: {
                                            maxDate: e.date,
                                        },
                                    });
                                });
                            }
                        });
                    }
                #>
            </script>
			<?php
        }

    }

    public function adminEnqueueScripts()
    {
        wp_enqueue_script(
            self::POPPER_HANDLE,
            'https://cdn.jsdelivr.net/npm/@popperjs/core@'.self::POPPER_VERSION.'/dist/umd/popper.min.js',
            [],
            self::POPPER_VERSION,
            true
            );

        wp_enqueue_script(
            self::TEMPUS_DOMINUS_HANDLE,
            'https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@'.self::TEMPUS_DOMINUS_VERSION.'/dist/js/tempus-dominus.min.js',
            ['jquery', self::POPPER_HANDLE],
            self::TEMPUS_DOMINUS_VERSION,
            true
            );

        wp_enqueue_style(
            'font-awesome-official-css',
            'https://use.fontawesome.com/releases/v'.self::FONTAWESOME_VERSION.'/css/all.css',
            [],
            self::FONTAWESOME_VERSION
            );

        wp_enqueue_style(
            self::TEMPUS_DOMINUS_HANDLE,
            'https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@'.self::TEMPUS_DOMINUS_VERSION.'/dist/css/tempus-dominus.min.css',
            [], //[self::BOOTSTRAP_HANDLE],
            self::TEMPUS_DOMINUS_VERSION
            );
    }

    public function wpScriptAttributes(array $attr)
    {
        if (self::POPPER_HANDLE.'-js' == $attr['id'] ||
            self::TEMPUS_DOMINUS_HANDLE.'-js' == $attr['id'])
        {
            $attr['crossorigin'] = 'anonymous';
        }

        return $attr;
    }

    public function styleLoaderTag($html, $handle)
    {
        if ('font-awesome-official-css' === $handle) {
            $html = str_replace(
                "media='all'",
                "media='all' integrity='sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm' crossorigin='anonymous'",
                $html
                );
        }

        return $html;
    }
}