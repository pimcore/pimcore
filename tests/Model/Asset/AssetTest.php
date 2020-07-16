<?php

namespace Pimcore\Tests\Model\Asset;

use Pimcore\Model\Asset;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;

/**
 * Class AssetTest
 *
 * @package Pimcore\Tests\Model\Asset
 * @group model.asset.asset
 */
class AssetTest extends ModelTestCase
{
    /**
     * Verifies that an asset can be saved with custom user modification id.
     *
     */
    public function testCustomUserModification()
    {
        $userId = 101;
        $asset = TestHelper::createImageAsset();

        //custom user modification
        $asset->setUserModification($userId);
        $asset->save();
        $this->assertEquals($userId, $asset->getUserModification(), 'Expected custom user modification id');

        //auto generated user modification
        $asset = Asset::getById($asset->getId(), true);
        $asset->save();
        $this->assertEquals(0, $asset->getUserModification(), 'Expected auto assigned user modification id');
    }

    /**
     * Verifies that an asset can be saved with custom modification date.
     *
     */
    public function testCustomModificationDate()
    {
        $customDateTime = new \Carbon\Carbon();
        $customDateTime = $customDateTime->subHour();

        $asset = TestHelper::createDocumentAsset();

        //custom modification date
        $asset->setModificationDate($customDateTime->getTimestamp());
        $asset->save();
        $this->assertEquals($customDateTime->getTimestamp(), $asset->getModificationDate(), 'Expected custom modification date');

        //auto generated modification date
        $currentTime = time();
        $asset = Asset::getById($asset->getId(), true);
        $asset->save();
        $this->assertGreaterThanOrEqual($currentTime, $asset->getModificationDate(), 'Expected auto assigned modification date');
    }
}
