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

class LinkSanitizerConfigurationTest extends TestCase
{
    private function process(array $configs): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $configs);
    }

    public function testLinkSanitizerStrictDefaultsToFalse(): void
    {
        $config = $this->process([[]]);

        $this->assertFalse($config['documents']['editables']['link_sanitizer']['strict']);
    }

    public function testLinkSanitizerStrictAcceptsTrue(): void
    {
        $config = $this->process([[
            'documents' => [
                'editables' => [
                    'link_sanitizer' => [
                        'strict' => true,
                    ],
                ],
            ],
        ]]);

        $this->assertTrue($config['documents']['editables']['link_sanitizer']['strict']);
    }
}
