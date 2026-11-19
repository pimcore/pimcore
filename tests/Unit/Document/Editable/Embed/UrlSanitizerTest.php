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

        $this->assertTrue($sanitizer->isUrlAllowed('<svg/onload=alert(1)>'));
        $this->assertTrue($sanitizer->isUrlAllowed('javascript:alert(1)'));
        $this->assertTrue($sanitizer->isUrlAllowed('data:text/html,<script>alert(1)</script>'));
    }

    public function testGetInstanceDefaultsToPermissive(): void
    {
        UrlSanitizer::setInstance(null);

        $this->assertTrue(UrlSanitizer::getInstance()->isUrlAllowed('<svg/onload=alert(1)>'));
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
            'svg markup' => ['<svg/onload=alert(1)>'],
            'img markup with event handler' => ['<img src=x onerror=alert(1)>'],
            'javascript scheme' => ['javascript:alert(1)'],
            'data scheme' => ['data:text/html,<script>alert(1)</script>'],
            'protocol-relative' => ['//evil.com/x'],
            'scheme without host' => ['http:evil'],
            'plain text' => ['not a url at all'],
        ];
    }

    public function testStrictAllowsLegitimateHttpUrls(): void
    {
        $sanitizer = UrlSanitizer::strict();

        $this->assertTrue($sanitizer->isUrlAllowed('https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertTrue($sanitizer->isUrlAllowed('http://example.com/path?a=1&b=2'));
        $this->assertTrue($sanitizer->isUrlAllowed(''));
    }
}
