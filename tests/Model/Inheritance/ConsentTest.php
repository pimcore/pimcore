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
use Pimcore\Db;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Data\Consent;
use Pimcore\Model\DataObject\Inheritance;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Consent is the other query-persisted field type besides calculatedValue which
 * does not support inheritance: a child must never report the parent's consent
 * in its query tables — neither on child save (parent-data fallback) nor when
 * the parent's consent changes or its brick is deleted (inheritance tracking).
 *
 * @see https://github.com/pimcore/platform-version/issues/275
 */
class ConsentTest extends ModelTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
        Pimcore::setAdminMode();
    }

    public function testEmptyChildConsentIsNotOverwrittenByParentData(): void
    {
        $one = $this->createParent(true);
        $two = $this->createChild($one);

        $this->assertConsentQueryValues($one, 1, 'parent keeps its own consent');
        $this->assertConsentQueryValues($two, 0, 'child without consent must not get the parent value copied');

        // regular fields still inherit
        $row = Db::get()->fetchAssociative('SELECT * FROM object_query_' . $one->getClassId() . ' WHERE oo_id = ?', [$two->getId()]);
        $this->assertEquals('parenttext', $row['normalinput']);
    }

    public function testParentConsentChangeIsNotPropagatedToChildren(): void
    {
        $one = $this->createParent(false);
        $two = $this->createChild($one);

        $this->assertConsentQueryValues($two, 0, 'child starts without consent');

        // the parent gives consent afterwards — the inheritance tracking must
        // skip consent fields and leave the child rows alone
        $one->setConsentinherited(new Consent(true));
        $one->setLconsentinherited(new Consent(true), 'en');
        $one->getMybricks()->getUnittestBrick()->setBrickconsent(new Consent(true));
        $one->save();

        $this->assertConsentQueryValues($one, 1, 'parent stores its changed consent');
        $this->assertConsentQueryValues($two, 0, 'parent consent change must not be pushed into child rows');
    }

    public function testParentBrickDeleteWithConsentKeepsChildRows(): void
    {
        $db = Db::get();

        $one = $this->createParent(true);
        $two = $this->createChild($one);

        $classId = $one->getClassId();

        // deleting the parent's brick must skip consent in the deletion tracking
        $one->getMybricks()->getUnittestBrick()->setDoDelete(true);
        $one->save();

        $parentBrickRow = $db->fetchAssociative('SELECT * FROM object_brick_query_unittestBrick_' . $classId . ' WHERE id = ?', [$one->getId()]);
        $this->assertFalse($parentBrickRow, 'parent brick query row is removed');

        $childBrickRow = $db->fetchAssociative('SELECT * FROM object_brick_query_unittestBrick_' . $classId . ' WHERE id = ?', [$two->getId()]);
        $this->assertIsArray($childBrickRow, 'child keeps its own brick query row');
        $this->assertSame(0, (int) $childBrickRow['brickconsent'], 'child brick consent stays untouched');
        $this->assertEquals('childbrick', $childBrickRow['brickinput2']);
        // the inheritable brickinput was inherited from the parent and is cleared by the delete propagation
        $this->assertNull($childBrickRow['brickinput']);
    }

    public function testBrickRowCreatedForChildWithoutBrickDoesNotCopyConsent(): void
    {
        $db = Db::get();

        // neither object has a brick yet, so the child has no brick query row at all
        $one = new Inheritance();
        $one->setKey('consent-one');
        $one->setParentId(1);
        $one->setPublished(true);
        $one->setNormalInput('parenttext');
        $one->save();

        $two = $this->createChild($one, false);

        $classId = $one->getClassId();
        $this->assertFalse(
            $db->fetchAssociative('SELECT * FROM object_brick_query_unittestBrick_' . $classId . ' WHERE id = ?', [$two->getId()]),
            'child has no brick query row yet'
        );

        // adding the brick on the parent creates the missing child rows by copying the
        // parent row — consent must not be carried over
        $one = Inheritance::getById($one->getId(), ['force' => true]);
        $oneBrick = new DataObject\Objectbrick\Data\UnittestBrick($one);
        $oneBrick->setBrickInput('parentbrick');
        $oneBrick->setBrickconsent(new Consent(true));
        $one->getMybricks()->setUnittestBrick($oneBrick);
        $one->save();

        $childBrickRow = $db->fetchAssociative('SELECT * FROM object_brick_query_unittestBrick_' . $classId . ' WHERE id = ?', [$two->getId()]);
        $this->assertIsArray($childBrickRow, 'the missing child brick query row was created');
        $this->assertSame(0, (int) $childBrickRow['brickconsent'], 'child must not inherit the parent consent');
        // the inheritable brick field is still copied over
        $this->assertEquals('parentbrick', $childBrickRow['brickinput']);
    }

    private function createParent(bool $withConsent): Inheritance
    {
        $one = new Inheritance();
        $one->setKey('consent-one');
        $one->setParentId(1);
        $one->setPublished(true);
        $one->setNormalInput('parenttext');

        $oneBrick = new DataObject\Objectbrick\Data\UnittestBrick($one);
        $oneBrick->setBrickInput('parentbrick');
        $one->getMybricks()->setUnittestBrick($oneBrick);

        if ($withConsent) {
            $one->setConsentinherited(new Consent(true));
            $one->setLconsentinherited(new Consent(true), 'en');
            $oneBrick->setBrickconsent(new Consent(true));
        }

        $one->save();

        return $one;
    }

    private function createChild(Inheritance $one, bool $withBrick = true): Inheritance
    {
        // the child does not give any consent
        $two = new Inheritance();
        $two->setKey('consent-two');
        $two->setParentId($one->getId());
        $two->setPublished(true);

        if ($withBrick) {
            $twoBrick = new DataObject\Objectbrick\Data\UnittestBrick($two);
            $twoBrick->setBrickInput2('childbrick');
            $two->getMybricks()->setUnittestBrick($twoBrick);
        }

        $two->save();

        return $two;
    }

    private function assertConsentQueryValues(Inheritance $object, int $expected, string $message): void
    {
        $db = Db::get();
        $classId = $object->getClassId();

        $row = $db->fetchAssociative('SELECT * FROM object_query_' . $classId . ' WHERE oo_id = ?', [$object->getId()]);
        $this->assertSame($expected, (int) $row['consentinherited'], $message . ' (object_query_)');

        $localizedRow = $db->fetchAssociative('SELECT * FROM object_localized_query_' . $classId . '_en WHERE ooo_id = ?', [$object->getId()]);
        $this->assertSame($expected, (int) $localizedRow['lconsentinherited'], $message . ' (object_localized_query_)');

        $brickRow = $db->fetchAssociative('SELECT * FROM object_brick_query_unittestBrick_' . $classId . ' WHERE id = ?', [$object->getId()]);
        $this->assertSame($expected, (int) $brickRow['brickconsent'], $message . ' (object_brick_query_)');
    }
}
