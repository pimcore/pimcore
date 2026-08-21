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

namespace Pimcore\Tests\Model\Inheritance;

use Pimcore;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Inheritance;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Fields which do not support inheritance (e.g. calculatedValue) must never
 * have the parent's stored query-table value copied onto the child row,
 * even when the child's own computed value is empty.
 *
 * @see https://github.com/pimcore/platform-version/issues/275
 */
class CalculatedValueTest extends ModelTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
        Pimcore::setAdminMode();
    }

    public function testEmptyChildValueIsNotOverwrittenByParentData(): void
    {
        $db = Db::get();

        // the test calculator returns this runtime-cache value for every calculated field
        RuntimeCache::set('modeltest.testCalculatedValue.value', 'parentcalc');

        $one = new Inheritance();
        $one->setKey('calc-one');
        $one->setParentId(1);
        $one->setPublished(true);
        $one->setNormalInput('parenttext');

        $oneBrick = new DataObject\Objectbrick\Data\UnittestBrick($one);
        $oneBrick->setBrickInput('parentbrick');
        $one->getMybricks()->setUnittestBrick($oneBrick);

        $one->save();

        $classId = $one->getClassId();

        $row = $db->fetchAssociative('SELECT * FROM object_query_' . $classId . ' WHERE oo_id = ?', [$one->getId()]);
        $this->assertEquals('parentcalc', $row['calculatedinherited']);

        $localizedRow = $db->fetchAssociative('SELECT * FROM object_localized_query_' . $classId . '_en WHERE ooo_id = ?', [$one->getId()]);
        $this->assertEquals('parentcalc', $localizedRow['lcalculatedinherited']);

        $brickRow = $db->fetchAssociative('SELECT * FROM object_brick_query_unittestBrick_' . $classId . ' WHERE id = ?', [$one->getId()]);
        $this->assertEquals('parentcalc', $brickRow['brickcalculated']);

        // for the child the calculator computes an empty value; the parent's
        // stored value must NOT be copied in, calculatedValue does not support inheritance
        RuntimeCache::set('modeltest.testCalculatedValue.value', '');

        $two = new Inheritance();
        $two->setKey('calc-two');
        $two->setParentId($one->getId());
        $two->setPublished(true);

        $twoBrick = new DataObject\Objectbrick\Data\UnittestBrick($two);
        $twoBrick->setBrickInput2('childbrick');
        $two->getMybricks()->setUnittestBrick($twoBrick);

        $two->save();

        $row = $db->fetchAssociative('SELECT * FROM object_query_' . $classId . ' WHERE oo_id = ?', [$two->getId()]);
        $this->assertSame('', (string) $row['calculatedinherited'], 'child object_query_ row must keep the empty computed value');

        $localizedRow = $db->fetchAssociative('SELECT * FROM object_localized_query_' . $classId . '_en WHERE ooo_id = ?', [$two->getId()]);
        $this->assertSame('', (string) $localizedRow['lcalculatedinherited'], 'child localized query row must keep the empty computed value');

        $brickRow = $db->fetchAssociative('SELECT * FROM object_brick_query_unittestBrick_' . $classId . ' WHERE id = ?', [$two->getId()]);
        $this->assertSame('', (string) $brickRow['brickcalculated'], 'child brick query row must keep the empty computed value');

        // regular fields still inherit
        $this->assertEquals('parenttext', $row['normalinput']);
    }
}
