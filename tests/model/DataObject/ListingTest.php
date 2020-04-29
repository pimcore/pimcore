<?php

namespace Pimcore\Tests\Model\DataObject;

use Pimcore\Db;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Tests\Helper\DataType\TestDataHelper;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;

/**
 * Class ListingTest
 *
 * @package Pimcore\Tests\Model\DataObject
 * @group model.dataobject.listing
 */
class ListingTest extends ModelTestCase
{
    /**
     * @var TestDataHelper
     */
    protected $testDataHelper;

    /**
     * @param TestDataHelper $testData
     */
    public function _inject(TestDataHelper $testData)
    {
        $this->testDataHelper = $testData;
    }

    public function setUp()
    {
        parent::setUp();
        TestHelper::cleanUp();

        $this->prepareData();
    }

    public function tearDown()
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    public function prepareData()
    {
        $seeds = [10, 11, 42, 53, 65, 78, 85];

        foreach ($seeds as $seed) {
            $object = TestHelper::createEmptyObject('listing-test-' . $seed . '_', true, true);

            $object->setInput('content' . $seed);
            $object->setNumber(99 + $seed);

            $object->save();
        }
    }

    public function testSimpleCondition()
    {
        $listing = new Unittest\Listing();
        $listing->setCondition('input = "content10"');

        $this->assertEquals(1, $listing->getTotalCount(), 'Simple Condition Result Published Objects');

        $listing = new Unittest\Listing();
        $listing->setCondition('input = "content10" AND number = 109');

        $this->assertEquals(1, $listing->getTotalCount(), 'Simple Condition Result Published Objects');
    }

    public function testSimpleParamCondition()
    {
        $listing = new Unittest\Listing();
        $listing->setCondition('input = ?', ['content10']);

        $this->assertEquals(1, $listing->getTotalCount(), 'Simple ParamCondition Result Published Objects');

        $listing = new Unittest\Listing();
        $listing->setCondition('input = ? AND number = ?', ['content10', 109]);

        $this->assertEquals(1, $listing->getTotalCount(), 'Simple ParamCondition Result Published Objects');

        $listing = new Unittest\Listing();
        $listing->setCondition('input = :param1', ['param1' => 'content10']);

        $this->assertEquals(1, $listing->getTotalCount(), 'Simple ParamCondition Result Published Objects');

        $listing = new Unittest\Listing();
        $listing->setCondition('input = :param1 AND number = :param2', ['param2' => 109, 'param1' => 'content10']);

        $this->assertEquals(1, $listing->getTotalCount(), 'Simple ParamCondition Result Published Objects');
    }

    public function testArrayCondition()
    {
        $listing = new Unittest\Listing();
        $listing->setCondition('input IN (?)', [['content10', 'contentXX']]);

        $this->assertEquals(1, $listing->getTotalCount(), 'Simple Array Condition Published Objects');

        $listing = new Unittest\Listing();
        $listing->setCondition('input IN (?) AND input = ?', [['content10', 'contentXX'], 'content10']);

        $this->assertEquals(1, $listing->getTotalCount(), 'Combined Array Condition Published Objects');

        $listing = new Unittest\Listing();
        $listing->setCondition('input IN (?) AND input = ? AND number IN (?)', [['content10', 'contentXX'], 'content10', [109, 999]]);

        $this->assertEquals(1, $listing->getTotalCount(), 'Three Combined Array Condition Published Objects');
    }

    /**
     * Verifies that cached list is flushed on changing the condition and filters
     *
     */
    public function testCacheObjects()
    {
        $listing = new Unittest\Listing();
        $listing->setCondition('input IN (?)', [['content10', 'content11', 'content42']]);
        $listing->load();

        $this->assertEquals(3, $listing->getCount(), 'Expected 3 objects in the list');

        $listing->setCondition('input IN (?)', [['content10', 'content11']]);
        $this->assertEquals(2, $listing->getCount(), 'Expected 2 objects in the list');

        $listing->setLimit(1);
        $this->assertEquals(1, $listing->getCount(), 'Expected 1 object in the list');
    }

    public function testListCount()
    {
        $db = Db::get();

        // prepare data creates 7 objects + 1 root => 8
        $count = $db->fetchOne('SELECT count(*) from objects');
        $this->assertEquals(8, $count, 'expected 8 objects');

        $list = new DataObject\Listing();
        $totalCount = $list->getTotalCount();
        $this->assertEquals(8, $totalCount, 'expected 8 objects');

        $list = new DataObject\Listing();
        $list->setLimit(3);
        $list->setOffset(1);
        $count = $list->getCount();
        $this->assertEquals(3, $count, 'expected 3 objects');

        $list = new DataObject\Listing();
        $list->setLimit(10);
        $list->setOffset(1);
        $count = $list->getCount();
        $this->assertEquals(7, $count, 'expected 7 objects');

        $list = new DataObject\Listing();
        $list->setLimit(10);
        $list->setOffset(1);
        $list->load();                      // with load
        $count = $list->getCount();
        $this->assertEquals(7, $count, 'expected 7 objects');
        $totalCount = $list->getTotalCount();
        $this->assertEquals(8, $totalCount, 'expected 8 objects');
    }
}
