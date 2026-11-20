<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Document\Editable\Embed;

/**
 * Governs which editmode-submitted values \Pimcore\Model\Document\Editable\Embed::frontend() hands
 * to Embera\Embera::autoEmbed().
 *
 * The default instance (getInstance() with nothing configured) is fully permissive: any value is
 * passed straight through, matching this editable's behavior prior to GHSA-v3fp-8x27-9g8f. When no
 * registered oEmbed provider recognizes the value, Embera returns it unchanged - so a value that
 * isn't actually a URL (including raw markup such as "<svg/onload=alert(1)>") is emitted verbatim
 * into the public page. strict() closes that by requiring the value to be a well-formed http(s)
 * URL with a host before it reaches Embera at all.
 *
 * To opt into the stricter policy, set the "pimcore.documents.editables.embed_sanitizer.strict"
 * config option to true - PimcoreCoreBundle::boot() reads it and installs a policy built from it.
 * For a fully custom policy, call setInstance() directly instead, from your own bundle's boot()
 * method.
 */
final class UrlSanitizer
{
    private static ?self $instance = null;

    /**
     * Tracks explicit setInstance() calls separately from $instance, because $instance is also
     * populated by getInstance()'s lazy permissive fallback - conflating the two would let merely
     * reading the sanitizer (e.g. rendering an Embed) before setInstance() is ever called make
     * isConfigured() report true, causing PimcoreCoreBundle::boot() to skip a configured
     * "strict: true" policy.
     */
    private static bool $explicitlyConfigured = false;

    public function __construct(private readonly bool $requireHttpUrl = false)
    {
    }

    /**
     * Returns the active policy, without marking it as explicitly configured - see
     * $explicitlyConfigured. A caller that needs to distinguish "nothing configured yet" from "an
     * explicit permissive policy was installed" must use isConfigured() instead.
     */
    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * The supported application extension point: call this once during bootstrap (e.g. a bundle's
     * boot() method) to install a custom policy, most commonly UrlSanitizer::strict(). Also usable
     * to reset the policy (pass null) - tests do this in tearDown().
     */
    public static function setInstance(?self $sanitizer): void
    {
        self::$instance = $sanitizer;
        self::$explicitlyConfigured = $sanitizer !== null;
    }

    /**
     * True once something (an application bundle, or a previous PimcoreCoreBundle::boot() call)
     * has explicitly called setInstance() with a non-null policy - never true merely because
     * getInstance() was called.
     */
    public static function isConfigured(): bool
    {
        return self::$explicitlyConfigured;
    }

    /**
     * The policy that closes GHSA-v3fp-8x27-9g8f (stored XSS via the Embed editable). This is not
     * the default - see the class docblock.
     */
    public static function strict(): self
    {
        return new self(requireHttpUrl: true);
    }

    public function isUrlAllowed(string $url): bool
    {
        if (!$this->requireHttpUrl) {
            return true;
        }

        if ($url === '') {
            return true;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = parse_url($url, PHP_URL_HOST);

        return in_array($scheme, ['http', 'https'], true) && !empty($host);
    }
}
