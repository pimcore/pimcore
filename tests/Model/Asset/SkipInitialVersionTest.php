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

namespace Pimcore\Tests\Model\Asset;

use Pimcore\Config;
use Pimcore\Model\Asset;
use Pimcore\Model\Schedule\Task;
use Pimcore\Model\Version;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Covers `pimcore.assets.versions.skip_initial_version`: no version is created when an asset is added, the persisted
 * state is versioned lazily on the first modification instead.
 *
 * @group model.asset.asset
 */
class SkipInitialVersionTest extends ModelTestCase
{
    private ?array $originalAssetsConfig = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalAssetsConfig = Config::getSystemConfiguration('assets');
        $this->setSkipInitialVersion(true);
    }

    protected function tearDown(): void
    {
        Config::setSystemConfiguration($this->originalAssetsConfig, 'assets');
        Version::enable();

        TestHelper::cleanUp();

        parent::tearDown();
    }

    private function setSkipInitialVersion(bool $skip): void
    {
        $config = $this->originalAssetsConfig ?? [];
        $config['versions']['skip_initial_version'] = $skip;

        Config::setSystemConfiguration($config, 'assets');
    }

    /**
     * @return Version[]
     */
    private function loadVersions(Asset $asset): array
    {
        $listing = new Version\Listing();
        $listing->setCondition('cid = :cid AND ctype = :ctype', ['cid' => $asset->getId(), 'ctype' => 'asset']);
        $listing->setOrderKey('id')->setOrder('ASC');

        return $listing->load();
    }

    private function loadFileContent(string $filePath): string
    {
        $content = file_get_contents(TestHelper::resolveFilePath($filePath));
        $this->assertNotFalse($content);

        return $content;
    }

    public function testNoVersionIsCreatedOnAdd(): void
    {
        $asset = TestHelper::createImageAsset();

        $this->assertCount(0, $this->loadVersions($asset));
        $this->assertNull($asset->getLatestVersion(null, true));
        $this->assertCount(0, Asset::getById($asset->getId(), ['force' => true])->getVersions());
    }

    public function testScheduledTasksAreStillSavedOnAdd(): void
    {
        $asset = TestHelper::createImageAsset('', null, false);
        $asset->setScheduledTasks([
            new Task(['date' => time() + 3600, 'action' => 'publish', 'active' => true]),
        ]);
        $asset->save();

        $this->assertCount(0, $this->loadVersions($asset));

        $tasks = Asset::getById($asset->getId(), ['force' => true])->getScheduledTasks();
        $this->assertCount(1, $tasks, 'scheduled tasks are not versioned and must be saved even without a version');
        $this->assertSame('publish', $tasks[0]->getAction());
        $this->assertSame($asset->getId(), $tasks[0]->getCid());
    }

    public function testVersionIsStillCreatedOnAddWhenOptionIsDisabled(): void
    {
        $this->setSkipInitialVersion(false);

        $asset = TestHelper::createImageAsset();

        $versions = $this->loadVersions($asset);
        $this->assertCount(1, $versions);
        $this->assertSame(1, $versions[0]->getVersionCount());
    }

    public function testFoldersAreNotAffected(): void
    {
        $folder = TestHelper::createAssetFolder();
        $this->assertCount(0, $this->loadVersions($folder));

        $folder->setProperty('propname', 'text', 'changed');
        $folder->save();

        $this->assertCount(0, $this->loadVersions($folder));
    }

    public function testPersistedStateIsVersionedOnFirstBinaryChange(): void
    {
        $originalContent = $this->loadFileContent('assets/images/image5.jpg');
        $changedContent = $this->loadFileContent('assets/images/image1.jpg');
        $this->assertNotSame($originalContent, $changedContent);

        $asset = TestHelper::createImageAsset('', $originalContent);
        $originalModificationDate = $asset->getModificationDate();
        $originalVersionCount = $asset->getVersionCount();
        $this->assertCount(0, $this->loadVersions($asset));

        // make sure the modification dates of both versions differ
        $asset->setModificationDate($originalModificationDate + 10);
        $asset->setData($changedContent);
        $asset->save(['versionNote' => 'first edit']);

        $versions = $this->loadVersions($asset);
        $this->assertCount(2, $versions, 'the persisted state and the new state are both versioned on the first edit');

        [$snapshot, $edit] = $versions;

        // the lazily created version reflects the state as it was persisted when the asset was added
        $this->assertSame($originalModificationDate, $snapshot->getDate());
        $this->assertSame($originalVersionCount, $snapshot->getVersionCount());
        $this->assertSame(1, $snapshot->getUserId());
        $this->assertSame('', $snapshot->getNote());
        $this->assertSame($originalContent, stream_get_contents($snapshot->getBinaryFileStream()));

        $snapshotAsset = $snapshot->loadData();
        $this->assertInstanceOf(Asset::class, $snapshotAsset);
        $this->assertSame($asset->getId(), $snapshotAsset->getId());
        $this->assertSame($originalContent, stream_get_contents($snapshotAsset->getStream()));

        // the regular version of the edit is created on top of it
        $this->assertSame($originalModificationDate + 10, $edit->getDate());
        $this->assertSame($originalVersionCount + 1, $edit->getVersionCount());
        $this->assertSame('first edit', $edit->getNote());
        $this->assertSame($changedContent, stream_get_contents($edit->getBinaryFileStream()));

        // the asset itself carries the new content and points to the version of the edit
        $this->assertSame($changedContent, stream_get_contents(Asset::getById($asset->getId(), ['force' => true])->getStream()));
        $this->assertSame($edit->getId(), $asset->getLatestVersion(null, true)->getId());
    }

    public function testPersistedStateIsVersionedOnlyOnce(): void
    {
        $asset = TestHelper::createImageAsset();

        $asset->setProperty('propname', 'text', 'first change');
        $asset->save();
        $this->assertCount(2, $this->loadVersions($asset));

        $asset->setProperty('propname', 'text', 'second change');
        $asset->save();
        $this->assertCount(3, $this->loadVersions($asset), 'subsequent saves create exactly one version each');

        $asset->setData($this->loadFileContent('assets/images/image1.jpg'));
        $asset->save();
        $this->assertCount(4, $this->loadVersions($asset));
    }

    public function testMetadataOnlyChangeVersionsPersistedStateAndSharesBinaryData(): void
    {
        $originalContent = $this->loadFileContent('assets/images/image5.jpg');
        $asset = TestHelper::createImageAsset('', $originalContent);

        $asset->setProperty('propname', 'text', 'changed');
        $asset->save();

        $versions = $this->loadVersions($asset);
        $this->assertCount(2, $versions);
        [$snapshot, $edit] = $versions;

        $this->assertSame('bla', $snapshot->loadData()->getProperty('propname'));
        $this->assertSame('changed', $edit->loadData()->getProperty('propname'));

        // the binary data didn't change, so it is stored only once and shared by both versions
        $this->assertSame($snapshot->getBinaryFileHash(), $edit->getBinaryFileHash());
        $this->assertSame($snapshot->getId(), $edit->getBinaryFileId());
        $this->assertSame($originalContent, stream_get_contents($edit->getBinaryFileStream()));
    }

    public function testPersistedStateIsVersionedWhenAssetIsRenamedOnFirstEdit(): void
    {
        $originalContent = $this->loadFileContent('assets/images/image5.jpg');
        $changedContent = $this->loadFileContent('assets/images/image1.jpg');

        $asset = TestHelper::createImageAsset('', $originalContent);
        $originalFilename = $asset->getFilename();

        $asset->setFilename('renamed-' . $originalFilename);
        $asset->setData($changedContent);
        $asset->save();

        $versions = $this->loadVersions($asset);
        $this->assertCount(2, $versions);
        [$snapshot, $edit] = $versions;

        // the binary data was captured before the asset was renamed and overwritten
        $this->assertSame($originalContent, stream_get_contents($snapshot->getBinaryFileStream()));
        $this->assertSame($changedContent, stream_get_contents($edit->getBinaryFileStream()));

        // the asset being saved is back in the runtime cache with its new state
        $this->assertSame($asset, Asset::getById($asset->getId()));
        $this->assertSame('renamed-' . $originalFilename, Asset::getById($asset->getId())->getFilename());
    }

    public function testExistingVersionsPreventLazySnapshot(): void
    {
        $this->setSkipInitialVersion(false);
        $asset = TestHelper::createImageAsset();
        $this->assertCount(1, $this->loadVersions($asset));

        $this->setSkipInitialVersion(true);
        $asset->setProperty('propname', 'text', 'changed');
        $asset->save();

        $this->assertCount(2, $this->loadVersions($asset), 'an asset that already has versions gets exactly one new version');
    }

    public function testDisabledVersioningSkipsLazySnapshot(): void
    {
        $asset = TestHelper::createImageAsset();

        Version::disable();

        try {
            $asset->setProperty('propname', 'text', 'changed');
            $asset->save();
        } finally {
            Version::enable();
        }

        $this->assertCount(0, $this->loadVersions($asset));
    }

    public function testSaveVersionCalledDirectlyStillCreatesVersion(): void
    {
        $asset = TestHelper::createImageAsset();
        $this->assertCount(0, $this->loadVersions($asset));

        // "save only version" is an explicit request for a version and must not be affected by the option
        $version = $asset->saveVersion(true, true, 'explicit version');

        $this->assertNotNull($version);
        $versions = $this->loadVersions($asset);
        $this->assertCount(1, $versions);
        $this->assertSame('explicit version', $versions[0]->getNote());
    }
}
