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

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition;

use PHPUnit\Framework\TestCase;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Fieldcontainer;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Iframe;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Text;

/**
 * These layout fields are optional in the class editor, so setValues() never calls their setter
 * when they are left empty. Without a declared default the typed properties stay uninitialized and
 * the getters raise "must not be accessed before initialization" instead of returning ''.
 */
class LayoutOptionalStringDefaultsTest extends TestCase
{
    public function testTextRenderingDataDefaultsToEmptyString(): void
    {
        $this->assertSame('', (new Text())->getRenderingData());
    }

    public function testIframeUrlDefaultsToEmptyString(): void
    {
        $this->assertSame('', (new Iframe())->getIframeUrl());
    }

    public function testIframeRenderingDataDefaultsToEmptyString(): void
    {
        $this->assertSame('', (new Iframe())->getRenderingData());
    }

    public function testFieldcontainerFieldLabelDefaultsToEmptyString(): void
    {
        $this->assertSame('', (new Fieldcontainer())->getFieldLabel());
    }
}
