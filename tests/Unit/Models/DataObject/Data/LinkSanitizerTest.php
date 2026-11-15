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

namespace Pimcore\Tests\Unit\Models\DataObject\Data;

use Pimcore\Model\DataObject\Data\Link;
use Pimcore\Model\DataObject\Data\Link\SanitizerPolicy;
use Pimcore\Model\Document\Editable\Link\AttributeSanitizer;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * GHSA-h78x-47qg-qjmq: DataObject\Data\Link::getHtml() applies the SanitizerPolicy (configured via
 * pimcore.objects.link_sanitizer.*, independent of the Document Link editable). The permissive default keeps the historical output, strict() closes the advisory.
 */
class LinkSanitizerTest extends TestCase
{
    protected function tearDown(): void
    {
        SanitizerPolicy::setInstance(null);
        parent::tearDown();
    }

    private function createLink(string $href = 'https://example.com', string $attributes = ''): Link
    {
        $link = new Link();
        $link->setDirect($href);
        $link->setText('Click');
        if ($attributes !== '') {
            $link->setAttributes($attributes);
        }

        return $link;
    }

    /**
     * @return string[]
     */
    private function captureDeprecations(callable $callback): array
    {
        $deprecations = [];
        set_error_handler(static function (int $level, string $message) use (&$deprecations): bool {
            if ($level === E_USER_DEPRECATED) {
                $deprecations[] = $message;
            }

            return true;
        });

        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $deprecations;
    }

    // ---- permissive default -------------------------------------------------------------------

    public function testDefaultKeepsBenignOutputByteIdentical(): void
    {
        $link = $this->createLink('https://example.com/?a=1&amp;b=2&c=3', "data-a='x'   data-b=y  autofocus");
        $link->setTitle('Tom &amp; Jerry <3');

        $this->assertSame(
            '<a href="https://example.com/?a=1&amp;b=2&c=3" title="Tom &amp; Jerry <3" data-a=\'x\'   data-b=y  autofocus>Click</a>',
            $link->getHtml()
        );
        $this->assertSame([], $this->captureDeprecations(fn () => $link->getHtml()));
    }

    public function testDefaultEscapesDoubleQuotesSoValuesCannotBreakOutOfTheirAttribute(): void
    {
        $link = $this->createLink('https://example.com/#x" data-marker="1');
        $link->setTitle('" data-marker="1');
        $link->setClass('" data-marker="1');

        $html = $link->getHtml();

        $this->assertStringNotContainsString('data-marker="1"', $html);
        $this->assertStringContainsString('title="&quot; data-marker=&quot;1"', $html);
        $this->assertStringContainsString('class="&quot; data-marker=&quot;1"', $html);
        $this->assertStringContainsString('href="https://example.com/#x&quot; data-marker=&quot;1"', $html);
    }

    public function testDefaultStillRendersScriptUrlAndEventHandlerButDeprecates(): void
    {
        $link = $this->createLink('javascript:alert(1)', 'autofocus onfocus=alert(1)');

        $html = '';
        $deprecations = $this->captureDeprecations(function () use ($link, &$html): void {
            $html = $link->getHtml();
        });

        $this->assertSame('<a href="javascript:alert(1)" autofocus onfocus=alert(1)>Click</a>', $html);
        $this->assertCount(2, $deprecations);
        $this->assertStringStartsWith('Since pimcore/pimcore 2026.3:', $deprecations[0]);
        $this->assertStringContainsString('will be removed in 2027.1', $deprecations[0]);
    }

    public function testExplicitCustomPolicyDoesNotTriggerTheDefaultDeprecation(): void
    {
        SanitizerPolicy::setInstance(new AttributeSanitizer());
        $link = $this->createLink('javascript:alert(1)', 'onclick=x');

        $this->assertSame([], $this->captureDeprecations(fn () => $link->getHtml()));
    }

    // ---- strict policy ------------------------------------------------------------------------

    /**
     * @dataProvider unsafeHrefProvider
     */
    public function testStrictRejectsUnsafeHref(string $href): void
    {
        SanitizerPolicy::setInstance(AttributeSanitizer::strict());

        $this->assertSame('<a href="" >Click</a>', $this->createLink($href)->getHtml());
    }

    public static function unsafeHrefProvider(): array
    {
        return [
            'javascript' => ['javascript:void(0)'],
            'vbscript' => ['vbscript:msgbox(1)'],
            'embedded whitespace' => ["java\tscript:void(0)"],
            'character reference' => ['javascript&#58;alert(1)'],
            'data text/html' => ['data:text/html,<script>alert(1)</script>'],
            'data svg' => ['data:image/svg+xml,<svg onload="alert(1)"></svg>'],
        ];
    }

    public function testStrictKeepsLegitimateUrls(): void
    {
        SanitizerPolicy::setInstance(AttributeSanitizer::strict());

        $this->assertSame('<a href="mailto:test@example.com" >Click</a>', $this->createLink('mailto:test@example.com')->getHtml());
        $this->assertSame('<a href="/relative/path?a=1&b=2" >Click</a>', $this->createLink('/relative/path?a=1&b=2')->getHtml());
    }

    public function testStrictStripsEventHandlersFromFreeFormAttributes(): void
    {
        SanitizerPolicy::setInstance(AttributeSanitizer::strict());

        $html = $this->createLink('https://example.com', 'autofocus onclick=x ONFOCUS="y" data-ok=1')->getHtml();

        $this->assertSame('<a href="https://example.com" autofocus data-ok="1">Click</a>', $html);
    }

    public function testStrictRejectsTagInjectionViaFreeFormAttributes(): void
    {
        SanitizerPolicy::setInstance(AttributeSanitizer::strict());

        $html = $this->createLink('https://example.com', 'data-x=""><script>alert(1)</script>')->getHtml();

        $this->assertStringNotContainsString('<script', $html);
        $this->assertSame('<a href="https://example.com" >Click</a>', $html);
    }

    public function testStrictKeepsQuotedValuesContainingEventHandlerTextOrAngleBrackets(): void
    {
        SanitizerPolicy::setInstance(AttributeSanitizer::strict());

        $html = $this->createLink('https://example.com', 'data-code="onclick=foo" data-label="1 < 2" data-e="a &amp; b"')->getHtml();

        $this->assertSame('<a href="https://example.com" data-code="onclick=foo" data-label="1 &lt; 2" data-e="a &amp; b">Click</a>', $html);
    }

    public function testStrictHandlesLongWhitespaceRunsBeforeInvalidTokens(): void
    {
        SanitizerPolicy::setInstance(AttributeSanitizer::strict());
        $attributes = 'data-a="1"' . str_repeat(' ', 20000) . '"><script>' . str_repeat(' ', 20000) . 'data-b="2"';

        $this->assertSame('<a href="https://example.com" data-a="1" data-b="2">Click</a>', $this->createLink('https://example.com', $attributes)->getHtml());
    }
}
