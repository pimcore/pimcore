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
 * Covers the HTML escaping of the Numeric editable's frontend() output.
 */
final class NumericTest extends TestCase
{
    private const MARKUP_VALUE = '<svg onload="alert(document.domain)"></svg>';

    protected function needsDb(): bool
    {
        return false;
    }

    public function testFrontendEscapesStoredValueByDefault(): void
    {
        $numeric = new Numeric();
        $numeric->setDataFromEditmode(self::MARKUP_VALUE);

        $rendered = $numeric->frontend();

        $this->assertStringNotContainsString('<svg', $rendered);
        $this->assertSame(htmlspecialchars(self::MARKUP_VALUE, ENT_QUOTES, 'UTF-8'), $rendered);
    }

    public function testFrontendEscapesQuotesAndAmpersands(): void
    {
        $numeric = new Numeric();
        $numeric->setDataFromEditmode('" a=\'b\' & c');

        $this->assertSame('&quot; a=&#039;b&#039; &amp; c', $numeric->frontend());
    }

    public function testFrontendEscapesValueLoadedFromStorage(): void
    {
        $numeric = new Numeric();
        $numeric->setDataFromResource(self::MARKUP_VALUE);

        $this->assertStringNotContainsString('<svg', $numeric->frontend());
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
        $numeric->setDataFromEditmode(self::MARKUP_VALUE);

        $this->assertSame(self::MARKUP_VALUE, $numeric->frontend());
    }

    public function testUnsetNumericFrontendStaysNull(): void
    {
        $numeric = new Numeric();

        $this->assertNull($numeric->frontend());

        $numeric->setConfig(['htmlspecialchars' => false]);
        $this->assertNull($numeric->frontend());
    }
}
