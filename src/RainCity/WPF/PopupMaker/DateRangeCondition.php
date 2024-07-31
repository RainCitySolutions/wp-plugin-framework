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

    /**
     * Add hooks for the PopupMaker condition.
     *
     * @param ActionFilterLoader $loader
     */
    public function loadActions(ActionFilterLoader $loader): void {
        if (class_exists('Popup_Maker')) {
            $loader->addAction('admin_enqueue_scripts', $this, 'adminEnqueueScripts');
            $loader->addAction('admin_print_footer_scripts', $this, 'adminPrintFooterScripts', -1);
            $loader->addFilter('wp_script_attributes', $this, 'wpScriptAttributes', 10, 1 );
            $loader->addFilter('style_loader_tag', $this, 'styleLoaderTag', 10, 2);

            $loader->addFilter('pum_registered_conditions', $this, 'registerConditions');
        }
    }

    /**
     * Callback for the 'pum_registered_conditions' filter.
     *
     * Adds our custom conditions to the list of possible conditions.
     *
     * @param array<string, array<mixed>> $conditions An array of conditions.
     *
     * @return array<string, array<mixed>> The modified conditions array.
     */
    public function registerConditions(array $conditions = []): array
    {
        $conditions = array_merge(
            $conditions,
            [
                'raincity_date_range' => [
                    'group'    => 'General',
                    'name'     => 'Date Range',
                    'fields'   => [
                        'start_date' => [
                            'label'   => 'Start Date/Time',
                            'type'          => 'datetimepicker',
                            'priority'      => 1,
                        ],
                        'end_date' => [
                            'label'   => 'End Date/Time',
                            'type'          => 'datetimepicker',
                            'priority'      => 1,
                        ],
                    ],
                    'callback' => array ($this,'isInDateRange')
                ]
            ]
            );

        return $conditions;
    }

    /**
     *
     * @param array<string, array<mixed>> $condition
     *
     * @return bool
     */
    public function isInDateRange(array $condition): bool
    {
        //TODO: check if data is in range
        return true;
    }

    public function adminPrintFooterScripts(): void
    {
        if ((wp_script_is('pum-admin-general') || wp_script_is('popup-maker-admin')) &&
            (did_action('admin_footer') || doing_action('admin_footer')) )
        {
            ?>
            <script type="text/html" id="tmpl-pum-field-datetimepicker">
                <div id="{{data.id}}-container">
                    <div
                        class="input-group"
                        id="{{data.id}}-wrapper"
                        data-td-target-input="nearest"
                        data-td-target-toggle="nearest"
                    >
                        <input
                            id="{{data.id}}"
                            type="text"
                            class="form-control {{data.size}}-text"
                            name="{{data.name}}"
                            data-td-target="#{{data.id}}-wrapper"
                            value="{{data.value}}"
                            {{{data.meta}}}
                            />
                        <span
                            class="input-group-text"
                            title="Select Date/Time"
                            data-td-target="#{{data.id}}-wrapper"
                            data-td-toggle="datetimepicker"
                            >
                            <i class="fas fa-calendar-alt"></i>
                        </span>
                    </div>
                </div>
                <#
                    if (data.id !== '') {
                        new Promise(resolve => setTimeout(resolve, 1000)).then(() => {
                            let wrapperId = data.id + "-wrapper";
                            let wrapper = document.getElementById(wrapperId);

                            if (wrapper !== null) {
                                new tempusDominus.TempusDominus(wrapper, {
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
                                    }
                                });
                            }
                        });
                    }
                #>
            </script>
            <?php
        }

    }

    public function adminEnqueueScripts(): void
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
            'https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@'.self::TEMPUS_DOMINUS_VERSION.'/dist/js/tempus-dominus.js',
            ['jquery', self::POPPER_HANDLE],
            self::TEMPUS_DOMINUS_VERSION,
            true
            );

        wp_enqueue_style(
            'font-awesome-official-css',
            'https://use.fontawesome.com/releases/v5.15.4/css/all.css',
            [],
            '5.15.4'
            );

        wp_enqueue_style(
            self::TEMPUS_DOMINUS_HANDLE,
            'https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@'.self::TEMPUS_DOMINUS_VERSION.'/dist/css/tempus-dominus.min.css',
            [], //[self::BOOTSTRAP_HANDLE],
            self::TEMPUS_DOMINUS_VERSION
            );
    }

    /**
     *
     * @param array<string, mixed> $attr
     *
     * @return array<string, mixed>
     */
    public function wpScriptAttributes(array $attr): array
    {
        if (self::POPPER_HANDLE.'-js' == $attr['id'] ||
            self::TEMPUS_DOMINUS_HANDLE.'-js' == $attr['id'])
        {
            $attr['crossorigin'] = 'anonymous';
        }

        return $attr;
    }

    public function styleLoaderTag(string $html, string $handle): string
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
