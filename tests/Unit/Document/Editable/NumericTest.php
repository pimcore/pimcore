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

use Pimcore\Model\Document\Editable\Numeric;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for GHSA-6x4h-x5fw-8w6h: a document-edit user could store arbitrary HTML/JS as
 * the editmode value, which was returned unescaped from frontend(), resulting in stored XSS.
 */
final class NumericTest extends TestCase
{
    private const XSS_PAYLOAD = '<svg onload="alert(document.domain)"></svg>';

    protected function needsDb(): bool
    {
        return false;
    }

    public function testFrontendEscapesStoredValueByDefault(): void
    {
        $numeric = new Numeric();
        $numeric->setDataFromEditmode(self::XSS_PAYLOAD);

        $rendered = $numeric->frontend();

        $this->assertStringNotContainsString('onload="alert(document.domain)"', $rendered);
        $this->assertStringContainsString(
            htmlspecialchars(self::XSS_PAYLOAD, ENT_QUOTES, 'UTF-8'),
            $rendered
        );
    }

    public function testFrontendStillRendersALegitimateValue(): void
    {
        $numeric = new Numeric();
        $numeric->setDataFromEditmode('42.5');

        $this->assertSame('42.5', $numeric->frontend());
    }

    public function testFrontendWithHtmlspecialcharsDisabled(): void
    {
        $numeric = new Numeric();
        $numeric->setConfig(['htmlspecialchars' => false]);
        $numeric->setDataFromEditmode(self::XSS_PAYLOAD);

        $this->assertSame(self::XSS_PAYLOAD, $numeric->frontend());
    }

    public function testEmptyNumericFrontendWithDefaultConfig(): void
    {
        $numeric = new Numeric();

        $this->assertSame('', $numeric->frontend());
    }
}
