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

namespace Pimcore\Tests\Unit\Document\Editable;

use DOMDocument;
use DOMElement;
use Pimcore\Model\Document\Editable\Link;
use Pimcore\Tests\Support\Test\TestCase;

class LinkTest extends TestCase
{
    public function testGetHrefEscapesAttributeBreakoutCharacters(): void
    {
        $link = new Link();
        $link->setDataFromResource([
            'path' => '#x" onclick="alert(document.domain)',
            'linktype' => 'direct',
        ]);

        $this->assertSame('#x&quot; onclick=&quot;alert(document.domain)', $link->getHref());
    }

    public function testGetHrefStripsJavascriptScheme(): void
    {
        $link = new Link();
        $link->setDataFromResource([
            'path' => 'javascript:alert(document.domain)',
            'linktype' => 'direct',
        ]);

        $this->assertSame('', $link->getHref());
    }

    public function testGetHrefStripsJavascriptSchemeWithEmbeddedWhitespace(): void
    {
        $link = new Link();
        $link->setDataFromResource([
            'path' => "java\tscript:alert(document.domain)",
            'linktype' => 'direct',
        ]);

        $this->assertSame('', $link->getHref());
    }

    public function testGetHrefStripsDataScheme(): void
    {
        $link = new Link();
        $link->setDataFromResource([
            'path' => 'data:text/html,<script>alert(document.domain)</script>',
            'linktype' => 'direct',
        ]);

        $this->assertSame('', $link->getHref());
    }

    public function testGetHrefStripsDataSchemeWithSvgMediaType(): void
    {
        $link = new Link();
        $link->setDataFromResource([
            'path' => 'data:image/svg+xml,<svg onload="alert(document.domain)"></svg>',
            'linktype' => 'direct',
        ]);

        $this->assertSame('', $link->getHref());
    }

    public function testGetHrefKeepsLegitimateDataImageUri(): void
    {
        $link = new Link();
        $link->setDataFromResource([
            'path' => 'data:image/png;base64,iVBORw0KGgo=',
            'linktype' => 'direct',
        ]);

        $this->assertSame('data:image/png;base64,iVBORw0KGgo=', $link->getHref());
    }

    public function testFrontendDoesNotEmitAttributeBreakoutOrScriptScheme(): void
    {
        $xssAttribute = new Link();
        $xssAttribute->setDataFromResource([
            'path' => '#x" onclick="alert(document.domain)',
            'linktype' => 'direct',
        ]);

        // the payload lands inertly inside the (properly escaped) href value, so it must not
        // become a separate onclick attribute node on the rendered <a> element
        $this->assertNull($this->getRenderedAnchorAttribute($xssAttribute->frontend(), 'onclick'));

        $xssScheme = new Link();
        $xssScheme->setDataFromResource([
            'path' => 'javascript:alert(document.domain)',
            'linktype' => 'direct',
        ]);

        $this->assertSame('', $xssScheme->frontend());
    }

    public function testGetHrefRejectsDangerousSchemeEvenWithParametersAndAnchor(): void
    {
        $link = new Link();
        $link->setDataFromResource([
            'path' => 'javascript:alert(document.domain)',
            'linktype' => 'direct',
            'parameters' => 'foo=bar',
            'anchor' => 'section1',
        ]);

        $this->assertSame('', $link->getHref());
        $this->assertSame('', $link->frontend());
    }

    public function testGetHrefKeepsLegitimatePathIntact(): void
    {
        $link = new Link();
        $link->setDataFromResource([
            'path' => 'https://example.com/some/page',
            'linktype' => 'direct',
        ]);

        $this->assertSame('https://example.com/some/page', $link->getHref());
    }

    public function testFrontendRejectsEventHandlerAttributeInjectedViaEditableData(): void
    {
        $link = new Link();
        $link->setDataFromResource([
            'path' => 'https://example.com',
            'linktype' => 'direct',
            'onmouseover' => 'alert(document.domain)',
        ]);

        $this->assertNull($this->getRenderedAnchorAttribute($link->frontend(), 'onmouseover'));
    }

    public function testFrontendKeepsEventHandlerAttributeConfiguredOnlyByTemplate(): void
    {
        $link = new Link();
        $link->setDataFromResource([
            'path' => 'https://example.com',
            'linktype' => 'direct',
        ]);
        $link->setConfig(['onclick' => 'trackClick()']);

        $this->assertSame('trackClick()', $this->getRenderedAnchorAttribute($link->frontend(), 'onclick'));
    }

    public function testFrontendRejectsEventHandlerWhenEditorDataSharesKeyWithTrustedConfig(): void
    {
        $link = new Link();
        $link->setDataFromResource([
            'path' => 'https://example.com',
            'linktype' => 'direct',
            'onclick' => 'alert(document.domain)',
        ]);
        $link->setConfig(['onclick' => 'trackClick()']);

        // the editor-supplied value would otherwise merge into the same attribute as the
        // trusted config value (see the empty($this->data[$key]) && empty($this->config[$key])
        // branch), so the key must be rejected once the editor can influence it at all -
        // trusting it because the template also configured it is not enough
        $this->assertNull($this->getRenderedAnchorAttribute($link->frontend(), 'onclick'));
    }

    public function testFrontendRejectsAttributeKeyContainingWhitespaceOrQuote(): void
    {
        $link = new Link();
        $link->setDataFromResource([
            'path' => 'https://example.com',
            'linktype' => 'direct',
            // htmlspecialchars() on the key alone would not stop this: the raw whitespace
            // before "onmouseover" is untouched by escaping and re-tokenized by the HTML
            // parser as the start of a new, real attribute
            'x" onmouseover="alert(document.domain)' => '1',
        ]);

        $this->assertNull($this->getRenderedAnchorAttribute($link->frontend(), 'onmouseover'));
    }

    public function testFrontendDoesNotLeakInternalBookkeepingKeysAsAttributes(): void
    {
        $link = new Link();
        $link->setDataFromResource([
            'path' => 'https://example.com',
            'linktype' => 'direct',
            'text' => 'Visit us',
        ]);

        $output = $link->frontend();

        $this->assertStringNotContainsString('path="', $output);
        $this->assertStringNotContainsString('linktype="', $output);
        $this->assertStringNotContainsString('text="', $output);
    }

    public function testFrontendStillRendersDocumentedAttributes(): void
    {
        $link = new Link();
        $link->setDataFromResource([
            'path' => 'https://example.com',
            'linktype' => 'direct',
            'target' => '_blank',
            'title' => 'Example',
            'data-track' => 'homepage-link',
        ]);

        $output = $link->frontend();

        $this->assertStringContainsString('target="_blank"', $output);
        $this->assertStringContainsString('title="Example"', $output);
        $this->assertStringContainsString('data-track="homepage-link"', $output);
    }

    /**
     * Parses the rendered markup with an actual HTML parser (rather than substring matching, which
     * cannot distinguish a live attribute from inert escaped text inside another attribute's value)
     * and returns the named attribute's decoded value on the <a> element, or null if absent.
     */
    private function getRenderedAnchorAttribute(string $html, string $attributeName): ?string
    {
        $document = new DOMDocument();
        @$document->loadHTML('<!DOCTYPE html><html><body>' . $html . '</body></html>');

        $anchor = $document->getElementsByTagName('a')->item(0);
        if (!$anchor instanceof DOMElement || !$anchor->hasAttribute($attributeName)) {
            return null;
        }

        return $anchor->getAttribute($attributeName);
    }
}
