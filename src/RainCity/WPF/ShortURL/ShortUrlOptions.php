<?php
namespace RainCity\WPF\ShortURL;

use RainCity\WPF\WordPressOptions;

class ShortUrlOptions extends WordPressOptions
{
    const OPTIONS_NAME = 'raincity_wpf_shorturl_options';

    const URL_PREFIX = 'urlPrefix';

    /**
     * Initialize the collections used to maintain the values.
     */
    protected function initializeInstance() {
        parent::initializeInstance();

        $this->initializeOptions(
            self::OPTIONS_NAME,
            array(
                self::URL_PREFIX
                )
            );
    }

    public function getUrlPrefix(): ?string {
        return $this->getValue(self::URL_PREFIX);
    }
}
