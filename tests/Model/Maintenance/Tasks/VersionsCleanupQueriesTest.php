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

namespace Pimcore\Tests\Model\Maintenance\Tasks;

use Pimcore\Db;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Version;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * The version cleanup (VersionsCleanupTask) reads its candidates through the version DAO. These queries are written so
 * that MySQL can answer them from indexes instead of reading the whole versions table; the tests pin that they still
 * return exactly what the cleanup is meant to delete.
 */
class VersionsCleanupQueriesTest extends ModelTestCase
{
    /**
     * Ranking only the elements with more versions than are kept must return the same versions as ranking every
     * version of the type
     */
    public function testStepCleanupReturnsTheSameVersionsAsRankingEveryVersion(): void
    {
        $overTheLimit = $this->createObjectWithVersions(5);
        $atTheLimit = $this->createObjectWithVersions(2);
        $justOverTheLimit = $this->createObjectWithVersions(3);
        $cids = [$overTheLimit->getId(), $atTheLimit->getId(), $justOverTheLimit->getId()];

        $outdated = $this->outdatedVersionIdsOf($cids, 1);

        $this->assertSame($this->outdatedVersionIdsRankingEveryVersion($cids, 1), $outdated);
        $this->assertNotEmpty(array_intersect($outdated, $this->versionIdsOf($overTheLimit)), 'versions beyond the steps must be cleaned up');
        $this->assertSame([], array_values(array_intersect($outdated, $this->versionIdsOf($atTheLimit))), 'an element within the steps keeps all its versions');
    }

    public function testStepCleanupFindsNothingWhenNoElementHasMoreVersionsThanAreKept(): void
    {
        $object = $this->createObjectWithVersions(3);

        $this->assertSame([], $this->outdatedVersionIdsOf([$object->getId()], 10));
    }

    public function testOrphanedVersionsAndOutdatedAutoSavesAreFound(): void
    {
        $object = $this->createObjectWithVersions(2);
        $versionsOfTheObject = $this->versionIdsOf($object);
        $missingObjectId = (int) Db::get()->fetchOne('SELECT COALESCE(MAX(id), 0) + 1000 FROM objects');
        $missingAssetId = (int) Db::get()->fetchOne('SELECT COALESCE(MAX(id), 0) + 1000 FROM assets');

        $orphan = $this->insertVersion($missingObjectId, 'object');
        $orphanOfAnotherType = $this->insertVersion($missingAssetId, 'asset');
        $outdatedAutoSave = $this->insertVersion($object->getId(), 'object', autoSave: true, date: time() - 4 * 86400);
        $recentAutoSave = $this->insertVersion($object->getId(), 'object', autoSave: true, date: time() - 3600);

        $found = array_map('intval', (new Version())->getDao()->getOrphanedVersionsAndOutdatedAutoSave([['elementType' => 'object', 'steps' => 10]]));

        $this->assertContains($orphan, $found, 'a version of a deleted object is an orphan');
        $this->assertContains($outdatedAutoSave, $found, 'an auto-save older than 72 hours is outdated');
        $this->assertNotContains($recentAutoSave, $found, 'a recent auto-save is kept');
        $this->assertNotContains($orphanOfAnotherType, $found, 'only versions of the requested element type are orphans');
        $this->assertSame([], array_values(array_intersect($found, $versionsOfTheObject)), 'versions of an existing object are kept');
        $this->assertSame(count($found), count(array_unique($found)), 'each version is found once');
    }

    private function createObjectWithVersions(int $versions): Concrete
    {
        $object = TestHelper::createEmptyObject();
        while ($this->countVersions($object) < $versions) {
            $object = DataObject::getById($object->getId(), ['force' => true]);
            $this->assertInstanceOf(Concrete::class, $object);
            $object->save();
        }
        $this->assertSame($versions, $this->countVersions($object));

        return $object;
    }

    /**
     * @param int[] $cids
     *
     * @return int[]
     */
    private function outdatedVersionIdsOf(array $cids, int $steps): array
    {
        $versionIds = (new Version())->getDao()->maintenanceGetOutdatedVersions([['elementType' => 'object', 'steps' => $steps]]);
        $ofTheseElements = array_merge(...array_map(fn (int $cid): array => $this->versionIdsOfCid($cid), $cids));
        $result = array_values(array_intersect(array_map('intval', $versionIds), $ofTheseElements));
        sort($result);

        return $result;
    }

    /**
     * The query maintenanceGetOutdatedVersions() ran before it restricted the ranking to elements with more versions
     * than are kept
     *
     * @param int[] $cids
     *
     * @return int[]
     */
    private function outdatedVersionIdsRankingEveryVersion(array $cids, int $steps): array
    {
        $versionIds = Db::get()->fetchFirstColumn(
            'SELECT sub.id
            FROM (
                SELECT id, cid, versions.`date`, versionCount,
                       ROW_NUMBER() OVER (PARTITION BY cid ORDER BY versionCount DESC, id DESC) AS rownumber
                FROM versions
                WHERE ctype = ? AND public = 0 AND autosave = 0
            ) sub
            LEFT JOIN schedule_tasks ON sub.id = schedule_tasks.version
            LEFT JOIN objects AS element ON sub.cid = element.id
            WHERE rownumber > ? AND IFNULL(active, 0) = 0 AND element.modificationDate >= sub.`date`
            AND NOT (sub.versionCount = element.versionCount AND sub.`date` = element.modificationDate)',
            ['object', $steps + 1]
        );
        $ofTheseElements = array_merge(...array_map(fn (int $cid): array => $this->versionIdsOfCid($cid), $cids));
        $result = array_values(array_intersect(array_map('intval', $versionIds), $ofTheseElements));
        sort($result);

        return $result;
    }

    private function insertVersion(int $cid, string $ctype, bool $autoSave = false, ?int $date = null): int
    {
        Db::get()->insert('versions', [
            'cid' => $cid,
            'ctype' => $ctype,
            'date' => $date ?? time(),
            'public' => 0,
            'autoSave' => $autoSave ? 1 : 0,
            'storageType' => 'db',
        ]);

        return (int) Db::get()->lastInsertId();
    }

    /**
     * @return int[]
     */
    private function versionIdsOf(Concrete $object): array
    {
        return $this->versionIdsOfCid($object->getId());
    }

    /**
     * @return int[]
     */
    private function versionIdsOfCid(int $cid): array
    {
        return array_map('intval', Db::get()->fetchFirstColumn('SELECT id FROM versions WHERE cid = ? AND ctype = ?', [$cid, 'object']));
    }

    private function countVersions(Concrete $object): int
    {
        return count($this->versionIdsOf($object));
    }
}
