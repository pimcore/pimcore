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

namespace Pimcore\Tests\Unit\Composer\Config\Normalizer;

use Pimcore\Composer\Config\Normalizer\Php7PlatformNormalizer;
use Pimcore\Tests\Test\TestCase;

/**
 * @covers Php7PlatformNormalizer
 */
class Php7PlatformNormalizerTest extends TestCase
{
    /**
     * @var Php7PlatformNormalizer
     */
    private $normalizer;

    private $fixture = [
        'name'     => 'pimcore/pimcore',
        'type'     => 'project',
        'homepage' => 'http://www.pimcore.com',
        'license'  => 'GPL-3.0',
        'config'   => [
            'optimize-autoloader' => true,
            'sort-packages'       => true
        ],
        'require'  => [
            'php'                  => '>=7.0',
            'pimcore/core-version' => 'v5.1.0',
            'symfony/symfony'      => '3.4.*',
        ],
    ];

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->normalizer = new Php7PlatformNormalizer();
    }

    public function testNoChangesWithoutPlatform()
    {
        $this->assertEquals($this->fixture, $this->normalizer->normalize($this->fixture));
    }

    public function testEntryIsRemoved()
    {
        $input = $this->fixture;

        $input['config']['platform'] = [
            'php' => '7.0'
        ];

        $this->assertEquals(
            $this->fixture,
            $this->normalizer->normalize($input)
        );
    }

    public function testParentNodesAreRemovedIfEmpty()
    {
        $input = $this->fixture;

        unset($input['config']['optimize-autoloader']);
        unset($input['config']['sort-packages']);

        $this->assertEmpty($input['config']);

        $expected = $input;
        unset($expected['config']);

        $input['config']['platform'] = [
            'php' => '7.0'
        ];

        $this->assertEquals(
            $expected,
            $this->normalizer->normalize($input)
        );
    }

    public function testEntryIsntRemovedIfVersionIsNot70()
    {
        $input = $this->fixture;

        $input['config']['platform'] = [
            'php' => '7.1'
        ];

        $this->assertEquals(
            $input,
            $this->normalizer->normalize($input)
        );
    }
}
