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

namespace Pimcore\Tests\Unit\Tool;

use Pimcore\Bundle\CoreBundle\DependencyInjection\Config\Processor\PlaceholderProcessor;
use Pimcore\Tests\Test\TestCase;

class PlaceholderProcessorTest extends TestCase
{
    /**
     * @var PlaceholderProcessor
     */
    private $processor;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->processor = new PlaceholderProcessor();
    }

    public function testPlaceholdersAreMergedIntoArrayValues()
    {
        $input = [
            'locale' => '%locale%',
        ];

        $expected = [
            'locale' => 'en_US',
        ];

        $placeholders = [
            '%locale%' => 'en_US',
        ];

        $this->assertEquals($expected, $this->processor->mergePlaceholders($input, $placeholders));
    }

    public function testMultiplePlaceholdersAreMergedIntoArrayValues()
    {
        $input = [
            'locale1' => '%locale1%',
            'locale2' => '%locale2%',
        ];

        $expected = [
            'locale1' => 'de_AT',
            'locale2' => 'en_US',
        ];

        $placeholders = [
            '%locale1%' => 'de_AT',
            '%locale2%' => 'en_US',
        ];

        $this->assertEquals($expected, $this->processor->mergePlaceholders($input, $placeholders));
    }

    public function testPlaceholdersAreMergedIntoCompositeArrayValues()
    {
        $input = [
            'locale' => 'my locale is %locale%',
        ];

        $expected = [
            'locale' => 'my locale is en_US',
        ];

        $placeholders = [
            '%locale%' => 'en_US',
        ];

        $this->assertEquals($expected, $this->processor->mergePlaceholders($input, $placeholders));
    }

    public function testPlaceholdersAreMergedIntoDeepArrayValues()
    {
        $input = [
            'locales' => [
                'locale' => '%locale2%',
                'locales' => [
                    '%locale1%',
                    '%locale2%',
                ],
            ],
        ];

        $expected = [
            'locales' => [
                'locale' => 'en_US',
                'locales' => [
                    'de_AT',
                    'en_US',
                ],
            ],
        ];

        $placeholders = [
            '%locale1%' => 'de_AT',
            '%locale2%' => 'en_US',
        ];

        $this->assertEquals($expected, $this->processor->mergePlaceholders($input, $placeholders));
    }

    public function testPlaceholdersAreMergedIntoArrayKeys()
    {
        $input = [
            'locales' => [
                'locale' => '%locale1%',
                'locales' => [
                    '%locale1%',
                    '%locale2%',
                ],
                'locale_%locale1%' => '%locale2%',
            ],
            'mapping' => [
                '%locale1%' => '%locale2%',
            ],
        ];

        $expected = [
            'locales' => [
                'locale' => 'de_AT',
                'locales' => [
                    'de_AT',
                    'en_US',
                ],
                'locale_de_AT' => 'en_US',
            ],
            'mapping' => [
                'de_AT' => 'en_US',
            ],
        ];

        $placeholders = [
            '%locale1%' => 'de_AT',
            '%locale2%' => 'en_US',
        ];

        $this->assertEquals($expected, $this->processor->mergePlaceholders($input, $placeholders));
    }
}
