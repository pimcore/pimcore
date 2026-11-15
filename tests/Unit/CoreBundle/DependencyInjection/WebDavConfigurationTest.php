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

namespace Pimcore\Tests\Unit\CoreBundle\DependencyInjection;

use Pimcore\Bundle\CoreBundle\DependencyInjection\Configuration;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Config\Definition\Processor;

final class WebDavConfigurationTest extends TestCase
{
    public function testBrowserPluginIsDisabledByDefault(): void
    {
        $config = $this->process([[]]);

        $this->assertFalse($config['assets']['webdav']['browser_plugin']);
    }

    public function testBrowserPluginCanBeEnabled(): void
    {
        $config = $this->process([[
            'assets' => [
                'webdav' => [
                    'browser_plugin' => true,
                ],
            ],
        ]]);

        $this->assertTrue($config['assets']['webdav']['browser_plugin']);
    }

    public function testBrowserPluginRejectsNonBooleanValues(): void
    {
        $this->expectException(InvalidTypeException::class);

        $this->process([[
            'assets' => [
                'webdav' => [
                    'browser_plugin' => 'yes',
                ],
            ],
        ]]);
    }

    private function process(array $configs): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $configs);
    }
}
