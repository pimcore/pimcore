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

use Exception;
use League\Flysystem\UnableToReadFile;
use Pimcore;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Config;
use Pimcore\Db;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\Model\Asset\ResolveMimeTypeEvent;
use Pimcore\Event\Model\VersionEvent;
use Pimcore\Event\VersionEvents;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\Service as ElementService;
use Pimcore\Model\Schedule\Task;
use Pimcore\Model\Version;
use Pimcore\Model\Version\Adapter\FileSystemVersionStorageAdapter;
use Pimcore\SystemSettingsConfig;
use Pimcore\Tests\Support\Helper\Pimcore as PimcoreHelper;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Storage;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use RuntimeException;
use Throwable;

/**
 * Covers `pimcore.assets.versions.skip_initial_version`: no version is created when an asset is added, the persisted
 * state is versioned lazily on the first modification instead.
 *
 * @group model.asset.asset
 */
class SkipInitialVersionTest extends ModelTestCase
{
    private const INITIAL_VERSION_SKIPPED = 'pimcore-asset-initial-version-skipped';

    private ?array $originalAssetsConfig = null;

    private SystemSettingsConfig $systemSettingsConfig;

    private array $originalSystemSettings;

    private array $registeredListeners = [];

    private ?ContainerInterface $originalStorageLocator = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalAssetsConfig = Config::getSystemConfiguration('assets');
        $this->setSkipInitialVersion(true);

        $pimcoreModule = $this->getModule('\\' . PimcoreHelper::class);
        $this->systemSettingsConfig = $pimcoreModule->grabService(SystemSettingsConfig::class);
        $this->originalSystemSettings = $this->systemSettingsConfig->get();
    }

    protected function tearDown(): void
    {
        $this->restoreStorage();
        Config::setSystemConfiguration($this->originalAssetsConfig, 'assets');
        $this->systemSettingsConfig->testSave($this->originalSystemSettings);
        Version::enable();

        foreach ($this->registeredListeners as [$eventName, $listener]) {
            Pimcore::getEventDispatcher()->removeListener($eventName, $listener);
        }
        $this->registeredListeners = [];

        TestHelper::cleanUp();

        parent::tearDown();
    }

    private function addListener(string $eventName, callable $listener): void
    {
        Pimcore::getEventDispatcher()->addListener($eventName, $listener);
        $this->registeredListeners[] = [$eventName, $listener];
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
        $this->assertNull(
            Asset::getById($asset->getId(), ['force' => true])->getCustomSetting(self::INITIAL_VERSION_SKIPPED),
            'an asset that got its upload version is not marked'
        );
    }

    public function testPersistedStateIsVersionedAfterTheOptionIsDisabledAgain(): void
    {
        // an asset added while the option was enabled has no version of its upload; disabling the option must not
        // make its first modification overwrite the original data without a version
        $originalContent = $this->loadFileContent('assets/images/image5.jpg');
        $changedContent = $this->loadFileContent('assets/images/image1.jpg');
        $asset = TestHelper::createImageAsset('', $originalContent);

        $this->setSkipInitialVersion(false);

        $asset = Asset::getById($asset->getId(), ['force' => true]);
        $asset->setData($changedContent);
        $asset->save();

        $versions = $this->loadVersions($asset);
        $this->assertCount(2, $versions, 'the persisted state and the modification are versioned');
        $this->assertSame($originalContent, stream_get_contents($versions[0]->getBinaryFileStream()));
        $this->assertSame($changedContent, stream_get_contents($versions[1]->getBinaryFileStream()));
    }

    public function testMarkerOfSkippedInitialVersionIsRemovedOnceVersioned(): void
    {
        $asset = TestHelper::createImageAsset();
        $this->assertTrue(
            Asset::getById($asset->getId(), ['force' => true])->getCustomSetting(self::INITIAL_VERSION_SKIPPED),
            'an asset added without a version is marked'
        );

        $asset->setProperty('propname', 'text', 'changed');
        $asset->save();

        $this->assertNull(Asset::getById($asset->getId(), ['force' => true])->getCustomSetting(self::INITIAL_VERSION_SKIPPED));

        // the marker is not part of the versioned state, so restoring the snapshot doesn't bring it back
        [$snapshot] = $this->loadVersions($asset);
        $this->assertNull($snapshot->loadData()->getCustomSetting(self::INITIAL_VERSION_SKIPPED));
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

    public function testDisabledVersioningSkipsLazySnapshotWithoutBinaryChange(): void
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

    public function testDisabledVersioningStillVersionsPersistedStateOnBinaryChange(): void
    {
        // without a version of the upload, replacing the binary data with versioning disabled (importers, data-hub
        // `omitVersionCreate`, ...) would otherwise destroy the original data for good
        $originalContent = $this->loadFileContent('assets/images/image5.jpg');
        $changedContent = $this->loadFileContent('assets/images/image1.jpg');
        $asset = TestHelper::createImageAsset('', $originalContent);

        Version::disable();

        try {
            $asset->setData($changedContent);
            $asset->save();
        } finally {
            Version::enable();
        }

        $versions = $this->loadVersions($asset);
        $this->assertCount(1, $versions, 'only the persisted state is versioned, the edit itself is not');
        $this->assertSame($originalContent, stream_get_contents($versions[0]->getBinaryFileStream()));
        $this->assertSame($changedContent, stream_get_contents(Asset::getById($asset->getId(), ['force' => true])->getStream()));
    }

    public function testVersioningStaysDisabledAfterLazySnapshot(): void
    {
        $asset = TestHelper::createImageAsset();

        Version::disable();

        try {
            $asset->setData($this->loadFileContent('assets/images/image1.jpg'));
            $asset->save();

            $this->assertFalse(Version::isEnabled(), 'the snapshot must not re-enable versioning for the caller');
        } finally {
            Version::enable();
        }
    }

    public function testSaveIsAbortedWhenPersistedBinaryCannotBeRead(): void
    {
        $originalContent = $this->loadFileContent('assets/images/image5.jpg');
        $asset = TestHelper::createImageAsset('', $originalContent);
        $path = $asset->getRealFullPath();

        // a transient read error (e.g. on an object storage) must not lead to an empty snapshot, followed by
        // overwriting the original binary data
        $this->failReadsOnAssetStorage($path);

        $asset->setData($this->loadFileContent('assets/images/image1.jpg'));

        $exception = null;

        try {
            $asset->save();
        } catch (Exception $e) {
            $exception = $e;
        } finally {
            $this->restoreStorage();
        }

        $this->assertNotNull($exception, 'save() was expected to fail because the persisted binary data cannot be read');
        $this->assertStringContainsString('Unable to read the binary data', $exception->getMessage());

        $this->assertCount(0, $this->loadVersions($asset), 'no (empty) snapshot is kept');
        $this->assertSame($originalContent, Storage::get('asset')->read($path), 'the original binary data is untouched');
    }

    public function testRuntimeCacheIsRestoredWhenSnapshotFails(): void
    {
        $asset = TestHelper::createImageAsset();
        $cacheKey = ElementService::getElementCacheTag('asset', $asset->getId());
        RuntimeCache::getInstance()->offsetUnset($cacheKey);

        // fails while the persisted instance temporarily takes the place in the runtime cache
        $this->failPostSaveOfFirstVersion($asset);
        $asset->setFilename('unsaved-' . $asset->getFilename());

        $exception = null;

        try {
            $asset->save();
        } catch (RuntimeException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception, 'save() was expected to fail');

        // the unsaved instance must not have taken the place of the absent runtime cache entry
        $this->assertFalse(RuntimeCache::isRegistered($cacheKey));
    }

    public function testMissingPersistedBinaryDoesNotBlockSave(): void
    {
        $asset = TestHelper::createImageAsset();
        $path = $asset->getRealFullPath();

        // there is nothing to preserve if the binary data is already gone, uploading a replacement must stay possible
        Storage::get('asset')->delete($path);

        $changedContent = $this->loadFileContent('assets/images/image1.jpg');
        $asset->setData($changedContent);
        $asset->save();

        $this->assertCount(2, $this->loadVersions($asset));
        $this->assertSame($changedContent, Storage::get('asset')->read($path));
    }

    public function testRetentionPolicyWithoutVersionsIsRespected(): void
    {
        // "keep 0 versions" means regular saves create no versions at all, so the lazy version of the
        // persisted state must not be created either
        $settings = $this->originalSystemSettings;
        $settings['assets']['versions']['steps'] = 0;
        $settings['assets']['versions']['days'] = null;
        $this->systemSettingsConfig->testSave($settings);
        $this->assertFalse(Asset::isVersionCreationEnabledByConfig());

        $asset = TestHelper::createImageAsset();
        $this->assertCount(0, $this->loadVersions($asset));

        $asset->setProperty('propname', 'text', 'changed');
        $asset->save();
        $this->assertCount(0, $this->loadVersions($asset), 'no lazy version with a zero-version retention policy');

        $asset->setData($this->loadFileContent('assets/images/image1.jpg'));
        $asset->save();
        $this->assertCount(0, $this->loadVersions($asset));

        // an explicit saveVersion() call is still honored, as for regular saves
        $asset->saveVersion(true, true, 'explicit version');
        $this->assertCount(1, $this->loadVersions($asset));
    }

    public function testSnapshotIsKeptWhenSaveIsRolledBack(): void
    {
        $originalContent = $this->loadFileContent('assets/images/image5.jpg');
        $changedContent = $this->loadFileContent('assets/images/image1.jpg');
        $asset = TestHelper::createImageAsset('', $originalContent);

        // fails inside update() after the new binary data was already written to the (non-transactional) asset
        // storage, the transaction is rolled back afterwards
        $failingListener = function (ResolveMimeTypeEvent $event) use ($asset): void {
            if ($event->getAsset() === $asset) {
                throw new RuntimeException('simulated failure during save');
            }
        };
        $this->addListener(AssetEvents::RESOLVE_MIME_TYPE, $failingListener);

        $asset->setData($changedContent);

        $exception = null;

        try {
            $asset->save();
        } catch (RuntimeException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception, 'save() was expected to fail');
        $this->assertSame('simulated failure during save', $exception->getMessage());

        // the snapshot is committed before the save transaction, so the original binary data stays restorable even
        // though the failed save already overwrote it on the asset storage
        $versions = $this->loadVersions($asset);
        $this->assertCount(1, $versions, 'the version of the persisted state is kept');
        $snapshot = $versions[0];
        $this->assertSame($originalContent, stream_get_contents($snapshot->getBinaryFileStream()));
        $this->assertSame($originalContent, stream_get_contents($snapshot->loadData()->getStream()));

        // a subsequent successful save only adds the regular version
        Pimcore::getEventDispatcher()->removeListener(AssetEvents::RESOLVE_MIME_TYPE, $failingListener);
        $asset->save();

        $versions = $this->loadVersions($asset);
        $this->assertCount(2, $versions);
        $this->assertSame($snapshot->getId(), $versions[0]->getId());
        $this->assertSame($changedContent, stream_get_contents($versions[1]->getBinaryFileStream()));
    }

    public function testEditVersionIsCreatedWhenSnapshotIsRemovedBeforeSaveTransaction(): void
    {
        $asset = TestHelper::createImageAsset();
        $changedContent = $this->loadFileContent('assets/images/image1.jpg');

        // simulates the versions cleanup removing the (committed) snapshot while the save is running: this is still
        // an update of the asset and must get its regular version
        $this->addListener(AssetEvents::RESOLVE_MIME_TYPE, function (ResolveMimeTypeEvent $event) use ($asset): void {
            if ($event->getAsset() === $asset) {
                foreach ($this->loadVersions($asset) as $version) {
                    $version->delete();
                }
            }
        });

        $asset->setData($changedContent);
        $asset->save();

        $versions = $this->loadVersions($asset);
        $this->assertCount(1, $versions, 'the regular version of the update is created');
        $this->assertSame($changedContent, stream_get_contents($versions[0]->getBinaryFileStream()));
    }

    public function testStorageFilesOfSnapshotAreRemovedWhenItsSaveFailsAfterWriting(): void
    {
        $originalContent = $this->loadFileContent('assets/images/image5.jpg');
        $asset = TestHelper::createImageAsset('', $originalContent);
        $snapshot = $this->failPostSaveOfFirstVersion($asset);

        $asset->setData($this->loadFileContent('assets/images/image1.jpg'));

        $exception = null;

        try {
            $asset->save();
        } catch (RuntimeException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception, 'save() was expected to fail');
        $this->assertSame('simulated failure after the version was written', $exception->getMessage());
        $this->assertSnapshotIsGone($asset, $snapshot->version);
        $this->assertSame($originalContent, Storage::get('asset')->read($asset->getRealFullPath()), 'the save was aborted before the binary data was overwritten');
    }

    public function testStorageFilesOfSnapshotAreRemovedWhenItsSaveFailsInSaveVersion(): void
    {
        $asset = TestHelper::createImageAsset();
        $snapshot = $this->failPostSaveOfFirstVersion($asset);

        $exception = null;

        try {
            $asset->saveVersion(true, true, 'explicit version');
        } catch (RuntimeException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception, 'saveVersion() was expected to fail');
        $this->assertSnapshotIsGone($asset, $snapshot->version);
    }

    public function testStorageFilesOfSnapshotAreRemovedWhenItsCommitFails(): void
    {
        $asset = TestHelper::createImageAsset();

        /** @var Version|null $snapshot */
        $snapshot = null;
        $this->addListener(VersionEvents::POST_SAVE, function (VersionEvent $event) use ($asset, &$snapshot): void {
            if ($snapshot === null && $event->getVersion()->getCid() === $asset->getId()) {
                $snapshot = $event->getVersion();

                // the transaction of the snapshot is gone underneath, so its commit() fails after the snapshot's
                // row and storage files were written
                Db::get()->rollBack();
            }
        });

        $exception = null;

        try {
            $asset->saveVersion(true, true, 'explicit version');
        } catch (Throwable $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception, 'saveVersion() was expected to fail because the commit fails');
        $this->assertSnapshotIsGone($asset, $snapshot);
    }

    /**
     * Lets the POST_SAVE event of the first version of the asset fail, after its row and storage files were written.
     *
     * @return object{version: ?Version}
     */
    private function failPostSaveOfFirstVersion(Asset $asset): object
    {
        $captured = new class() {
            public ?Version $version = null;
        };

        $this->addListener(VersionEvents::POST_SAVE, function (VersionEvent $event) use ($asset, $captured): void {
            if ($captured->version === null && $event->getVersion()->getCid() === $asset->getId()) {
                $captured->version = $event->getVersion();

                throw new RuntimeException('simulated failure after the version was written');
            }
        });

        return $captured;
    }

    private function assertSnapshotIsGone(Asset $asset, ?Version $snapshot): void
    {
        $this->assertNotNull($snapshot, 'the version of the persisted state was written before the failure');
        $this->assertCount(0, $this->loadVersions($asset), 'the version row is removed');

        // version storage is not transactional, the files must have been cleaned up explicitly
        $storage = Storage::get('version');
        $adapter = new FileSystemVersionStorageAdapter();
        $this->assertFalse($storage->fileExists($adapter->getStorageFilename($snapshot->getId(), $asset->getId(), 'asset')));
        $this->assertFalse($storage->fileExists($adapter->getBinaryStoragePath($snapshot)));
    }

    public function testSaveVersionCalledDirectlyStillCreatesVersion(): void
    {
        $asset = TestHelper::createImageAsset();
        $this->assertCount(0, $this->loadVersions($asset));

        // "save only version" is an explicit request for a version and must not be affected by the option
        $version = $asset->saveVersion(true, true, 'explicit version');

        $this->assertNotNull($version);
        $versions = $this->loadVersions($asset);
        $this->assertCount(2, $versions, 'the persisted state is versioned first, followed by the explicit version');
        $this->assertSame('explicit version', $versions[1]->getNote());
        $this->assertSame($version->getId(), $versions[1]->getId());
    }

    public function testSaveVersionCalledDirectlyVersionsPersistedStateFirst(): void
    {
        // the version created by saveVersion() would otherwise prevent the lazy snapshot on the next save(),
        // so the persisted (upload) state would be overwritten without ever being versioned
        $asset = TestHelper::createImageAsset();

        $asset->setProperty('propname', 'text', 'unsaved change');
        $asset->saveVersion(true, true, 'unsaved change');

        $versions = $this->loadVersions($asset);
        $this->assertCount(2, $versions);
        [$snapshot, $explicit] = $versions;
        $this->assertSame('bla', $snapshot->loadData()->getProperty('propname'));
        $this->assertSame('unsaved change', $explicit->loadData()->getProperty('propname'));
        $this->assertSame('unsaved change', $explicit->getNote());

        // the next save() doesn't create another snapshot
        $asset->save();
        $this->assertCount(3, $this->loadVersions($asset));
    }

    public function testSaveVersionCalledDirectlyWithDisabledVersioningCreatesNoSnapshot(): void
    {
        // no version is created, so nothing prevents the lazy snapshot on a later save()
        $asset = TestHelper::createImageAsset();

        Version::disable();

        try {
            $asset->saveVersion(true, true, 'not versioned');
        } finally {
            Version::enable();
        }

        $this->assertCount(0, $this->loadVersions($asset));
    }

    /**
     * Makes the next readStream() of the asset storage fail for the given path while the file still exists, as it
     * happens with a transient error of a remote storage. The storage service is shared (and already initialized), so its locator
     * is swapped instead.
     */
    private function failReadsOnAssetStorage(string $failingPath): void
    {
        $storage = Pimcore::getContainer()->get(Storage::class);
        $locatorProperty = new ReflectionProperty(Storage::class, 'locator');
        $originalLocator = $locatorProperty->getValue($storage);
        $this->originalStorageLocator = $originalLocator;

        $assetStorage = $originalLocator->get('pimcore.asset.storage');
        $failed = false;
        $methods = array_filter(
            get_class_methods($assetStorage),
            static fn (string $method): bool => !str_starts_with($method, '__')
        );
        $failingAssetStorage = $this->getMockBuilder($assetStorage::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array_values($methods))
            ->getMock();
        foreach ($methods as $method) {
            if ($method === 'readStream') {
                continue;
            }
            $failingAssetStorage->method($method)->willReturnCallback(
                static fn (mixed ...$arguments): mixed => $assetStorage->$method(...$arguments)
            );
        }
        $failingAssetStorage->method('readStream')->willReturnCallback(
            static function (string $location) use ($assetStorage, $failingPath, &$failed) {
                if ($location === $failingPath && !$failed) {
                    $failed = true;

                    throw UnableToReadFile::fromLocation($location, 'simulated transient read error');
                }

                return $assetStorage->readStream($location);
            }
        );

        $locator = $this->createStub(ContainerInterface::class);
        $locator->method('get')->willReturnCallback(
            static fn (string $id): mixed => $id === 'pimcore.asset.storage' ? $failingAssetStorage : $originalLocator->get($id)
        );

        $locatorProperty->setValue($storage, $locator);
    }

    private function restoreStorage(): void
    {
        if ($this->originalStorageLocator !== null) {
            $storage = Pimcore::getContainer()->get(Storage::class);
            (new ReflectionProperty(Storage::class, 'locator'))->setValue($storage, $this->originalStorageLocator);
            $this->originalStorageLocator = null;
        }
    }
}
