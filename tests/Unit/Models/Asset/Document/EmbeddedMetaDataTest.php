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

namespace Pimcore\Tests\Unit\Models\Asset\Document;

use Pimcore\Model\Asset\Document;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Console;

/**
 * Document assets support embedded meta data (#18478) by reusing the
 * EmbeddedMetaDataTrait which is also used by image and video assets.
 *
 * @group model.asset.document
 */
class EmbeddedMetaDataTest extends TestCase
{
    private function getFixturePath(): string
    {
        return TestHelper::resolveFilePath('assets/document/embedded-meta-data.pdf');
    }

    public function testEmbeddedMetaDataIsReadFromXmpPacket(): void
    {
        $document = new Document();
        $this->assertSame([], $document->getEmbeddedMetaData(false));
        $this->assertNull($document->getCustomSetting('embeddedMetaDataExtracted'));

        $document->handleEmbeddedMetaData(false, $this->getFixturePath());

        $metaData = $document->getEmbeddedMetaData(false);
        $this->assertTrue($document->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertSame('Pimcore Test Suite', $metaData['CreatorTool']);
        $this->assertSame('Pimcore Test Suite', $metaData['Producer']);
        $this->assertSame('pimcore, embedded, metadata', $metaData['Keywords']);
        $this->assertStringContainsString('Embedded Meta Data Test', $metaData['title']);
        $this->assertStringContainsString('Pimcore', $metaData['creator']);
    }

    public function testEmbeddedMetaDataIsReadWithExifTool(): void
    {
        if (!Console::getExecutable('exiftool')) {
            $this->markTestSkipped('exiftool is not available');
        }

        $document = new Document();
        $document->handleEmbeddedMetaData(true, $this->getFixturePath());

        $metaData = $document->getEmbeddedMetaData(false);
        $this->assertTrue($document->getCustomSetting('embeddedMetaDataExtracted'));
        $this->assertSame('Embedded Meta Data Test', $metaData['Title']);
        $this->assertSame('Pimcore Test Suite', $metaData['CreatorTool']);
        // list values are flattened
        $this->assertSame('pimcore | embedded | metadata', $metaData['Keywords']);
        $this->assertSame('pimcore | embedded | metadata', $metaData['Subject']);

        foreach (['Directory', 'FileName', 'SourceFile', 'ExifToolVersion'] as $removedKey) {
            $this->assertArrayNotHasKey($removedKey, $metaData);
        }
    }

    public function testEmbeddedMetaDataIsOnlyExtractedOnceUnlessDataChanged(): void
    {
        $previousMetaData = ['title' => 'from a previous extraction'];

        $document = new Document();
        $document->setCustomSetting('embeddedMetaData', $previousMetaData);
        $document->setCustomSetting('embeddedMetaDataExtracted', true);

        $document->handleEmbeddedMetaData(false, $this->getFixturePath());
        $this->assertSame($previousMetaData, $document->getEmbeddedMetaData(false));

        $document->setDataChanged(true);
        $document->handleEmbeddedMetaData(false, $this->getFixturePath());
        $this->assertSame('Pimcore Test Suite', $document->getEmbeddedMetaData(false)['CreatorTool']);
    }
}
