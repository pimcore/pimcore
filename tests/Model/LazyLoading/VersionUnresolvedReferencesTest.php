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

namespace Pimcore\Tests\Model\LazyLoading;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Pimcore;
use Pimcore\Cache;
use Pimcore\Db;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\LazyLoading;
use Pimcore\Model\DataObject\RelationTest;
use Pimcore\Model\Version;
use Pimcore\Model\Version\UnresolvedReference;

/**
 * A version keeps the relations of the moment it was saved. When a related element is deleted afterwards, the
 * relation can not be restored, but the version must still tell which element it pointed to
 * (pimcore/service-operations#1286).
 */
class VersionUnresolvedReferencesTest extends AbstractLazyLoadingTest
{
    public function testVersionReportsRelationsToDeletedElements(): void
    {
        $targets = $this->loadRelations()->load();
        [$single, $kept1, $deleted, $kept2, $localized] = $targets;

        $object = $this->createDataObject();
        $object->setRelation($single);
        $object->setRelations([$kept1, $deleted, $kept2]);
        $object->setLrelations([$localized], 'en');
        $object->save();

        $versionId = $object->getLatestVersion(null, true)?->getId();
        $this->assertNotNull($versionId, 'saving the object must create a version');

        foreach ([$single, $deleted, $localized] as $target) {
            $target->delete();
        }

        Cache::clearAll();
        Pimcore::collectGarbage();

        $version = Version::getById($versionId);
        $versionData = $version->loadData();
        $this->assertInstanceOf(LazyLoading::class, $versionData);

        // the deleted elements can not be restored ...
        $this->assertNull($versionData->getRelation());
        $this->assertSame(
            [$kept1->getId(), $kept2->getId()],
            array_map(static fn (RelationTest $relation) => $relation->getId(), $versionData->getRelations())
        );
        $this->assertSame([], $versionData->getLrelations('en'));

        // ... but the version still tells which elements it pointed to
        $this->assertEquals(
            [
                new UnresolvedReference('relation', null, null, 'object', $single->getId()),
                new UnresolvedReference('relations', null, 1, 'object', $deleted->getId()),
                new UnresolvedReference('lrelations', 'en', 0, 'object', $localized->getId()),
            ],
            $version->getUnresolvedReferences()
        );
    }

    /**
     * Like in the admin, unpublished elements are not hidden, so the relations of the version must not keep a
     * placeholder for the deleted elements, otherwise the version can not be published again.
     */
    public function testVersionWithDeletedElementsCanBeRestored(): void
    {
        $targets = $this->loadRelations()->load();
        [$single, $kept1, $deleted, $kept2, $localized] = $targets;

        $object = $this->createDataObject();
        $object->setRelation($single);
        $object->setRelations([$kept1, $deleted, $kept2]);
        $object->setLobjects([$localized], 'en');
        $object->save();

        $versionId = $object->getLatestVersion(null, true)?->getId();
        $this->assertNotNull($versionId, 'saving the object must create a version');
        foreach ([$single, $deleted, $localized] as $target) {
            $target->delete();
        }

        Cache::clearAll();
        Pimcore::collectGarbage();

        $hideUnpublished = DataObject::doHideUnpublished();
        DataObject::setHideUnpublished(false);

        try {
            $version = Version::getById($versionId);
            $versionData = $version->loadData();

            $this->assertSame(
                [$kept1->getId(), $kept2->getId()],
                array_map(static fn (RelationTest $relation) => $relation->getId(), $versionData->getRelations())
            );
            $this->assertSame([], $versionData->getLobjects('en'));
            $this->assertCount(3, $version->getUnresolvedReferences());

            $versionData->save();
        } finally {
            DataObject::setHideUnpublished($hideUnpublished);
        }

        $deletedIds = [$single->getId(), $deleted->getId(), $localized->getId()];
        $staleRows = Db::get()->fetchOne(
            'SELECT COUNT(*) FROM object_relations_' . $object->getClassId() . ' WHERE src_id = ? AND dest_id IN (?)',
            [$object->getId(), $deletedIds],
            [ParameterType::INTEGER, ArrayParameterType::INTEGER]
        );
        $this->assertSame(0, (int) $staleRows, 'restoring the version must remove the relation rows of deleted elements');

        $restored = LazyLoading::getById($object->getId(), ['force' => true]);
        $this->assertNull($restored->getRelation());
        $this->assertSame(
            [$kept1->getId(), $kept2->getId()],
            array_map(static fn (RelationTest $relation) => $relation->getId(), $restored->getRelations())
        );
    }

    public function testVersionWithoutDeletedElementsHasNoUnresolvedReferences(): void
    {
        $object = $this->createDataObject();
        $object->setRelation($this->loadSingleRelation());
        $object->save();

        $versionId = $object->getLatestVersion(null, true)?->getId();
        $this->assertNotNull($versionId, 'saving the object must create a version');
        $version = Version::getById($versionId);
        $version->loadData();

        $this->assertSame([], $version->getUnresolvedReferences());
    }
}
