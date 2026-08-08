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
use Pimcore\Model\Asset\WebDAV\Service as WebDavService;
use Pimcore\Model\Property;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Serialize;

/**
 * Regression coverage for the WebDAV delete log, which Asset\WebDAV\File::delete() writes and
 * Asset\WebDAV\Tree::move() reads to turn a third-party "delete then move" (how e.g. Photoshop
 * saves) back into an overwrite of the existing asset.
 *
 * The entry's payload is a whole serialized Asset, so reading it needs object deserialization. With
 * it forbidden the payload came back as __PHP_Incomplete_Class - which is truthy, so Tree::move()
 * went on to call setData() on it and died with "The script tried to call a method on an incomplete
 * object".
 *
 * @group model.asset.webdav
 */
class WebDavDeleteLogTest extends ModelTestCase
{
    public function tearDown(): void
    {
        $file = WebDavService::getDeleteLogFile();
        if (file_exists($file)) {
            unlink($file);
        }

        parent::tearDown();
    }

    /**
     * The log array itself only ever holds scalars - the asset lives in it as an already-serialized
     * string - which is why the outer read can forbid object deserialization.
     */
    public function testDeleteLogItselfHoldsThePayloadAsAString(): void
    {
        $asset = TestHelper::createImageAsset();
        $path = $asset->getRealFullPath();

        WebDavService::saveDeleteLog([
            $path => ['id' => $asset->getId(), 'timestamp' => time(), 'data' => Serialize::serialize($asset)],
        ]);

        $log = WebDavService::getDeleteLog();

        $this->assertArrayHasKey($path, $log);
        $this->assertIsString($log[$path]['data']);
        $this->assertSame($asset->getId(), $log[$path]['id']);
    }

    /**
     * Writes the log the way File::delete() does, reads it back the way Tree::move() does, and
     * checks the nested graph too: in dump state Asset::getBlockedVars() keeps `properties`, so the
     * payload contains Property objects as well as the Asset itself.
     */
    public function testDeletedAssetSurvivesTheDeleteLogRoundTrip(): void
    {
        $asset = TestHelper::createImageAsset();
        $asset->setProperty('webdavProbe', 'text', 'probeValue');
        $asset->save();

        $path = $asset->getRealFullPath();

        // exactly what File::delete() persists
        $asset->setInDumpState(true);
        WebDavService::saveDeleteLog([
            $path => ['id' => $asset->getId(), 'timestamp' => time(), 'data' => Serialize::serialize($asset)],
        ]);
        $asset->setInDumpState(false);

        $log = WebDavService::getDeleteLog();
        // the same call Tree::move() makes
        $restored = WebDavService::restoreDeletedAsset($log[$path]['data']);

        $this->assertInstanceOf(Asset\Image::class, $restored);
        $this->assertSame($asset->getId(), $restored->getId());

        // The nested Property objects must be intact - Asset::__wakeup() calls methods on them, so a
        // neutralised Property is an immediate fatal rather than a silent loss.
        $property = $restored->getProperties()['webdavProbe'] ?? null;
        $this->assertInstanceOf(Property::class, $property);
        $this->assertSame('probeValue', $property->getData());

        // The frame that was fatal in Tree::move().
        $restored->setData('some new binary content');
        $this->assertSame('some new binary content', $restored->getData());
    }
}
