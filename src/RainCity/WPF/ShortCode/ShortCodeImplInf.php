<?php
namespace RainCity\WPF\ShortCode;

use RainCity\WPF\Documentation\ShortCodeDocumentation;

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
     * @param array<string, string>  $attrs  An array of attributes included
     *      with the short code.
     * @param string  $content The content, if any between a start and
     *      end tag for the short code. Likely an empty string when there Is
     *      no end tag.
     *
     * @return string The HTML content for the short code.
     */
    public function renderShortCode(array $attrs = [], ?string $content = null): string;

    /**
     * Fetch the documentation for the short code.
     *
     * @param array<ShortCodeDocumentation> $documentation An array of ShortCodeDocumentation instances.
     *
     * @return array<ShortCodeDocumentation> The array of ShortCodeDocumentation instances with ours added.
     *
     * @see ShortCodeDocumentation
     */
    public function getDocumentation(array $documentation): array;

    /**
     * Filter the attributes for the short code.
     * <p>
     * The filter can be used to ensure that integer attributes are
     * represented as integers for example.
     *
     * @param array<string, string> $combinedAtts The combined array of default and provided
     *       shortcode attributes.
     * @param array<string, string> $defaultPairs The default shortcode attributes.
     * @param array<string, string> $providedAtts The provided attributes.
     * @param string $shortcode   The shortcode name.
     *
     * @return array<string, string> The filtered attributes.
     */
    public function filterAttributes(
        array $combinedAtts,
        array $defaultPairs,
        array $providedAtts,
        string $shortcode
        ): array;
}
