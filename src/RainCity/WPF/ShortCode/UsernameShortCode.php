<?php
namespace RainCity\WPF\ShortCode;

use RainCity\WPF\Utils;
use RainCity\WPF\Documentation\ShortCodeDocumentation;

class UsernameShortCode
    implements ShortCodeImplInf
{
    /**
     *
     * {@inheritDoc}
     * @see \RainCity\WPF\ShortCode\ShortCodeImplInf::getTagName()
     */
    public function getTagName(): string
    {
        return 'raincity_wpf_username';
    }

    /**
     *
     * {@inheritDoc}
     * @see \RainCity\WPF\ShortCode\ShortCodeImplInf::renderShortCode()
     */
    public function renderShortCode($attrs = [], $content = null): string
    {
        $wpUser = Utils::getWPUser();

        return $wpUser->user_login;
    }

    public function getDocumentation(array $documentation): array
    {
        array_push ($documentation, new ShortCodeDocumentation(
            $this->getTagName(),
            'Renders the username of the currently logged in user, if there is one.'));

            return $documentation;
    }
}
