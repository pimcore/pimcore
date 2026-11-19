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
 * frontend() only reaches the mpratt/embera call after the sanitizer check passes, so every test
 * here exercises a value the strict() policy rejects - this proves the early return and never
 * requires the actual Embera library to be installed.
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

    public function testStrictPolicyRejectsSvgMarkupBeforeReachingEmbera(): void
    {
        UrlSanitizer::setInstance(UrlSanitizer::strict());

        $embed = new Embed();
        $embed->setDataFromResource(['url' => '<svg/onload=alert(1)>']);

        $this->assertSame('', $embed->frontend());
    }

    public function testStrictPolicyRejectsJavascriptSchemeBeforeReachingEmbera(): void
    {
        UrlSanitizer::setInstance(UrlSanitizer::strict());

        $embed = new Embed();
        $embed->setDataFromResource(['url' => 'javascript:alert(1)']);

        $this->assertSame('', $embed->frontend());
    }

    public function testStrictPolicyRejectsDataSchemeBeforeReachingEmbera(): void
    {
        UrlSanitizer::setInstance(UrlSanitizer::strict());

        $embed = new Embed();
        $embed->setDataFromResource(['url' => 'data:text/html,<script>alert(1)</script>']);

        $this->assertSame('', $embed->frontend());
    }

    public function testStrictPolicyDoesNotTriggerTheDefaultDeprecation(): void
    {
        UrlSanitizer::setInstance(UrlSanitizer::strict());

        $embed = new Embed();
        $embed->setDataFromResource(['url' => '<svg/onload=alert(1)>']);

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
