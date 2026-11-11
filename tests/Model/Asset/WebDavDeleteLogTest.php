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
use Pimcore\Tests\Model\DataType\UnserializeWakeupProbe;
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
 * object". The payload is therefore signed when it is logged, and only a correctly signed payload is
 * unserialized.
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

        WebDavService::saveDeleteLog([$path => WebDavService::createDeleteLogEntry($asset)]);

        $log = WebDavService::getDeleteLog();

        $this->assertArrayHasKey($path, $log);
        $this->assertIsString($log[$path]['data']);
        $this->assertIsString($log[$path]['signature']);
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
        WebDavService::saveDeleteLog([$path => WebDavService::createDeleteLogEntry($asset)]);

        $log = WebDavService::getDeleteLog();
        // the same call Tree::move() makes
        $restored = WebDavService::restoreDeletedAsset($path, $log[$path]);

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

    /**
     * Only a payload signed by createDeleteLogEntry() is unserialized. An entry without a valid
     * signature - written by an earlier version, or not written by Pimcore at all - is ignored, and
     * Tree::move() falls back to a plain move.
     */
    public function testUnsignedPayloadIsNotUnserialized(): void
    {
        UnserializeWakeupProbe::$wokeUp = false;
        $path = '/webdav-probe.jpg';

        $restored = WebDavService::restoreDeletedAsset($path, [
            'id' => 1,
            'timestamp' => time(),
            'data' => Serialize::serialize(new UnserializeWakeupProbe()),
        ]);

        $this->assertNull($restored);
        $this->assertFalse(UnserializeWakeupProbe::$wokeUp);
    }

    public function testPayloadWithForeignSignatureIsNotUnserialized(): void
    {
        UnserializeWakeupProbe::$wokeUp = false;
        $asset = TestHelper::createImageAsset();
        $path = $asset->getRealFullPath();

        $entry = WebDavService::createDeleteLogEntry($asset);
        $entry['data'] = Serialize::serialize(new UnserializeWakeupProbe());

        $this->assertNull(WebDavService::restoreDeletedAsset($path, $entry));
        $this->assertFalse(UnserializeWakeupProbe::$wokeUp);
    }

    /**
     * The timestamp limits how long an entry stays in the log, so it is signed as well: a valid entry
     * cannot be kept alive past its lifetime by moving its timestamp.
     */
    public function testSignedEntryIsBoundToItsTimestamp(): void
    {
        $asset = TestHelper::createImageAsset();
        $path = $asset->getRealFullPath();

        $entry = WebDavService::createDeleteLogEntry($asset);
        $entry['timestamp'] += 3600;

        $this->assertNull(WebDavService::restoreDeletedAsset($path, $entry));
    }

    /**
     * The signature covers the path the entry was logged for, so a valid entry cannot be replayed
     * to overwrite a different destination.
     */
    public function testSignedEntryIsBoundToItsPath(): void
    {
        $asset = TestHelper::createImageAsset();

        $entry = WebDavService::createDeleteLogEntry($asset);

        $this->assertNull(WebDavService::restoreDeletedAsset('/some/other/path.jpg', $entry));
        $this->assertInstanceOf(
            Asset\Image::class,
            WebDavService::restoreDeletedAsset($asset->getRealFullPath(), $entry)
        );
    }
}
