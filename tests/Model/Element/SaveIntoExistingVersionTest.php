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

namespace Pimcore\Tests\Model\Element;

use InvalidArgumentException;
use Pimcore\Db;
use Pimcore\Maintenance\Tasks\ScheduledTasksTask;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Schedule\Task;
use Pimcore\Model\User;
use Pimcore\Model\Version;
use Pimcore\SystemSettingsConfig;
use Pimcore\Tests\Support\Helper\Pimcore as PimcoreHelper;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Psr\Log\NullLogger;

/**
 * Publishing a prepared version writes the published state into that very version (`existingVersion` save
 * parameter) instead of creating a new one, so the version history shows which version went live
 * (pimcore/service-operations#1628).
 */
class SaveIntoExistingVersionTest extends ModelTestCase
{
    public static function elementTypeProvider(): array
    {
        return [
            'asset' => ['asset'],
            'document' => ['document'],
            'object' => ['object'],
        ];
    }

    /**
     * @dataProvider elementTypeProvider
     */
    public function testSaveWritesIntoTheGivenVersionInsteadOfCreatingANewOne(string $type): void
    {
        $element = $this->createFreshElement($type);
        $draft = $element->saveVersion();
        $this->assertNotNull($draft, 'expected a draft version to be saved');
        $this->assertNotNull($this->reload($element)->getLatestVersion(), 'the draft must be newer than the element');
        $versionCountBefore = $this->countVersions($element);

        $restored = $draft->getData();
        $this->assertInstanceOf(get_class($element), $restored, 'the element must be restorable from the draft');
        $this->publish($restored);
        $restored->save(['existingVersion' => $draft]);

        $published = $this->reload($element);
        $this->assertSame($versionCountBefore, $this->countVersions($element), 'publishing a version must not add a version');

        $publishedVersion = Version::getById($draft->getId());
        $this->assertNotNull($publishedVersion, 'the published version must still exist');
        $this->assertSame($published->getVersionCount(), $publishedVersion->getVersionCount(), 'the version must carry the element\'s version count');
        $this->assertSame($published->getModificationDate(), $publishedVersion->getDate(), 'the version must carry the element\'s modification date');
        $this->assertFalse($publishedVersion->isAutoSave(), 'a published version must not be an auto-save one');
        $this->assertNull($published->getLatestVersion(), 'no draft must be left after publishing');
        $this->assertSame($draft->getId(), $published->getLatestVersion(null, true)?->getId(), 'the published version must be the current one');
        $this->assertInstanceOf(get_class($element), $publishedVersion->getData(), 'the version data must be readable after it was written again');
    }

    /**
     * @dataProvider elementTypeProvider
     */
    public function testScheduledPublishVersionPublishesTheScheduledVersionItself(string $type): void
    {
        $element = $this->createFreshElement($type);
        $draft = $element->saveVersion();
        $this->assertNotNull($draft, 'expected a draft version to be saved');
        $versionCountBefore = $this->countVersions($element);

        $task = Task::create([
            'cid' => $element->getId(),
            'ctype' => $type,
            'date' => time() - 60,
            'action' => 'publish-version',
            'version' => $draft->getId(),
            'active' => true,
            'userId' => $this->createAdminUser()->getId(),
        ]);
        $task->save();

        (new ScheduledTasksTask(new NullLogger()))->execute();

        $published = $this->reload($element);
        $this->assertSame($versionCountBefore, $this->countVersions($element), 'the scheduled publish must not add a version');
        $this->assertNull($published->getLatestVersion(), 'no draft must be left after the scheduled publish');
        if (!$published instanceof Asset) {
            $this->assertTrue($published->isPublished(), 'the element must be published');
        }

        $publishedVersion = Version::getById($draft->getId());
        $this->assertNotNull($publishedVersion, 'the scheduled version must still exist');
        $this->assertSame($published->getVersionCount(), $publishedVersion->getVersionCount(), 'the scheduled version must be the current one');
        $this->assertSame($published->getModificationDate(), $publishedVersion->getDate(), 'the scheduled version must carry the publish date');

        $this->assertSame(0, (int) Db::get()->fetchOne('SELECT active FROM schedule_tasks WHERE id = ?', [$task->getId()]), 'the task must be done');
    }

    /**
     * With a retention policy of zero versions a regular save adds no version, writing into an existing version
     * must still happen: otherwise the version isn't published although the save succeeded
     *
     * @dataProvider elementTypeProvider
     */
    public function testSaveWritesIntoTheGivenVersionWithoutVersionRetention(string $type): void
    {
        $element = $this->createFreshElement($type);
        $draft = $element->saveVersion();
        $this->assertNotNull($draft, 'expected a draft version to be saved');
        $versionCountBefore = $this->countVersions($element);

        /** @var SystemSettingsConfig $systemSettingsConfig */
        $systemSettingsConfig = $this->getModule('\\' . PimcoreHelper::class)->grabService(SystemSettingsConfig::class);
        $originalSystemSettings = $systemSettingsConfig->get();
        $settings = $originalSystemSettings;
        $settingsKey = $type . 's';
        $settings[$settingsKey]['versions']['steps'] = 0;
        $settings[$settingsKey]['versions']['days'] = null;
        $systemSettingsConfig->testSave($settings);

        try {
            $restored = $draft->getData();
            $this->publish($restored);
            $restored->save(['existingVersion' => $draft]);
        } finally {
            $systemSettingsConfig->testSave($originalSystemSettings);
        }

        $published = $this->reload($element);
        $this->assertSame($versionCountBefore, $this->countVersions($element), 'publishing a version must not add a version');
        $publishedVersion = Version::getById($draft->getId());
        $this->assertNotNull($publishedVersion, 'the published version must still exist');
        $this->assertSame($published->getVersionCount(), $publishedVersion->getVersionCount(), 'the version must carry the element\'s version count');
        $this->assertSame($published->getModificationDate(), $publishedVersion->getDate(), 'the version must carry the element\'s modification date');
    }

    public function testAVersionOfAnotherElementIsRejected(): void
    {
        $element = $this->createFreshElement('object');
        $other = $this->createFreshElement('object');
        $otherVersion = $other->saveVersion();
        $this->assertNotNull($otherVersion, 'expected a version of the other element to be saved');
        $otherVersionCount = $this->countVersions($other);

        try {
            $element->save(['existingVersion' => $otherVersion]);
            $this->fail('saving into the version of another element must be rejected');
        } catch (InvalidArgumentException) {
        }

        $this->assertSame($otherVersionCount, $this->countVersions($other), 'the other element must keep its versions');
        $this->assertSame($other->getId(), Version::getById($otherVersion->getId())?->getCid(), 'the version must still belong to the other element');
    }

    public function testAnInvalidExistingVersionParameterIsRejected(): void
    {
        $element = $this->createFreshElement('object');

        $this->expectException(InvalidArgumentException::class);
        $element->save(['existingVersion' => 'not a version']);
    }

    /**
     * The asset storage isn't transactional, so a rejected `existingVersion` must be detected before the binary data
     * of the asset is overwritten
     */
    public function testARejectedAssetSaveLeavesTheBinaryDataUntouched(): void
    {
        $asset = $this->createFreshElement('asset');
        $originalData = $asset->getData();
        $version = $asset->getLatestVersion(null, true);
        $this->assertNotNull($version, 'expected the asset to have a version');
        $otherVersion = $this->createFreshElement('asset')->getLatestVersion(null, true);
        $this->assertNotNull($otherVersion, 'expected the other asset to have a version');

        // a version of another asset, and a version whose binary data differs from the changed asset
        foreach ([$otherVersion, $version] as $rejectedVersion) {
            $asset->setData('changed binary data');
            try {
                $asset->save(['existingVersion' => $rejectedVersion]);
                $this->fail(sprintf('saving into version %d must be rejected', $rejectedVersion->getId()));
            } catch (InvalidArgumentException) {
            }

            $this->assertSame($originalData, $this->reload($asset)->getData(), 'the binary data of the asset must be untouched');
        }
    }

    /**
     * A version that is published in place keeps its id, so the step-based cleanup must not rank it by id: it is
     * the current version and must be kept
     */
    public function testStepCleanupKeepsAVersionThatIsPublishedInPlace(): void
    {
        $object = $this->createFreshElement('object');
        $object->save();
        $olderVersion = $object->getLatestVersion(null, true);
        $this->assertNotNull($olderVersion, 'expected the object to have a version');
        $object->save();
        $object->save();

        // date the history back, so the publish below is clearly the latest change
        $versionIds = Db::get()->fetchFirstColumn('SELECT id FROM versions WHERE cid = ? AND ctype = ? ORDER BY id', [$object->getId(), 'object']);
        foreach ($versionIds as $index => $versionId) {
            Db::get()->update('versions', ['date' => time() - 1000 + $index], ['id' => $versionId]);
        }

        $olderVersion = Version::getById($olderVersion->getId());
        $this->assertNotNull($olderVersion);
        $restored = $olderVersion->getData();
        $restored->save(['existingVersion' => $olderVersion]);

        $outdatedVersionIds = (new Version())->getDao()->maintenanceGetOutdatedVersions([['elementType' => 'object', 'steps' => 1]]);

        $this->assertNotContains($olderVersion->getId(), array_map('intval', $outdatedVersionIds), 'the published version must not be cleaned up');
        $this->assertContains((int) $versionIds[0], array_map('intval', $outdatedVersionIds), 'versions beyond the steps must still be cleaned up');
    }

    /**
     * Dates have second precision: versions saved within the same second as the publish must not outrank the
     * version that is published in place
     */
    public function testStepCleanupKeepsAVersionThatIsPublishedInPlaceWithinTheSameSecond(): void
    {
        $object = $this->createFreshElement('object');
        $object->save();
        $olderVersion = $object->getLatestVersion(null, true);
        $this->assertNotNull($olderVersion, 'expected the object to have a version');
        $object->save();
        $object->save();

        $olderVersion = Version::getById($olderVersion->getId());
        $this->assertNotNull($olderVersion);
        $restored = $olderVersion->getData();
        $restored->save(['existingVersion' => $olderVersion]);

        // all versions of the object share the date of the publish
        Db::get()->executeStatement(
            'UPDATE versions SET `date` = ? WHERE cid = ? AND ctype = ?',
            [$this->reload($object)->getModificationDate(), $object->getId(), 'object']
        );

        $outdatedVersionIds = (new Version())->getDao()->maintenanceGetOutdatedVersions([['elementType' => 'object', 'steps' => 1]]);

        $this->assertNotContains($olderVersion->getId(), array_map('intval', $outdatedVersionIds), 'the published version must not be cleaned up');
    }

    private function createFreshElement(string $type): Asset|Page|Concrete
    {
        $element = match ($type) {
            'asset' => TestHelper::createImageAsset(),
            'document' => TestHelper::createEmptyDocumentPage(),
            'object' => TestHelper::createEmptyObject(),
        };

        return $this->reload($element);
    }

    /**
     * Loads the element again from the database, bypassing the runtime cache
     */
    private function reload(Asset|Page|Concrete $element): Asset|Page|Concrete
    {
        $fresh = match (true) {
            $element instanceof Asset => Asset::getById($element->getId(), ['force' => true]),
            $element instanceof Page => Document::getById($element->getId(), ['force' => true]),
            $element instanceof Concrete => DataObject::getById($element->getId(), ['force' => true]),
        };
        $this->assertNotNull($fresh);

        return $fresh;
    }

    private function publish(Asset|Page|Concrete $element): void
    {
        if (!$element instanceof Asset) {
            $element->setPublished(true);
        }
    }

    private function countVersions(Asset|Page|Concrete $element): int
    {
        return (int) Db::get()->fetchOne(
            'SELECT COUNT(*) FROM versions WHERE cid = ? AND ctype = ?',
            [$element->getId(), Service::getElementType($element)]
        );
    }

    private function createAdminUser(): User
    {
        $name = 'test-scheduled-publish-admin';
        if (!$user = User::getByName($name)) {
            $user = new User();
            $user->setName($name);
            $user->setAdmin(true);
            $user->save();
        }

        return $user;
    }
}
