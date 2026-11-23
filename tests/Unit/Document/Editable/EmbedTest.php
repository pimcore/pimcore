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

use Pimcore\Model\Document\Editable\Embed;
use Pimcore\Model\Document\Editable\Embed\UrlSanitizer;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * The strict() policy tests exercise values the policy rejects: frontend() only reaches the
 * mpratt/embera call after the sanitizer check passes, so they prove the early return without
 * depending on any oEmbed provider (or the network). The default-policy tests use a value that
 * contains no URL, so Embera has nothing to look up.
 */
class EmbedTest extends TestCase
{
    protected function tearDown(): void
    {
        UrlSanitizer::setInstance(null);
        parent::tearDown();
    }

    public function testFrontendReturnsEmptyStringWhenUrlIsNotSet(): void
    {
        $embed = new Embed();

        $this->assertSame('', $embed->frontend());
    }

    /**
     * @dataProvider strictRejectedValueProvider
     */
    public function testStrictPolicyRendersNothingForRejectedValues(string $value): void
    {
        UrlSanitizer::setInstance(UrlSanitizer::strict());

        $embed = new Embed();
        $embed->setDataFromEditmode(['url' => $value]);

        $this->assertSame('', $embed->frontend());
    }

    public static function strictRejectedValueProvider(): array
    {
        return [
            'markup' => ['<b>not-a-url</b>'],
            'javascript scheme' => ['javascript:void(0)'],
            'data scheme' => ['data:text/html,<b>x</b>'],
            'url followed by markup' => ['https://example.com/"><b>x</b>'],
            'provider url followed by markup' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ"><b>x</b>'],
            'url with tag in the path' => ['https://example.com/<b>x</b>'],
            'markup inside surrounding whitespace' => [" \n https://example.com/\"><b>x</b> \n "],
        ];
    }

    public function testStrictPolicyStillRendersAUrlWithSurroundingWhitespace(): void
    {
        UrlSanitizer::setInstance(UrlSanitizer::strict());

        $embed = new Embed();
        $embed->setDataFromEditmode(['url' => "  https://example.com/video\r\n"]);

        // no oEmbed provider matches example.com, so Embera returns the stored value unchanged
        $this->assertSame('https://example.com/video', trim($embed->frontend()));
        $this->assertSame([], $this->captureDeprecations(fn () => $embed->frontend()));
    }

    public function testStrictPolicyDoesNotTriggerTheDefaultDeprecation(): void
    {
        UrlSanitizer::setInstance(UrlSanitizer::strict());

        $embed = new Embed();
        $embed->setDataFromResource(['url' => '<b>not-a-url</b>']);

        $this->assertSame([], $this->captureDeprecations(fn () => $embed->frontend()));
    }

    public function testDefaultPolicyTriggersDeprecationForAValueTheStrictPolicyWouldReject(): void
    {
        UrlSanitizer::setInstance(null);

        $embed = new Embed();
        $embed->setDataFromResource(['url' => 'not a url at all']);

        $deprecations = $this->captureDeprecations(fn () => $embed->frontend());

        $this->assertCount(1, $deprecations);
        $this->assertStringStartsWith('Since pimcore/pimcore 2026.3:', $deprecations[0]);
        $this->assertStringContainsString('will be removed in 2027.1', $deprecations[0]);
    }

    public function testExplicitPermissivePolicyDoesNotTriggerTheDefaultDeprecation(): void
    {
        // an application deliberately allowing everything is an explicit opt-out, not the
        // deprecated unconfigured default
        UrlSanitizer::setInstance(new UrlSanitizer());

        $embed = new Embed();
        $embed->setDataFromResource(['url' => 'not a url at all']);

        $this->assertSame([], $this->captureDeprecations(fn () => $embed->frontend()));
    }

    public function testGetDataAndGetUrlReflectTheStoredValue(): void
    {
        $embed = new Embed();
        $embed->setDataFromResource(['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);

        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $embed->getUrl());
        $this->assertSame(['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'], $embed->getData());
        $this->assertSame(['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'], $embed->getDataForResource());
    }

    public function testSetDataFromEditmodeStoresTheUrl(): void
    {
        $embed = new Embed();
        $embed->setDataFromEditmode(['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);

        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $embed->getUrl());
    }

    public function testIsEmptyReflectsWhetherAUrlIsSet(): void
    {
        $empty = new Embed();
        $this->assertTrue($empty->isEmpty());

        $notEmpty = new Embed();
        $notEmpty->setDataFromResource(['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);
        $this->assertFalse($notEmpty->isEmpty());
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
}
