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

namespace Pimcore\Tests\Unit\CustomReportsBundle\DependencyInjection;

use Pimcore\Bundle\CustomReportsBundle\DependencyInjection\PimcoreCustomReportsExtension;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Verifies that a disabled adapter is never registered into the shared
 * 'pimcore.custom_report.adapter.factories' ServiceLocator, since that's the mechanism every
 * consumer (the classic admin controller, the Studio backend bundle's AdapterService, or any
 * third-party code) relies on to resolve an adapter by type.
 */
final class PimcoreCustomReportsExtensionTest extends TestCase
{
    /**
     * @return string[]
     */
    private function registeredAdapterKeys(array $enabledAdapters): array
    {
        $container = new ContainerBuilder();

        (new PimcoreCustomReportsExtension())->load([[
            'adapters' => [
                'sql' => 'pimcore.custom_report.adapter.factory.sql',
                'other' => 'some.other.factory',
            ],
            'enabled_adapters' => $enabledAdapters,
        ]], $container);

        $arguments = $container->getDefinition('pimcore.custom_report.adapter.factories')->getArgument(0);

        return array_keys($arguments);
    }

    public function testAdaptersNotListedInEnabledAdaptersDefaultToEnabled(): void
    {
        $this->assertSame(['sql', 'other'], $this->registeredAdapterKeys([]));
    }

    public function testAdapterExplicitlyDisabledIsExcludedFromTheServiceLocator(): void
    {
        $this->assertSame(['other'], $this->registeredAdapterKeys(['sql' => false]));
    }

    public function testAdapterExplicitlyEnabledStaysRegistered(): void
    {
        $this->assertSame(['sql', 'other'], $this->registeredAdapterKeys(['sql' => true]));
    }
}
