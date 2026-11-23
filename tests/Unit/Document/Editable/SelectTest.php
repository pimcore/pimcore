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
 * Covers the HTML escaping of the Select editable's frontend() output.
 */
final class SelectTest extends TestCase
{
    private const MARKUP_VALUE = '<img src=x onerror="alert(document.domain)">';

    protected function needsDb(): bool
    {
        return false;
    }

    public function testFrontendEscapesStoredValueByDefault(): void
    {
        $select = new Select();
        $select->setDataFromEditmode(self::MARKUP_VALUE);

        $rendered = $select->frontend();

        $this->assertStringNotContainsString('<img', $rendered);
        $this->assertSame(htmlspecialchars(self::MARKUP_VALUE, ENT_QUOTES, 'UTF-8'), $rendered);
    }

    public function testFrontendEscapesQuotesAndAmpersands(): void
    {
        $select = new Select();
        $select->setDataFromEditmode('" a=\'b\' & c');

        $this->assertSame('&quot; a=&#039;b&#039; &amp; c', $select->frontend());
    }

    public function testFrontendEscapesValueLoadedFromStorage(): void
    {
        $select = new Select();
        $select->setDataFromResource(self::MARKUP_VALUE);

        $this->assertStringNotContainsString('<img', $select->frontend());
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
        $select->setDataFromEditmode(self::MARKUP_VALUE);

        $this->assertSame(self::MARKUP_VALUE, $select->frontend());
    }

    public function testUnsetSelectFrontendStaysNull(): void
    {
        $select = new Select();

        $this->assertNull($select->frontend());

        $select->setConfig(['htmlspecialchars' => false]);
        $this->assertNull($select->frontend());
    }
}
