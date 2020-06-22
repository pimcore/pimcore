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

namespace Pimcore\Tests\Unit\Process;

use Pimcore\Process\PartsBuilder;
use Pimcore\Tests\Test\TestCase;

/**
 * @covers PartsBuilder
 */
class PartsBuilderTest extends TestCase
{
    private $inputArguments = [
        'foo',
        'bar',
        'baz:inga',
    ];

    private $inputOptions = [
        'foo' => true,
        'bar' => false, // omitted because false
        'baz' => 'inga',
        'test' => null,  // omitted because null
        'o' => true,
        'x' => 'yz',
    ];

    private $expected = [
        'foo',
        'bar',
        'baz:inga',
        '--foo',
        '--baz=inga',
        '-o',
        '-x=yz',
    ];

    public function testSetPartsInConstructor()
    {
        $builder = new PartsBuilder($this->inputArguments, $this->inputOptions);

        $this->assertEquals($this->expected, $builder->getParts());
    }

    public function testSetPartsAsArray()
    {
        $builder = new PartsBuilder();
        $builder->addArguments($this->inputArguments);
        $builder->addOptions($this->inputOptions);

        $this->assertEquals($this->expected, $builder->getParts());
    }

    public function testSetPartsSingle()
    {
        $builder = new PartsBuilder();

        foreach ($this->inputArguments as $argument) {
            $builder->addArgument($argument);
        }

        foreach ($this->inputOptions as $option => $value) {
            $builder->addOption($option, $value);
        }

        $this->assertEquals($this->expected, $builder->getParts());
    }

    public function testMergeParts()
    {
        $builder = new PartsBuilder([
            'foo',
            'bar',
        ], [
            'foo' => true,
            'bar' => false,
            'baz' => 'inga',
        ]);

        $builder->addArgument('baz:inga');
        $builder->addOption('test', null);
        $builder->addOption('o', true);
        $builder->addOption('x', 'yz');

        $this->assertEquals($this->expected, $builder->getParts());
    }

    public function testOptionIsSetMultipleTimes()
    {
        $builder = new PartsBuilder([
            'foo',
            'bar',
        ], [
            'foo' => 'bar',
            'bar' => true,
            'x' => 'yz',
            'y' => 'z',
        ]);

        $builder->addOption('foo', 'baz');
        $builder->addOption('x', 'ab');
        $builder->addOption('y', 'b');
        $builder->addOption('bar', false);
        $builder->addOption('baz', true);

        $this->assertEquals([
            'foo',
            'bar',
            '--foo=bar',
            '--bar',
            '-x=yz',
            '-y=z',
            '--foo=baz',
            '-x=ab',
            '-y=b',
            '--baz',
        ], $builder->getParts());
    }
}
