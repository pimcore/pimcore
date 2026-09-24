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

namespace Pimcore\Model\Document\Editable\Link;

/**
 * Governs which URL schemes and which rendered HTML attribute keys the Link editable
 * (\Pimcore\Model\Document\Editable\Link) is allowed to emit for editor-supplied ("direct" link
 * path, or custom attribute) data.
 *
 * The default instance (getInstance() with nothing configured) is fully permissive: no scheme is
 * rejected and no attribute key is rejected, matching this editable's behavior prior to
 * GHSA-9g27-c28m-8xg5. strict() closes that advisory (rejects javascript:/vbscript:/script-
 * executing data: URLs, and on*-shaped attribute keys the document editor could influence).
 *
 * To opt into the stricter policy, set the "pimcore.documents.editables.link_sanitizer.strict"
 * config option to true (see Configuration::addDocumentsNode()) - PimcoreCoreBundle::boot() reads
 * it and installs a policy built from it, including the blocked scheme list, which can be
 * overridden via the "...link_sanitizer.blocked_url_schemes" config option (default:
 * DEFAULT_BLOCKED_URL_SCHEMES) to add or remove schemes without writing PHP. For a fully custom
 * policy, call setInstance() directly instead, from your own bundle's boot() method.
 *
 * Note that a document editor able to set an arbitrary "direct" link path or a custom Link
 * attribute is, under the permissive default, able to store a stored-XSS payload that executes
 * for every visitor who views or clicks the rendered link - see the advisory for the full impact.
 */
class AttributeSanitizer
{
    /**
     * The scheme list strict() uses, and the default for the
     * "pimcore.documents.editables.link_sanitizer.blocked_url_schemes" config option.
     */
    public const DEFAULT_BLOCKED_URL_SCHEMES = ['javascript:', 'vbscript:'];

    private static ?self $instance = null;

    /**
     * Tracks explicit setInstance() calls separately from $instance, because $instance is also
     * populated by getInstance()'s lazy permissive fallback - conflating the two would let merely
     * reading the sanitizer (e.g. rendering a Link) before setInstance() is ever called make
     * isConfigured() report true, causing PimcoreCoreBundle::boot() to skip a configured
     * "strict: true" policy.
     */
    private static bool $explicitlyConfigured = false;

    /**
     * @param string[] $blockedUrlSchemes lower-case scheme prefixes (including the trailing ":")
     *                                     to reject outright, e.g. ["javascript:", "vbscript:"].
     *                                     Empty by default: no scheme is rejected.
     */
    public function __construct(
        private readonly array $blockedUrlSchemes = [],
        private readonly bool $blockUnsafeDataUrls = false,
        private readonly bool $blockEditorSuppliedEventHandlerAttributes = false,
        private readonly bool $requireConventionalAttributeNameShape = false,
        private readonly bool $omitInternalDataAttributes = false,
    ) {
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
     * boot() method) to install a custom policy, most commonly AttributeSanitizer::strict(). Also
     * usable to reset the policy (pass null) - tests do this in tearDown().
     */
    public static function setInstance(?self $sanitizer): void
    {
        self::$instance = $sanitizer;
        self::$explicitlyConfigured = $sanitizer !== null;
    }

    /**
     * True once something (an application bundle, or a previous PimcoreCoreBundle::boot() call)
     * has explicitly called setInstance() with a non-null policy - never true merely because
     * getInstance() was called. PimcoreCoreBundle::boot() checks this before applying the
     * config-driven default, so it never clobbers a policy an application bundle already
     * installed.
     */
    public static function isConfigured(): bool
    {
        return self::$explicitlyConfigured;
    }

    /**
     * The policy that closes GHSA-9g27-c28m-8xg5 (stored XSS via the Link editable). This is not
     * the default - see the class docblock.
     */
    public static function strict(): self
    {
        return new self(
            blockedUrlSchemes: self::DEFAULT_BLOCKED_URL_SCHEMES,
            blockUnsafeDataUrls: true,
            blockEditorSuppliedEventHandlerAttributes: true,
            requireConventionalAttributeNameShape: true,
            omitInternalDataAttributes: true,
        );
    }

    /**
     * Whether the Link editable's own bookkeeping data (path, linktype, text, parameters, anchor,
     * internal*) is kept out of the rendered <a> tag's attributes. The permissive default keeps
     * the historical behavior of emitting them as attributes (e.g. linktype="direct").
     */
    public function omitsInternalDataAttributes(): bool
    {
        return $this->omitInternalDataAttributes;
    }

    public function isUrlAllowed(string $url): bool
    {
        if ($this->blockedUrlSchemes === [] && !$this->blockUnsafeDataUrls) {
            return true;
        }

        // browsers ignore leading/trailing whitespace and embedded control characters (e.g.
        // tabs, newlines) when parsing a URL scheme, so those are stripped before comparison to
        // prevent bypasses such as "java\tscript:"
        $normalized = strtolower(preg_replace('/[\x00-\x20]+/', '', self::decodeCharacterReferences($url)) ?? '');

        foreach ($this->blockedUrlSchemes as $scheme) {
            if (str_starts_with($normalized, strtolower($scheme))) {
                return false;
            }
        }

        if ($this->blockUnsafeDataUrls && str_starts_with($normalized, 'data:')) {
            // data:image/* covers the legitimate use case (e.g. a downloadable data-uri image);
            // image/svg+xml can still embed and execute <script>, so it stays blocked
            return (bool) preg_match('/^data:image\/(?!svg\+xml)[a-z0-9.+-]+[;,]/', $normalized);
        }

        return true;
    }

    /**
     * The Link editable writes the path into href="..." with only '"' escaped, so the browser
     * decodes character references in it before resolving the scheme - "javascript&#58;..." runs
     * as "javascript:...". Decode them the way the HTML parser does before checking: once (so
     * "&amp;#58;" stays literal), and numeric references even without the trailing ";".
     */
    private static function decodeCharacterReferences(string $url): string
    {
        return preg_replace_callback(
            '/&(?:#[xX]([0-9a-fA-F]+);?|#([0-9]+);?|[a-zA-Z][a-zA-Z0-9]*;)/',
            static function (array $match): string {
                if (($match[1] ?? '') === '' && ($match[2] ?? '') === '') {
                    return html_entity_decode($match[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }

                $codePoint = ($match[1] ?? '') !== '' ? hexdec($match[1]) : (float) $match[2];

                // out-of-range references become U+FFFD in the browser; dropping them instead is
                // stricter (it can only join, never hide, the characters around it)
                return $codePoint > 0 && $codePoint <= 0x10FFFF ? (mb_chr((int) $codePoint, 'UTF-8') ?: '') : '';
            },
            $url
        ) ?? $url;
    }

    public function isAttributeKeyAllowed(string $key, bool $editorControlled): bool
    {
        // HTML attribute names are delimited by raw whitespace, `"`, `'`, `=`, `<`, `>` and `/` -
        // an HTML parser reads these characters before any entity decoding happens, so escaping
        // the key is not enough to stop one containing them from being re-tokenized by the
        // browser into a different attribute (or several) than the single source key it came from
        if ($this->requireConventionalAttributeNameShape && !preg_match('/^[a-zA-Z_:][a-zA-Z0-9_:.-]*$/', $key)) {
            return false;
        }

        // event handler attributes (onclick, onmouseover, onerror, ...) execute script
        // regardless of how well the attribute value is escaped; only reject them once the
        // document editor could have supplied or influenced the key - a template-configured
        // handler the editor never touched is trusted
        if ($this->blockEditorSuppliedEventHandlerAttributes && $editorControlled
            && preg_match('/^on[a-z]/i', $key)) {
            return false;
        }

        return true;
    }
}
