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

namespace Pimcore\Tests\Unit\FeatureToggles;

use Pimcore\FeatureToggles\FeatureContext;
use Pimcore\Tests\Test\TestCase;

/**
 * @covers \Pimcore\FeatureToggles\FeatureContext
 */
class FeatureContextTest extends TestCase
{
    /**
     * @var FeatureContext
     */
    private $context;

    /**
     * @var array
     */
    private $data = [
        'A' => 1,
        'B' => 2,
        'C' => [
            'D' => 3,
            'E' => 4
        ]
    ];

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->context = new FeatureContext($this->data);
    }

    public function testAll()
    {
        $this->assertEquals($this->data, $this->context->all());
    }

    public function testKeys()
    {
        $this->assertEquals(['A', 'B', 'C'], $this->context->keys());
    }

    public function testGet()
    {
        $this->assertSame(1, $this->context->get('A'));
        $this->assertSame(2, $this->context->get('B'));
        $this->assertEquals(['D' => 3, 'E' => 4], $this->context->get('C'));
    }

    public function testGetDefault()
    {
        $this->assertNull($this->context->get('F'));
        $this->assertSame(5, $this->context->get('F', 5));
    }

    public function testSet()
    {
        $this->assertSame(2, $this->context->get('B'));

        $this->context->set('B', 7);

        $this->assertSame(7, $this->context->get('B'));
    }

    public function testHas()
    {
        $this->assertTrue($this->context->has('A'));
        $this->assertFalse($this->context->has('F'));

        $this->context->set('F', 5);

        $this->assertTrue($this->context->has('A'));
        $this->assertTrue($this->context->has('F'));
    }

    public function testRemove()
    {
        $this->assertTrue($this->context->has('A'));
        $this->assertEquals(1, $this->context->get('A'));

        $this->context->remove('A');

        $this->assertFalse($this->context->has('A'));
        $this->assertNull($this->context->get('A'));

        // can remove again
        $this->context->remove('A');

        $this->assertFalse($this->context->has('A'));
        $this->assertNull($this->context->get('A'));
    }

    public function testCount()
    {
        $this->assertEquals(3, $this->context->count());
        $this->assertEquals(3, count($this->context));
    }

    public function testIterator()
    {
        foreach ($this->context as $key => $value) {
            $this->assertTrue(array_key_exists($key, $this->data));
            $this->assertEquals($this->data[$key], $value);
        }
    }
}
