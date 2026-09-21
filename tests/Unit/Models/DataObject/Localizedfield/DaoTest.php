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

namespace Pimcore\Tests\Unit\Model\DataObject\Localizedfield;

use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Localizedfield\Dao;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * The key columns returned here are passed to Db\Helper::upsert() while saving, instead of
 * looking up the primary key from the database schema for every language. These tests pin
 * the statically known columns per container context.
 *
 * @group model.datatype.localizedfield
 */
class DaoTest extends TestCase
{
    private function createDao(?array $context): Dao
    {
        $localizedfield = new Localizedfield();
        $localizedfield->setContext($context);

        $dao = new Dao();
        $dao->setModel($localizedfield);

        return $dao;
    }

    public function testTableKeyColumnsInClassContext(): void
    {
        $dao = $this->createDao(null);

        $this->assertSame(['ooo_id', 'language'], $dao->getTableKeyColumns());
    }

    public function testTableKeyColumnsInFieldcollectionContext(): void
    {
        $dao = $this->createDao([
            'containerType' => 'fieldcollection',
            'containerKey' => 'unittestfieldcollection',
            'fieldname' => 'fieldcollection',
        ]);

        $this->assertSame(['ooo_id', 'language', 'index', 'fieldname'], $dao->getTableKeyColumns());
    }

    public function testTableKeyColumnsInObjectbrickContext(): void
    {
        $dao = $this->createDao([
            'containerType' => 'objectbrick',
            'containerKey' => 'unittestBrick',
            'fieldname' => 'mybricks',
        ]);

        $this->assertSame(['ooo_id', 'language', 'index', 'fieldname'], $dao->getTableKeyColumns());
    }

    public function testTableKeyColumnsInBlockContext(): void
    {
        // block contexts use the default localized data table, see Dao::getTableName()
        $dao = $this->createDao([
            'containerType' => 'block',
            'containerKey' => 'testblock',
            'fieldname' => 'testblock',
        ]);

        $this->assertSame(['ooo_id', 'language'], $dao->getTableKeyColumns());
    }

    public function testQueryTableKeyColumns(): void
    {
        $dao = $this->createDao(null);

        $this->assertSame(['ooo_id', 'language'], $dao->getQueryTableKeyColumns());
    }
}
