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

namespace Pimcore\Tests\Model\DataObject\Listing\Concrete;

use Exception;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

final class DaoTest extends ModelTestCase
{
    protected function setUpTestClasses(): void
    {
        $this->tester->setupFieldcollection_Unittestfieldcollection();
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    public function testAddFieldCollectionEscapesFieldnameAgainstSqlInjection(): void
    {
        $object = TestHelper::createEmptyObject();

        $items = new Fieldcollection();
        $items->add(new FieldCollection\Data\Unittestfieldcollection());
        $object->setFieldcollection($items);
        $object->save();

        // If this fieldname were interpolated raw into the JOIN condition's SQL
        // string literal, as it was before this fix (see applyJoins() in
        // Concrete/Dao.php), the embedded double quote would terminate the
        // literal and the injected fragment would reference a table that
        // doesn't exist, causing the query to fail.
        $maliciousFieldname = '" AND 1=(SELECT 1 FROM this_table_does_not_exist_regression_test)-- -';

        $listing = new Unittest\Listing();
        $listing->addFieldCollection('unittestfieldcollection', $maliciousFieldname);

        // Must execute as a normal, safely-escaped query instead of throwing a
        // "table doesn't exist" error from injected SQL.
        $totalCount = $listing->getTotalCount();
        $this->assertGreaterThanOrEqual(1, $totalCount);
    }

    public function testAddFieldCollectionRejectsUnknownType(): void
    {
        // Previously accepted verbatim: getByKey()'s return value was discarded,
        // so any string reached applyJoins() and was concatenated raw into the
        // JOIN's table name (object_collection_<type>_<classId>), which Doctrine
        // never quotes. Only a type matching a real, existing field-collection
        // definition may be used now.
        $maliciousType = 'nonexistent" ON 1=1 LEFT JOIN users-- -';

        $listing = new Unittest\Listing();

        $this->expectException(Exception::class);
        $listing->addFieldCollection($maliciousType, 'irrelevant');
    }

    public function testAddFieldCollectionStillAcceptsARealType(): void
    {
        $listing = new Unittest\Listing();
        $listing->addFieldCollection('unittestfieldcollection', 'fieldinput1');

        $this->assertCount(1, $listing->getFieldCollections());
    }
}
