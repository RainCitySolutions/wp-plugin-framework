<?php
namespace RainCity\WPF\ShortCode;

interface ShortCodeImplInf
{
    /**
     * Fetch the tag name of the short code.
     *
     * @return string A string identifying the short code.
     */
    public function getTagName(): string;

    /**
     * Render the short code.
     * <p>
     * Because this function will ultimately be called by WordPress we cannot
     * enforce parameter types. The defaults might not help but they can't
     * hurt.
     *
     * @param string|array  $attrs  An array of attributes included with the
     *      short code. Likely an empty string if no attributes were provided.
     * @param mixed         $content The content, if any between a start and
     *      end tag for the short code. Likely an empty string when there Is
     *      no end tag.
     *
     * @return string The HTML content for the short code.
     */
    public function renderShortCode($attrs = [], $content = null): string;

    /**
     * Fetch the documentation for the short code.
     *
     * @param array $documentation An array of {@link ShortCodeDocumentation} instances.
     *
     * @return array The array of ShortCodeDocumentation instances with ours added.
     */
    public function getDocumentation(array $documentation): array;
}
