<?php

namespace Pimcore\Tests\Model\Asset;

use Pimcore\Db;
use Pimcore\Model\Asset;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;

/**
 * Class ListingTest
 *
 * @package Pimcore\Tests\Model\Document
 * @group model.asset.asset
 */
class ListingTest extends ModelTestCase
{
    public function testListCount()
    {
        $db = Db::get();

        $count = $db->fetchOne('SELECT count(*) from assets');
        $this->assertEquals(1, $count, 'expected 1 asset');

        for ($i = 0; $i < 3; $i++) {
            TestHelper::createImageAsset();
        }

        TestHelper::createDocumentAsset();
        TestHelper::createVideoAsset();

        $count = $db->fetchOne('SELECT count(*) from assets');
        $this->assertEquals(6, $count, 'expected 6 assets');

        $list = new Asset\Listing();
        $totalCount = $list->getTotalCount();
        $this->assertEquals(6, $totalCount, 'expected 6 assets');

        $list = new Asset\Listing();
        $list->setLimit(3);
        $list->setOffset(1);
        $count = $list->getCount();
        $this->assertEquals(3, $count, 'expected 3 assets');

        $list = new Asset\Listing();
        $list->setLimit(10);
        $list->setOffset(1);
        $count = $list->getCount();
        $this->assertEquals(5, $count, 'expected 5 assets');

        $list = new Asset\Listing();
        $list->setLimit(10);
        $list->setOffset(1);
        $list->load();                      // with load
        $count = $list->getCount();
        $this->assertEquals(5, $count, 'expected 5 assets');
        $totalCount = $list->getTotalCount();
        $this->assertEquals(6, $totalCount, 'expected 6 assets');
    }
}
