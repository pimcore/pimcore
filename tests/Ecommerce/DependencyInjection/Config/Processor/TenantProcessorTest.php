<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tests\Ecommerce\DependencyInjection\Config\Processor;

use Pimcore\Bundle\EcommerceFrameworkBundle\DependencyInjection\Config\Processor\TenantProcessor;
use Pimcore\Tests\Test\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class TenantProcessorTest extends TestCase
{
    /**
     * @var TenantProcessor
     */
    private $processor;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->processor = new TenantProcessor();
    }

    public function testNothingIsMergedWithoutDefaults()
    {
        $input = [
            'tenant1' => [
                'foo' => 'bar',
                'baz' => [
                    'in',
                    'ga',
                ],
            ],
        ];

        $this->assertEquals($input, $this->processor->mergeTenantConfig($input));
    }

    public function testDefaultValuesAreMergedIntoEveryTenantAndRemoved()
    {
        $input = [
            '_defaults' => [
                'default' => 'value',
            ],
            'default' => [
                'foo' => 'bar',
            ],
            'tenant1' => [
                'baz' => [
                    'in',
                    'ga',
                ],
            ],
        ];

        $expected = [
            'default' => [
                'default' => 'value',
                'foo' => 'bar',
            ],
            'tenant1' => [
                'default' => 'value',
                'baz' => [
                    'in',
                    'ga',
                ],
            ],
        ];

        $this->assertEquals($expected, $this->processor->mergeTenantConfig($input));
    }

    /**
     * Additional defaults can be used for YAML inheritance, but are removed
     * from final config.
     */
    public function testAdditionalDefaultsAreRemovedWithoutMerging()
    {
        $input = [
            '_defaults' => [
                'default' => 'value',
            ],
            '_defaults_foobar' => [
                'xy' => 'z',
            ],
            '_defaultsblahfoo' => [
                'blah' => 'foo',
            ],
            'tenant1' => [
                'foo' => 'bar',
            ],
            'tenant2' => [
                'baz' => [
                    'in',
                    'ga',
                ],
            ],
        ];

        $expected = [
            'tenant1' => [
                'default' => 'value',
                'foo' => 'bar',
            ],
            'tenant2' => [
                'default' => 'value',
                'baz' => [
                    'in',
                    'ga',
                ],
            ],
        ];

        $this->assertEquals($expected, $this->processor->mergeTenantConfig($input));
    }

    public function testAssociativeArraysAreExtended()
    {
        $input = [
            '_defaults' => [
                'values' => [
                    'A' => 'B',
                    'C' => 'D',
                ],
            ],
            'tenant1' => [
                'values' => [
                    'A' => 'B1',
                ],
            ],
            'tenant2' => [
                'values' => [
                    'E' => 'F',
                ],
            ],
        ];

        $expected = [
            'tenant1' => [
                'values' => [
                    'A' => 'B1',
                    'C' => 'D',
                ],
            ],
            'tenant2' => [
                'values' => [
                    'A' => 'B',
                    'C' => 'D',
                    'E' => 'F',
                ],
            ],
        ];

        $this->assertEquals($expected, $this->processor->mergeTenantConfig($input));
    }

    public function testSequentialArraysAreMerged()
    {
        $input = [
            '_defaults' => [
                'values' => ['A', 'B', 'C'],
            ],
            'tenant1' => [
                'values' => ['D', 'E'],
            ],
            'tenant2' => [
                'values' => ['F'],
            ],
        ];

        $expected = [
            'tenant1' => [
                'values' => ['A', 'B', 'C', 'D', 'E'],
            ],
            'tenant2' => [
                'values' => ['A', 'B', 'C', 'F'],
            ],
        ];

        $this->assertEquals($expected, $this->processor->mergeTenantConfig($input));
    }

    public function testDefaultsAreDeepMerged()
    {
        $input = [
            '_defaults' => [
                'level1' => [
                    'level11A' => [
                        'foo',
                        'bar',
                    ],
                    'level11B' => [
                        'x' => 'yz',
                        'y' => 'z',
                    ],
                ],
            ],

            'tenant1' => [
                'level1' => [
                    'level11B' => [
                        'y' => 'AA',
                    ],
                ],
                'level2' => [
                    'foo' => ['bar', 'bazinga'],
                ],
            ],

            'tenant2' => [
                'level1' => [
                    'level11A' => [
                        'bazinga',
                    ],
                    'level11C' => [
                        'my' => 'custom element',
                    ],
                ],
                'level2' => 'ABC',
            ],
        ];

        $expected = [
            'tenant1' => [
                'level1' => [
                    'level11A' => [
                        'foo',
                        'bar',
                    ],
                    'level11B' => [
                        'x' => 'yz',
                        'y' => 'AA',
                    ],
                ],
                'level2' => [
                    'foo' => ['bar', 'bazinga'],
                ],
            ],

            'tenant2' => [
                'level1' => [
                    'level11A' => [
                        'foo',
                        'bar',
                        'bazinga',
                    ],
                    'level11B' => [
                        'x' => 'yz',
                        'y' => 'z',
                    ],
                    'level11C' => [
                        'my' => 'custom element',
                    ],
                ],
                'level2' => 'ABC',
            ],
        ];

        $this->assertEquals($expected, $this->processor->mergeTenantConfig($input));
    }

    public function testExceptionOnMismatchingTypes()
    {
        $input = [
            '_defaults' => [
                'values' => ['A', 'B', 'C'],
            ],
            'tenant1' => [
                'values' => 'D;E',
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->processor->mergeTenantConfig($input);
    }
}
