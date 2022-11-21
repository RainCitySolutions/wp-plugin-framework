<?php
namespace RainCity\WPF\ShortCode;

/**
 * This trait provides a default implemementation for the ShortCodeImplInf
 * interface. Its primary use would be to avoid having to implement
 * getDocumentation() and filterAttributes() which may not be used by many
 * short codes.
 */
trait ShortCodeImpl
{
    /**
     * @see \RainCity\WPF\ShortCode\ShortCodeImplInf::getTagName()
     */
    public function getTagName(): string
    {
        return null;
    }

    /**
     * @see \RainCity\WPF\ShortCode\ShortCodeImplInf::renderShortCode()
     */
    public function renderShortCode(array $attrs = [], ?string $content = null): string // NOSONAR
    {
        return '';
    }

    /**
     * @see \RainCity\WPF\ShortCode\ShortCodeImplInf::getDocumentation()
     */
    public function getDocumentation(array $documentation): array // NOSONAR
    {
        return array();
    }

    /**
     * @see \RainCity\WPF\ShortCode\ShortCodeImplInf::filterAttributes()
     */
    public function filterAttributes(array $combinedAtts, array $defaultPairs, array $providedAtts, string $shortcode): array // NOSONAR
    {
        return $combinedAtts;
    }
}

