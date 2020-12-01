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

namespace Pimcore\Tests\Unit\Cache;

use Pimcore\Cache\Runtime;
use Pimcore\Tests\Test\TestCase;

class RuntimeTest extends TestCase
{
    public function blockedIndexProvider()
    {
        return [
            ['pimcore_tag_block_current'],
            ['pimcore_tag_block_numeration'],
        ];
    }

    /**
     * @dataProvider blockedIndexProvider
     *
     * @param string $index
     */
    public function testThrowsExceptionOnBlockedIndexConstruct(string $index)
    {
        $this->expectException(\InvalidArgumentException::class);
        $data = [];
        $data[$index] = 'foo';

        new Runtime($data);
    }

    /**
     * @dataProvider blockedIndexProvider
     *
     * @param string $index
     */
    public function testThrowsExceptionOnBlockedIndexMagicSet(string $index)
    {
        $this->expectException(\InvalidArgumentException::class);
        $cache = new Runtime();
        $cache->$index = 'foo';
    }

    /**
     * @dataProvider blockedIndexProvider
     *
     * @param string $index
     */
    public function testThrowsExceptionOnBlockedIndexOffsetSet(string $index)
    {
        $this->expectException(\InvalidArgumentException::class);
        $cache = new Runtime();
        $cache->offsetSet($index, 'foo');
    }
}
