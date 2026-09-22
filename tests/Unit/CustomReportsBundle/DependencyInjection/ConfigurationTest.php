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

use Pimcore\Bundle\CustomReportsBundle\DependencyInjection\Configuration;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    private function process(array $configs): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $configs);
    }

    public function testEnabledAdaptersAcceptsABooleanMap(): void
    {
        $config = $this->process([[
            'enabled_adapters' => [
                'sql' => false,
                'myAdapter' => true,
            ],
        ]]);

        $this->assertSame(['sql' => false, 'myAdapter' => true], $config['enabled_adapters']);
    }

    public function testEnabledAdaptersDefaultsToAnEmptyMapWhenOmitted(): void
    {
        $config = $this->process([[]]);

        $this->assertSame([], $config['enabled_adapters']);
    }

    public function testEnabledAdaptersRejectsNonBooleanValues(): void
    {
        $this->expectException(InvalidTypeException::class);

        $this->process([[
            'enabled_adapters' => [
                'sql' => 'yes',
            ],
        ]]);
    }
}
