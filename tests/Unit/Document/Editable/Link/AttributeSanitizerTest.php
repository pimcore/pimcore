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

namespace Pimcore\Tests\Unit\Document\Editable\Link;

use Pimcore\Model\Document\Editable\Link\AttributeSanitizer;
use Pimcore\Tests\Support\Test\TestCase;

class AttributeSanitizerTest extends TestCase
{
    protected function tearDown(): void
    {
        AttributeSanitizer::setInstance(null);
        parent::tearDown();
    }

    public function testBareConstructorIsFullyPermissive(): void
    {
        $sanitizer = new AttributeSanitizer();

        $this->assertTrue($sanitizer->isUrlAllowed('javascript:alert(document.domain)'));
        $this->assertTrue($sanitizer->isUrlAllowed('vbscript:msgbox("x")'));
        $this->assertTrue($sanitizer->isUrlAllowed('data:text/html,<script>alert(1)</script>'));
        $this->assertTrue($sanitizer->isAttributeKeyAllowed('onclick', true));
        $this->assertTrue($sanitizer->isAttributeKeyAllowed('x" onmouseover="alert(1)', true));
    }

    public function testGetInstanceDefaultsToPermissive(): void
    {
        AttributeSanitizer::setInstance(null);

        $this->assertTrue(AttributeSanitizer::getInstance()->isUrlAllowed('javascript:alert(1)'));
        $this->assertTrue(AttributeSanitizer::getInstance()->isAttributeKeyAllowed('onclick', true));
    }

    public function testSetInstanceOverridesGetInstance(): void
    {
        $custom = AttributeSanitizer::strict();
        AttributeSanitizer::setInstance($custom);

        $this->assertSame($custom, AttributeSanitizer::getInstance());
    }

    public function testIsConfiguredReflectsWhetherSetInstanceWasCalledWithANonNullPolicy(): void
    {
        AttributeSanitizer::setInstance(null);
        $this->assertFalse(AttributeSanitizer::isConfigured());

        AttributeSanitizer::setInstance(AttributeSanitizer::strict());
        $this->assertTrue(AttributeSanitizer::isConfigured());

        AttributeSanitizer::setInstance(null);
        $this->assertFalse(AttributeSanitizer::isConfigured());
    }

    /**
     * getInstance()'s lazy fallback must not be mistaken for an explicit application policy - or
     * anything that reads the sanitizer (e.g. rendering a Link) before PimcoreCoreBundle::boot()
     * runs would make isConfigured() falsely report true, causing boot() to skip a configured
     * "strict: true" policy entirely.
     */
    public function testGetInstanceDoesNotMarkThePolicyAsConfigured(): void
    {
        AttributeSanitizer::setInstance(null);

        AttributeSanitizer::getInstance();

        $this->assertFalse(AttributeSanitizer::isConfigured());
    }

    /**
     * @dataProvider strictBlockedSchemeProvider
     */
    public function testStrictBlocksDangerousSchemes(string $url): void
    {
        $this->assertFalse(AttributeSanitizer::strict()->isUrlAllowed($url));
    }

    public static function strictBlockedSchemeProvider(): array
    {
        return [
            'javascript' => ['javascript:alert(document.domain)'],
            'javascript with embedded whitespace' => ["java\tscript:alert(1)"],
            'vbscript' => ['vbscript:msgbox("x")'],
            'data text/html' => ['data:text/html,<script>alert(1)</script>'],
            'data image/svg+xml' => ['data:image/svg+xml,<svg onload="alert(1)"></svg>'],
        ];
    }

    public function testStrictAllowsLegitimateUrls(): void
    {
        $sanitizer = AttributeSanitizer::strict();

        $this->assertTrue($sanitizer->isUrlAllowed('https://example.com/some/page?a=1&b=2'));
        $this->assertTrue($sanitizer->isUrlAllowed('data:image/png;base64,iVBORw0KGgo='));
        $this->assertTrue($sanitizer->isUrlAllowed(''));
    }

    public function testStrictRejectsEditorSuppliedEventHandlerAttribute(): void
    {
        $sanitizer = AttributeSanitizer::strict();

        $this->assertFalse($sanitizer->isAttributeKeyAllowed('onclick', true));
        $this->assertFalse($sanitizer->isAttributeKeyAllowed('onmouseover', true));
    }

    public function testStrictAllowsTemplateOnlyEventHandlerAttribute(): void
    {
        $sanitizer = AttributeSanitizer::strict();

        $this->assertTrue($sanitizer->isAttributeKeyAllowed('onclick', false));
    }

    public function testStrictRejectsMalformedAttributeKeyShape(): void
    {
        $sanitizer = AttributeSanitizer::strict();

        $this->assertFalse($sanitizer->isAttributeKeyAllowed('x" onmouseover="alert(1)', false));
        $this->assertFalse($sanitizer->isAttributeKeyAllowed('x" onmouseover="alert(1)', true));
    }

    public function testStrictAllowsConventionalAttributeKeys(): void
    {
        $sanitizer = AttributeSanitizer::strict();

        foreach (['target', 'title', 'class', 'rel', 'data-track', 'aria-label'] as $key) {
            $this->assertTrue($sanitizer->isAttributeKeyAllowed($key, true));
        }
    }

    public function testBlockedUrlSchemesIsConfigurable(): void
    {
        $sanitizer = new AttributeSanitizer(blockedUrlSchemes: ['mailto:', 'tel:']);

        $this->assertFalse($sanitizer->isUrlAllowed('mailto:someone@example.com'));
        $this->assertFalse($sanitizer->isUrlAllowed('tel:+1234567890'));
        // a scheme not in the configured list is allowed, even though it's normally part of strict()
        $this->assertTrue($sanitizer->isUrlAllowed('javascript:alert(1)'));
    }

    public function testBlockedUrlSchemesMatchIsCaseInsensitive(): void
    {
        $sanitizer = new AttributeSanitizer(blockedUrlSchemes: ['JavaScript:']);

        $this->assertFalse($sanitizer->isUrlAllowed('JAVASCRIPT:alert(1)'));
    }

    public function testBlockUnsafeDataUrlsIsIndependentOfBlockedUrlSchemes(): void
    {
        $sanitizer = new AttributeSanitizer(blockedUrlSchemes: ['javascript:'], blockUnsafeDataUrls: false);

        $this->assertFalse($sanitizer->isUrlAllowed('javascript:alert(1)'));
        // blockUnsafeDataUrls is off, so this is allowed even though blockedUrlSchemes is non-empty
        $this->assertTrue($sanitizer->isUrlAllowed('data:text/html,<script>alert(1)</script>'));
    }

    public function testDefaultBlockedUrlSchemesConstantMatchesStrict(): void
    {
        $sanitizer = new AttributeSanitizer(blockedUrlSchemes: AttributeSanitizer::DEFAULT_BLOCKED_URL_SCHEMES);

        $this->assertFalse($sanitizer->isUrlAllowed('javascript:alert(1)'));
        $this->assertFalse($sanitizer->isUrlAllowed('vbscript:msgbox("x")'));
    }
}
