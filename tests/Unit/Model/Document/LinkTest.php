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

namespace Pimcore\Tests\Unit\Model\Document;

use Pimcore\Model\Document\Editable\Link\AttributeSanitizer;
use Pimcore\Model\Document\Link;
use Pimcore\Model\Property;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for GHSA-4wc2-j95x-2ggh: Link::getHtml() escaped navigation_name with
 * htmlspecialchars() but concatenated navigation_target, navigation_title, navigation_class and
 * navigation_relation (plus the sibling navigation_accesskey/navigation_tabindex attributes built
 * by the same loop, and the href value) straight into the <a> tag, so a document editor able to
 * set those link properties could break out of an attribute and plant live event handlers that
 * executed for every visitor of the rendered page.
 */
class LinkTest extends TestCase
{
    protected function tearDown(): void
    {
        AttributeSanitizer::setInstance(null);

        parent::tearDown();
    }

    /**
     * @param array<string, string> $values keyed by property name, e.g. 'navigation_target'
     */
    private function setDocumentProperties(Link $link, array $values): void
    {
        $properties = [];
        foreach ($values as $name => $value) {
            $property = new Property();
            $property->setType('text');
            $property->setName($name);
            $property->setData($value);
            $properties[$name] = $property;
        }

        $link->setProperties($properties);
    }

    public function testGetHtmlEscapesAttributeBreakoutPayloads(): void
    {
        $payload = 'x" onmouseover="alert(document.domain)';

        $link = new Link();
        $link->setLinktype('direct');
        $link->setDirect('https://example.com/');

        $this->setDocumentProperties($link, [
            'navigation_class' => $payload,
            'navigation_target' => $payload,
            'navigation_title' => $payload,
            'navigation_relation' => $payload,
            'navigation_name' => 'poc link',
        ]);

        $html = $link->getHtml();

        $this->assertStringNotContainsString('" onmouseover="', $html);
        $this->assertSame(
            '<a href="https://example.com/" '
            . 'class="x&quot; onmouseover=&quot;alert(document.domain)" '
            . 'target="x&quot; onmouseover=&quot;alert(document.domain)" '
            . 'title="x&quot; onmouseover=&quot;alert(document.domain)" '
            . 'rel="x&quot; onmouseover=&quot;alert(document.domain)">poc link</a>',
            $html
        );
    }

    public function testGetHtmlEscapesDirectHrefBreakoutPayload(): void
    {
        $link = new Link();
        $link->setLinktype('direct');
        $link->setDirect('https://example.com/" onmouseover="alert(document.domain)');

        $this->setDocumentProperties($link, [
            'navigation_name' => 'poc link',
        ]);

        $html = $link->getHtml();

        $this->assertStringNotContainsString('" onmouseover="', $html);
        $this->assertStringContainsString(
            'href="https://example.com/&quot; onmouseover=&quot;alert(document.domain)"',
            $html
        );
    }

    public function testGetHtmlRendersLegitimateAttributesUnchanged(): void
    {
        $link = new Link();
        $link->setLinktype('direct');
        $link->setDirect('https://example.com/');

        $this->setDocumentProperties($link, [
            'navigation_class' => 'btn btn-primary',
            'navigation_target' => '_blank',
            'navigation_title' => 'Visit Example',
            'navigation_relation' => 'noopener',
            'navigation_name' => 'Example',
        ]);

        $html = $link->getHtml();

        $this->assertSame(
            '<a href="https://example.com/" class="btn btn-primary" target="_blank" '
            . 'title="Visit Example" rel="noopener">Example</a>',
            $html
        );
    }

    /**
     * Escaping alone does not neutralize a script-executing URL scheme in an editor-controlled
     * "direct" link, so the strict link sanitizer policy has to reject it.
     *
     * @dataProvider scriptExecutingUrlProvider
     */
    public function testGetHtmlRendersEmptyHrefForScriptExecutingUrlUnderStrictPolicy(string $url): void
    {
        AttributeSanitizer::setInstance(AttributeSanitizer::strict());

        $link = new Link();
        $link->setLinktype('direct');
        $link->setDirect($url);

        $this->setDocumentProperties($link, [
            'navigation_name' => 'poc link',
        ]);

        $this->assertSame('<a href="" >poc link</a>', $link->getHtml());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function scriptExecutingUrlProvider(): array
    {
        return [
            'javascript' => ['javascript:alert(document.domain)'],
            'mixed case with whitespace' => [" JaVa\tScRiPt:alert(document.domain)"],
            'vbscript' => ['vbscript:msgbox(1)'],
            'html data uri' => ['data:text/html,<script>alert(document.domain)</script>'],
        ];
    }

    public function testGetHtmlKeepsRegularUrlUnderStrictPolicy(): void
    {
        AttributeSanitizer::setInstance(AttributeSanitizer::strict());

        $link = new Link();
        $link->setLinktype('direct');
        $link->setDirect('https://example.com/path?a=1');

        $this->setDocumentProperties($link, [
            'navigation_name' => 'Example',
        ]);

        $this->assertSame('<a href="https://example.com/path?a=1" >Example</a>', $link->getHtml());
    }

    /**
     * The permissive default policy rejects no URL scheme, so the href is kept (escaped) as before.
     */
    public function testGetHtmlKeepsUrlSchemeUnderPermissiveDefaultPolicy(): void
    {
        $link = new Link();
        $link->setLinktype('direct');
        $link->setDirect('javascript:void(0)');

        $this->setDocumentProperties($link, [
            'navigation_name' => 'noop',
        ]);

        $this->assertSame('<a href="javascript:void(0)" >noop</a>', $link->getHtml());
    }
}
