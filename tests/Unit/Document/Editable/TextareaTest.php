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

use Pimcore\Model\Document\Editable\Textarea;
use Pimcore\Tests\Support\Test\TestCase;

class TextareaTest extends TestCase
{
    public function testEmptyTextareaFrontendWithDefaultConfig(): void
    {
        $textarea = new Textarea();

        $this->assertSame('', $textarea->frontend());
    }

    public function testEmptyTextareaFrontendWithNl2br(): void
    {
        $textarea = new Textarea();
        // htmlspecialchars must be disabled, otherwise it already converts the null text
        // to a string before it reaches nl2br()
        $textarea->setConfig(['htmlspecialchars' => false, 'nl2br' => true]);

        $this->assertSame('', $textarea->frontend());
    }

    public function testSetDataFromEditmodeWithNull(): void
    {
        $textarea = new Textarea();
        $textarea->setDataFromEditmode(null);

        $this->assertSame('', $textarea->getText());
    }
}
