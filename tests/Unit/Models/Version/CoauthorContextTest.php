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

namespace Pimcore\Tests\Unit\Model\Version;

use Pimcore\Model\Version\CoauthorContext;
use Pimcore\Tests\Support\Test\TestCase;

class CoauthorContextTest extends TestCase
{
    public function testContextIsInactiveByDefault(): void
    {
        $context = new CoauthorContext();

        $this->assertFalse($context->isActive());
        $this->assertNull($context->getType());
        $this->assertNull($context->getCoauthor());
    }

    public function testSetActivatesContext(): void
    {
        $context = new CoauthorContext();
        $context->set('agent', 'product-data-agent');

        $this->assertTrue($context->isActive());
        $this->assertSame('agent', $context->getType());
        $this->assertSame('product-data-agent', $context->getCoauthor());
    }

    public function testClearResetsContext(): void
    {
        $context = new CoauthorContext();
        $context->set('agent', 'product-data-agent');
        $context->clear();

        $this->assertFalse($context->isActive());
    }

    public function testDisableSuppressesActivationButKeepsValues(): void
    {
        $context = new CoauthorContext();
        $context->set('agent', 'product-data-agent');
        $context->disable();

        $this->assertFalse($context->isActive());
        $this->assertFalse($context->isEnabled());
        $this->assertSame('agent', $context->getType());

        $context->enable();
        $this->assertTrue($context->isActive());
    }

    public function testResetRestoresDefaults(): void
    {
        $context = new CoauthorContext();
        $context->set('agent', 'product-data-agent');
        $context->disable();

        $context->reset();

        $this->assertTrue($context->isEnabled());
        $this->assertNull($context->getType());
        $this->assertFalse($context->isActive());
    }

    public function testWithCoauthorRestoresPreviousStateAndReturnsResult(): void
    {
        $context = new CoauthorContext();
        $context->set('automation', 'importer');

        $result = $context->withCoauthor('agent', 'product-data-agent', function () use ($context) {
            $this->assertSame('agent', $context->getType());
            $this->assertSame('product-data-agent', $context->getCoauthor());

            return 42;
        });

        $this->assertSame(42, $result);
        $this->assertSame('automation', $context->getType());
        $this->assertSame('importer', $context->getCoauthor());
    }

    public function testWithCoauthorRestoresEmptyState(): void
    {
        $context = new CoauthorContext();

        $context->withCoauthor('agent', 'product-data-agent', static fn () => null);

        $this->assertNull($context->getType());
        $this->assertNull($context->getCoauthor());
        $this->assertFalse($context->isActive());
    }
}
