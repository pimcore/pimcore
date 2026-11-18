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

namespace Pimcore\Tests\Unit\Bundle\CoreBundle\DependencyInjection;

use Pimcore\Bundle\CoreBundle\DependencyInjection\Configuration;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Config\Definition\Processor;

/**
 * The versions node of documents, objects and assets is the fallback for the system settings when
 * nothing is stored in the settings store yet, so it must always be present with its defaults.
 */
class VersionsConfigurationTest extends TestCase
{
    private function process(array $configs): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $configs);
    }

    /**
     * @dataProvider elementTypeProvider
     */
    public function testVersionsDefaultsArePresentWithoutConfiguration(string $elementType): void
    {
        $versions = $this->process([[]])[$elementType]['versions'] ?? null;

        $this->assertIsArray($versions);
        $this->assertArrayHasKey('days', $versions);
        $this->assertArrayHasKey('steps', $versions);
        $this->assertNull($versions['days']);
        $this->assertNull($versions['steps']);
        $this->assertFalse($versions['disable_events']);
        $this->assertFalse($versions['disable_stack_trace']);
    }

    public static function elementTypeProvider(): array
    {
        return [
            'documents' => ['documents'],
            'objects' => ['objects'],
            'assets' => ['assets'],
        ];
    }
}
