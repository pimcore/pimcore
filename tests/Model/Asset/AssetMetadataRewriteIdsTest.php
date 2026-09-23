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
use Pimcore\Model\Asset\Service as AssetService;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Document;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Verifies that relations stored in asset metadata fields (e.g. type "asset") are
 * rewritten by Service::rewriteIds - used by "Search and Replace Assignments".
 *
 * @group model.asset.asset
 */
class AssetMetadataRewriteIdsTest extends ModelTestCase
{
    public function testRewriteIdsUpdatesAssetMetadataRelation(): void
    {
        $oldTarget = TestHelper::createImageAsset('rewrite-old-target-');
        $newTarget = TestHelper::createImageAsset('rewrite-new-target-');

        $referencingAsset = TestHelper::createImageAsset('rewrite-referencing-asset-');
        $referencingAsset->addMetadata('relatedImage', 'asset', $oldTarget);
        $referencingAsset->save();

        $referencingAsset = Asset::getById($referencingAsset->getId(), ['force' => true]);
        AssetService::rewriteIds(
            $referencingAsset,
            ['asset' => [$oldTarget->getId() => $newTarget->getId()]]
        );

        $rewritten = $referencingAsset->getMetadata('relatedImage');
        $this->assertInstanceOf(Asset::class, $rewritten);
        $this->assertEquals($newTarget->getId(), $rewritten->getId());

        // rewritten reference should survive save & reload
        $referencingAsset->save();
        $referencingAsset = Asset::getById($referencingAsset->getId(), ['force' => true]);

        $reloaded = $referencingAsset->getMetadata('relatedImage');
        $this->assertInstanceOf(Asset::class, $reloaded);
        $this->assertEquals($newTarget->getId(), $reloaded->getId());
    }

    public function testRewriteIdsLeavesUnrelatedMetadataUntouched(): void
    {
        $oldTarget = TestHelper::createImageAsset('rewrite-old-target-unrelated-');
        $newTarget = TestHelper::createImageAsset('rewrite-new-target-unrelated-');
        $otherAsset = TestHelper::createImageAsset('rewrite-other-asset-');

        $referencingAsset = TestHelper::createImageAsset('rewrite-referencing-asset-unrelated-');
        $referencingAsset->addMetadata('relatedImage', 'asset', $otherAsset);
        $referencingAsset->addMetadata('label', 'input', 'some text');
        $referencingAsset->save();

        $referencingAsset = Asset::getById($referencingAsset->getId(), ['force' => true]);
        AssetService::rewriteIds(
            $referencingAsset,
            ['asset' => [$oldTarget->getId() => $newTarget->getId()]]
        );

        $rewritten = $referencingAsset->getMetadata('relatedImage');
        $this->assertInstanceOf(Asset::class, $rewritten);
        $this->assertEquals($otherAsset->getId(), $rewritten->getId());
        $this->assertEquals('some text', $referencingAsset->getMetadata('label'));
    }

    public function testRewriteIdsUpdatesDocumentMetadataRelation(): void
    {
        $oldTarget = TestHelper::createEmptyDocumentPage('rewrite-old-target-doc-');
        $newTarget = TestHelper::createEmptyDocumentPage('rewrite-new-target-doc-');

        $referencingAsset = TestHelper::createImageAsset('rewrite-referencing-asset-doc-');
        $referencingAsset->addMetadata('relatedDocument', 'document', $oldTarget);
        $referencingAsset->save();

        $referencingAsset = Asset::getById($referencingAsset->getId(), ['force' => true]);
        AssetService::rewriteIds(
            $referencingAsset,
            ['document' => [$oldTarget->getId() => $newTarget->getId()]]
        );

        $rewritten = $referencingAsset->getMetadata('relatedDocument');
        $this->assertInstanceOf(Document::class, $rewritten);
        $this->assertEquals($newTarget->getId(), $rewritten->getId());

        // rewritten reference should survive save & reload
        $referencingAsset->save();
        $referencingAsset = Asset::getById($referencingAsset->getId(), ['force' => true]);

        $reloaded = $referencingAsset->getMetadata('relatedDocument');
        $this->assertInstanceOf(Document::class, $reloaded);
        $this->assertEquals($newTarget->getId(), $reloaded->getId());
    }

    public function testRewriteIdsUpdatesObjectMetadataRelation(): void
    {
        $oldTarget = TestHelper::createEmptyObject('rewrite-old-target-object-');
        $newTarget = TestHelper::createEmptyObject('rewrite-new-target-object-');

        $referencingAsset = TestHelper::createImageAsset('rewrite-referencing-asset-object-');
        $referencingAsset->addMetadata('relatedObject', 'object', $oldTarget);
        $referencingAsset->save();

        $referencingAsset = Asset::getById($referencingAsset->getId(), ['force' => true]);
        AssetService::rewriteIds(
            $referencingAsset,
            ['object' => [$oldTarget->getId() => $newTarget->getId()]]
        );

        $rewritten = $referencingAsset->getMetadata('relatedObject');
        $this->assertInstanceOf(Concrete::class, $rewritten);
        $this->assertEquals($newTarget->getId(), $rewritten->getId());

        // rewritten reference should survive save & reload
        $referencingAsset->save();
        $referencingAsset = Asset::getById($referencingAsset->getId(), ['force' => true]);

        $reloaded = $referencingAsset->getMetadata('relatedObject');
        $this->assertInstanceOf(Concrete::class, $reloaded);
        $this->assertEquals($newTarget->getId(), $reloaded->getId());
    }
}
