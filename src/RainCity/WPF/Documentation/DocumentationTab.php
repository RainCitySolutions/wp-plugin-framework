<?php
namespace RainCity\WPF\Documentation;

use RainCity\WPF\Settings\AdminSettingsTab;

class DocumentationTab
    extends AdminSettingsTab
{
    private const TAB_NAME = "Documentation";

    const OPTIONS_SECTION_SHORTCODES_ID = 'shortcodeSection';
    const OPTIONS_SECTION_SHORTCODES_TITLE = 'ShortCodes';

    const DOCUMENTATION_FILTER = 'rcsDocumentationFilter';


    public function __construct() {
        parent::__construct(self::TAB_NAME, DocumentationOptions::instance());
    }


    public function addSettings(string $pageSlug)
    {
        /**
         * ShortCodes section
         */
        add_settings_section(
            self::OPTIONS_SECTION_SHORTCODES_ID,
            self::OPTIONS_SECTION_SHORTCODES_TITLE,
            function () {},
            $pageSlug
            );


    }

    public function sanitize(string $pageSlug, ?array $input): ?array
    {}

}

