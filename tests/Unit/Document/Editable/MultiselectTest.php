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

use Pimcore\Model\Document\Editable\Multiselect;
use Pimcore\Tests\Support\Test\TestCase;

class MultiselectTest extends TestCase
{
    public function testFrontendEscapesPersistedValuesByDefault(): void
    {
        $multiselect = new Multiselect();
        $multiselect->setDataFromResource(['<img src=x onerror=alert(1)>', 'safe-category']);

        $this->assertSame(
            '&lt;img src=x onerror=alert(1)&gt;,safe-category',
            $multiselect->frontend()
        );
    }

    public function testFrontendEscapesEditmodeSourcedValuesByDefault(): void
    {
        $multiselect = new Multiselect();
        $multiselect->setDataFromEditmode(['<img src=x onerror=alert(1)>', 'safe-category']);

        $this->assertSame(
            '&lt;img src=x onerror=alert(1)&gt;,safe-category',
            $multiselect->frontend()
        );
    }

    public function testFrontendLeavesBenignValuesUnchanged(): void
    {
        $multiselect = new Multiselect();
        $multiselect->setDataFromResource(['safe-category', 'https://example.test']);

        $this->assertSame('safe-category,https://example.test', $multiselect->frontend());
    }

    public function testGetDataReturnsUnescapedValues(): void
    {
        $multiselect = new Multiselect();
        $multiselect->setDataFromResource(['<img src=x onerror=alert(1)>']);

        $this->assertSame(['<img src=x onerror=alert(1)>'], $multiselect->getData());
    }

    public function testFrontendCanOptOutOfEscaping(): void
    {
        $multiselect = new Multiselect();
        $multiselect->setDataFromResource(['<img src=x onerror=alert(1)>']);
        $multiselect->setConfig(['htmlspecialchars' => false]);

        $this->assertSame('<img src=x onerror=alert(1)>', $multiselect->frontend());
    }
}
