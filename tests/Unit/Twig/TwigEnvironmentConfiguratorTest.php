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

namespace Pimcore\Tests\Unit\Twig;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Pimcore\Model\Document\Editable;
use Pimcore\Twig\TwigEnvironmentConfigurator;
use stdClass;
use Symfony\Bundle\TwigBundle\DependencyInjection\Configurator\EnvironmentConfigurator;
use Twig\Environment;
use Twig\Extension\CoreExtension;
use Twig\Loader\ArrayLoader;
use Twig\Runtime\EscaperRuntime;

/**
 * Regression test for the decoration of `twig.configurator.environment`: other bundles decorate the same
 * service with decorators that require Symfony's EnvironmentConfigurator as inner service without extending
 * it themselves (e.g. symfony/ux-twig-component), so Pimcore's configurator has to work on both ends of
 * such a chain.
 */
class TwigEnvironmentConfiguratorTest extends TestCase
{
    public function testCanBeDecoratedByConfiguratorsRequiringSymfonysEnvironmentConfigurator(): void
    {
        $configurator = new TwigEnvironmentConfigurator($this->createSymfonyConfigurator());

        $this->assertInstanceOf(EnvironmentConfigurator::class, $configurator);
    }

    public function testAppliesSymfonysEnvironmentConfiguratorAndMarksEditablesAsSafe(): void
    {
        $environment = new Environment(new ArrayLoader());

        (new TwigEnvironmentConfigurator($this->createSymfonyConfigurator()))->configure($environment);

        $this->assertSame(['d.m.Y', '%d days'], $environment->getExtension(CoreExtension::class)->getDateFormat());
        $this->assertSame(['html'], $environment->getRuntime(EscaperRuntime::class)->safeClasses[Editable::class] ?? null);
    }

    public function testDecoratesConfiguratorsNotExtendingSymfonysEnvironmentConfigurator(): void
    {
        $decorated = new class() {
            public ?Environment $configured = null;

            public function configure(Environment $environment): void
            {
                $this->configured = $environment;
            }
        };
        $environment = new Environment(new ArrayLoader());

        (new TwigEnvironmentConfigurator($decorated))->configure($environment);

        $this->assertSame($environment, $decorated->configured);
        $this->assertSame(['html'], $environment->getRuntime(EscaperRuntime::class)->safeClasses[Editable::class] ?? null);
    }

    public function testRejectsDecoratedServicesWithoutConfigureMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('stdClass given');

        new TwigEnvironmentConfigurator(new stdClass());
    }

    private function createSymfonyConfigurator(): EnvironmentConfigurator
    {
        return new EnvironmentConfigurator('d.m.Y', '%d days', null, 2, ',', '.');
    }
}
