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

use Pimcore\Bundle\InstallBundle\Event\InstallerStepEvent;
use Pimcore\Bundle\InstallBundle\Profile\InstallStep;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionClass;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 */
final class InstallerStepEventTest extends TestCase
{
    public function testClassIsFinal(): void
    {
        $reflection = new ReflectionClass(InstallerStepEvent::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function testExtendsSymfonyEvent(): void
    {
        $event = new InstallerStepEvent(
            InstallStep::WriteEnv,
            'Writing .env.local...',
            1,
            3,
        );

        $this->assertInstanceOf(Event::class, $event);
    }

    public function testGetStepReturnsInstallStepEnum(): void
    {
        $event = new InstallerStepEvent(
            InstallStep::CollectAndValidate,
            'Collecting configuration...',
            1,
            5,
        );

        $this->assertSame(InstallStep::CollectAndValidate, $event->getStep());
        $this->assertInstanceOf(InstallStep::class, $event->getStep());
    }

    public function testGetMessageReturnsMessage(): void
    {
        $event = new InstallerStepEvent(
            InstallStep::WriteDoctrineConfig,
            'Writing Doctrine config...',
            2,
            10,
        );

        $this->assertSame('Writing Doctrine config...', $event->getMessage());
    }

    public function testGetStepNumberReturnsStepNumber(): void
    {
        $event = new InstallerStepEvent(
            InstallStep::BootKernel,
            'Booting kernel...',
            3,
            10,
        );

        $this->assertSame(3, $event->getStepNumber());
    }

    public function testGetTotalStepsReturnsTotalSteps(): void
    {
        $event = new InstallerStepEvent(
            InstallStep::Finalize,
            'Finalizing...',
            10,
            10,
        );

        $this->assertSame(10, $event->getTotalSteps());
    }

    public function testConstructorSetsAllProperties(): void
    {
        $step = InstallStep::SetupDatabase;
        $message = 'Setting up database...';
        $stepNumber = 5;
        $totalSteps = 14;

        $event = new InstallerStepEvent($step, $message, $stepNumber, $totalSteps);

        $this->assertSame($step, $event->getStep());
        $this->assertSame($message, $event->getMessage());
        $this->assertSame($stepNumber, $event->getStepNumber());
        $this->assertSame($totalSteps, $event->getTotalSteps());
    }
}
