<?php

declare(strict_types = 1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Unit\Twig\Options;

use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Twig\Options\BlockOptions;

/**
 * @internal
 */
final class BlockOptionsTest extends TestCase
{
    public function testDefaultOptions(): void
    {
        $options = new BlockOptions();

        $this->assertSame("['manual' => false,'reload' => false,'default' => 0,]", $options->toString());
    }

    public function testManualTrue(): void
    {
        $options = new BlockOptions();
        $options->setManual(true);

        $this->assertSame("['manual' => true,'reload' => false,'default' => 0,]", $options->toString());
    }

    public function testReloadTrue(): void
    {
        $options = new BlockOptions();
        $options->setReload(true);

        $this->assertSame("['manual' => false,'reload' => true,'default' => 0,]", $options->toString());
    }

    public function testLimit(): void
    {
        $options = new BlockOptions();
        $options->setLimit(5);

        $this->assertSame("['manual' => false,'limit' => 5,'reload' => false,'default' => 0,]", $options->toString());
    }

    public function testClass(): void
    {
        $options = new BlockOptions();
        $options->setClass('my-class');

        $this->assertSame("['manual' => false,'reload' => false,'default' => 0,'class' => \"my-class\",]", $options->toString());
    }

    public function testDefault(): void
    {
        $options = new BlockOptions();
        $options->setDefault(42);

        $this->assertSame("['manual' => false,'reload' => false,'default' => 42,]", $options->toString());
    }
}
