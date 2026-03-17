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

namespace Pimcore\Tests\Unit\InstallBundle\Profile;

use Pimcore\Bundle\InstallBundle\Profile\PostInstallCommand;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @internal
 */
final class PostInstallCommandTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $cmd = new PostInstallCommand(
            'cache:clear',
            'Clearing cache',
        );

        $this->assertSame('cache:clear', $cmd->getCommand());
        $this->assertSame('Clearing cache', $cmd->getLabel());
        $this->assertSame(0, $cmd->getPriority());
    }

    public function testConstructorWithCustomValues(): void
    {
        $cmd = new PostInstallCommand(
            'generic-data-index:update:index',
            'Creating search index',
            priority: 100,
        );

        $this->assertSame(100, $cmd->getPriority());
    }
}
