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

namespace Pimcore\Tests\Unit\InstallBundle\Event;

use Pimcore\Bundle\InstallBundle\Event\InstallEvents;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionClass;

/**
 * @internal
 */
final class InstallEventsTest extends TestCase
{
    public function testEventNameStepConstant(): void
    {
        $this->assertSame('pimcore.installer.step', InstallEvents::EVENT_NAME_STEP);
    }

    public function testClassIsFinal(): void
    {
        $reflection = new ReflectionClass(InstallEvents::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function testCannotBeInstantiated(): void
    {
        $reflection = new ReflectionClass(InstallEvents::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPrivate());
    }
}
