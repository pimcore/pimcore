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

namespace Pimcore\Tests\Unit\Document\Editable\Embed;

use Pimcore\Model\Document\Editable\Embed\UrlSanitizer;
use Pimcore\Tests\Support\Test\TestCase;

class UrlSanitizerTest extends TestCase
{
    protected function tearDown(): void
    {
        UrlSanitizer::setInstance(null);
        parent::tearDown();
    }

    public function testBareConstructorIsFullyPermissive(): void
    {
        $sanitizer = new UrlSanitizer();

        $this->assertTrue($sanitizer->isUrlAllowed('<b>not-a-url</b>'));
        $this->assertTrue($sanitizer->isUrlAllowed('javascript:void(0)'));
        $this->assertTrue($sanitizer->isUrlAllowed('data:text/html,<b>x</b>'));
        $this->assertTrue($sanitizer->isUrlAllowed('https://example.com/"><b>x</b>'));
    }

    public function testGetInstanceDefaultsToPermissive(): void
    {
        UrlSanitizer::setInstance(null);

        $this->assertTrue(UrlSanitizer::getInstance()->isUrlAllowed('<b>not-a-url</b>'));
    }

    public function testSetInstanceOverridesGetInstance(): void
    {
        $custom = UrlSanitizer::strict();
        UrlSanitizer::setInstance($custom);

        $this->assertSame($custom, UrlSanitizer::getInstance());
    }

    public function testIsConfiguredReflectsWhetherSetInstanceWasCalledWithANonNullPolicy(): void
    {
        UrlSanitizer::setInstance(null);
        $this->assertFalse(UrlSanitizer::isConfigured());

        UrlSanitizer::setInstance(UrlSanitizer::strict());
        $this->assertTrue(UrlSanitizer::isConfigured());

        UrlSanitizer::setInstance(null);
        $this->assertFalse(UrlSanitizer::isConfigured());
    }

    /**
     * getInstance()'s lazy fallback must not be mistaken for an explicit application policy - or
     * anything that reads the sanitizer (e.g. rendering an Embed) before PimcoreCoreBundle::boot()
     * runs would make isConfigured() falsely report true, causing boot() to skip a configured
     * "strict: true" policy entirely.
     */
    public function testGetInstanceDoesNotMarkThePolicyAsConfigured(): void
    {
        UrlSanitizer::setInstance(null);

        UrlSanitizer::getInstance();

        $this->assertFalse(UrlSanitizer::isConfigured());
    }

    /**
     * @dataProvider strictRejectedValueProvider
     */
    public function testStrictRejectsNonUrlValues(string $value): void
    {
        $this->assertFalse(UrlSanitizer::strict()->isUrlAllowed($value));
    }

    public static function strictRejectedValueProvider(): array
    {
        return [
            'markup' => ['<b>not-a-url</b>'],
            'markup with attributes' => ['<img src=x alt=y>'],
            'javascript scheme' => ['javascript:void(0)'],
            'data scheme' => ['data:text/html,<b>x</b>'],
            'protocol-relative' => ['//example.com/x'],
            'scheme without host' => ['http:example'],
            'plain text' => ['not a url at all'],
        ];
    }

    /**
     * Embera returns everything it does not replace unchanged, so a value that merely *starts*
     * with a valid URL (or is a URL-shaped value no provider recognizes) must be rejected too.
     *
     * @dataProvider strictRejectedUrlShapedValueProvider
     */
    public function testStrictRejectsUrlShapedValuesCarryingMarkupOrUnsafeCharacters(string $value): void
    {
        $this->assertFalse(UrlSanitizer::strict()->isUrlAllowed($value));
    }

    public static function strictRejectedUrlShapedValueProvider(): array
    {
        return [
            'double quote and tag in the path' => ['https://example.com/"><b>x</b>'],
            'single quote and tag in the path' => ["https://example.com/'><b>x</b>"],
            'tag in the path' => ['https://example.com/<b>x</b>'],
            'tag in the host' => ['http://example<b>.com/'],
            'tag in the query' => ['https://example.com/?q=<b>x</b>'],
            'tag in the fragment' => ['https://example.com/#<b>x</b>'],
            'provider url followed by markup' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ"><b>x</b>'],
            'lone double quote' => ['https://example.com/?q="x'],
            'lone angle bracket' => ['https://example.com/a>b'],
            'backtick' => ['https://example.com/`x'],
            'space' => ['https://example.com/ x'],
            'tab' => ["https://example.com/\tx"],
            'line feed' => ["https://example.com/\nx"],
            'carriage return' => ["https://example.com/\rx"],
            'form feed' => ["https://example.com/\x0cx"],
            'NUL byte' => ["https://example.com/\0x"],
            'DEL character' => ["https://example.com/\x7fx"],
            'trailing line feed' => ["https://example.com/\n"],
        ];
    }

    /**
     * @dataProvider strictAllowedUrlProvider
     */
    public function testStrictAllowsLegitimateHttpUrls(string $value): void
    {
        $this->assertTrue(UrlSanitizer::strict()->isUrlAllowed($value));
    }

    public static function strictAllowedUrlProvider(): array
    {
        return [
            'provider url' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            'provider url with several query parameters' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10s&list=PL1'],
            'plain http url with query' => ['http://example.com/path?a=1&b=2'],
            'percent-encoded path, query and fragment' => ['https://example.com/a%20b?x=%22y%22#frag'],
            'reserved characters that are legal in a path' => ['https://example.com/a(b)~c,d;e=f@g:h'],
            'upper case scheme and host' => ['HTTPS://EXAMPLE.COM/Path'],
            'url with port' => ['http://localhost:8080/video'],
            'host consisting of the digit zero' => ['http://0'],
            'empty value (nothing to embed)' => [''],
        ];
    }
}
