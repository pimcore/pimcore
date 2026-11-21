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

use Pimcore\Model\Document\Editable\Select;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Regression test for GHSA-6x4h-x5fw-8w6h: a document-edit user could store arbitrary HTML/JS as
 * the editmode value, which was returned unescaped from frontend(), resulting in stored XSS.
 */
final class SelectTest extends TestCase
{
    private const XSS_PAYLOAD = '<img src=x onerror="alert(document.domain)">';

    protected function needsDb(): bool
    {
        return false;
    }

    public function testFrontendEscapesStoredValueByDefault(): void
    {
        $select = new Select();
        $select->setDataFromEditmode(self::XSS_PAYLOAD);

        $rendered = $select->frontend();

        $this->assertStringNotContainsString('onerror="alert(document.domain)"', $rendered);
        $this->assertStringContainsString(
            htmlspecialchars(self::XSS_PAYLOAD, ENT_QUOTES, 'UTF-8'),
            $rendered
        );
    }

    public function testFrontendStillRendersALegitimateValue(): void
    {
        $select = new Select();
        $select->setDataFromEditmode('wide');

        $this->assertSame('wide', $select->frontend());
    }

    public function testFrontendWithHtmlspecialcharsDisabled(): void
    {
        $select = new Select();
        $select->setConfig(['htmlspecialchars' => false]);
        $select->setDataFromEditmode(self::XSS_PAYLOAD);

        $this->assertSame(self::XSS_PAYLOAD, $select->frontend());
    }

    public function testEmptySelectFrontendWithDefaultConfig(): void
    {
        $select = new Select();

        $this->assertSame('', $select->frontend());
    }
}
