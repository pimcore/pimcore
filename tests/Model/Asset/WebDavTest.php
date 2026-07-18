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

use Pimcore\Model\Asset;
use Pimcore\Model\Asset\WebDAV\Service;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Serialize;

/**
 * @group model.asset.webdav
 */
class WebDavTest extends ModelTestCase
{
    protected function tearDown(): void
    {
        // make sure the shared delete log file never leaks into other tests
        if (file_exists(Service::getDeleteLogFile())) {
            unlink(Service::getDeleteLogFile());
        }

        parent::tearDown();
    }

    /**
     * Regression test for Asset\WebDAV\Tree::move(): the delete-log entry written by
     * Asset\WebDAV\File::delete() must be restorable back into a real Asset instance.
     *
     * Previously the restore path unserialized the payload with allowedClasses=false,
     * which decoded the asset as __PHP_Incomplete_Class and broke the delete->create->move
     * recovery (used by clients such as Photoshop). It must be unserialized allowing the
     * asset class so a usable Asset is returned.
     */
    public function testDeleteLogEntryRestoresToAsset(): void
    {
        $asset = TestHelper::createImageAsset();
        $path = $asset->getRealFullPath();

        // mirror what Asset\WebDAV\File::delete() records
        $asset->setInDumpState(true);
        $serialized = Serialize::serialize($asset);
        $asset->setInDumpState(false);

        Service::saveDeleteLog([
            $path => [
                'id' => $asset->getId(),
                'timestamp' => time(),
                'data' => $serialized,
            ],
        ]);

        $log = Service::getDeleteLog();
        $this->assertArrayHasKey($path, $log, 'delete log entry should survive the save/read round-trip');

        // this is exactly what Tree::move() does when restoring an overwritten asset
        $restored = Serialize::unserialize($log[$path]['data'], true);

        $this->assertInstanceOf(
            Asset::class,
            $restored,
            'delete-log payload must unserialize back into a usable Asset (regression: allowedClasses=false)'
        );
        $this->assertSame($asset->getId(), $restored->getId());
    }

    /**
     * Guards the buggy behaviour so it cannot silently return: unserializing with
     * allowedClasses=false yields an __PHP_Incomplete_Class, not a usable Asset.
     */
    public function testDeleteLogRestoreWithDisallowedClassesIsNotAnAsset(): void
    {
        $asset = TestHelper::createImageAsset();

        $asset->setInDumpState(true);
        $serialized = Serialize::serialize($asset);
        $asset->setInDumpState(false);

        $restored = Serialize::unserialize($serialized, false);

        $this->assertNotInstanceOf(Asset::class, $restored);
    }

    /**
     * Old entries (>30s) must be pruned by the delete-log housekeeping so stale
     * assets are never restored during a move.
     */
    public function testDeleteLogPrunesStaleEntries(): void
    {
        Service::saveDeleteLog([
            '/stale' => [
                'id' => 1,
                'timestamp' => time() - 60,
                'data' => 'x',
            ],
            '/fresh' => [
                'id' => 2,
                'timestamp' => time(),
                'data' => 'y',
            ],
        ]);

        $log = Service::getDeleteLog();

        $this->assertArrayNotHasKey('/stale', $log);
        $this->assertArrayHasKey('/fresh', $log);
    }
}
