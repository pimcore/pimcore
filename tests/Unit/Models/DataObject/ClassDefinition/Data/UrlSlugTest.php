<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\ClassDefinition\Data\UrlSlug;
use Pimcore\Model\DataObject\Data\UrlSlug as UrlSlugValue;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @group unit.model.datatype.urlslug
 */
class UrlSlugTest extends TestCase
{
    /**
     * Regression test: getDataForEditmode() emits an associative array
     * (['slug' => ..., 'siteId' => ..., 'domain' => ...]), but getDataFromEditmode()
     * used to read it back positionally ($item[0]/$item[1]/$item[2]), silently producing
     * a UrlSlug with a null slug and siteId 0 for any associative payload
     * (e.g. from Pimcore Studio's dependent-select-options flow).
     */
    public function testGetDataFromEditmodeAcceptsAssociativeArray(): void
    {
        $data = [
            ['siteId' => 3, 'slug' => '/foo', 'previousSlug' => '/old-foo'],
        ];

        $result = (new UrlSlug())->getDataFromEditmode($data);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(UrlSlugValue::class, $result[0]);
        $this->assertSame('/foo', $result[0]->getSlug());
        $this->assertSame(3, $result[0]->getSiteId());
        $this->assertSame('/old-foo', $result[0]->getPreviousSlug());
    }

    /**
     * Legacy Admin UI Classic still sends positional tuples ([siteId, slug, previousSlug]);
     * this shape must keep working unchanged.
     */
    public function testGetDataFromEditmodeAcceptsPositionalArray(): void
    {
        $data = [
            [3, '/foo', '/old-foo'],
        ];

        $result = (new UrlSlug())->getDataFromEditmode($data);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(UrlSlugValue::class, $result[0]);
        $this->assertSame('/foo', $result[0]->getSlug());
        $this->assertSame(3, $result[0]->getSiteId());
        $this->assertSame('/old-foo', $result[0]->getPreviousSlug());
    }

    public function testGetDataFromEditmodeReturnsEmptyArrayForNonArrayData(): void
    {
        $result = (new UrlSlug())->getDataFromEditmode(null);

        $this->assertSame([], $result);
    }
}
